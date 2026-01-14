<?php

/**
 * DeviceRepository
 *
 * Eloquent implementation of the DeviceContract.
 * Manages device persistence and pairing code management using Redis cache.
 *
 * @package App\Infrastructure\Repositories\Eloquent
 *
 * @extends BaseRepository<Device>
 */

namespace App\Infrastructure\Repositories\Eloquent;

use App\Infrastructure\Repositories\Contracts\DeviceContract;
use App\Models\Device;
use App\Models\User;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

class DeviceRepository extends BaseRepository implements DeviceContract
{
    private const CODE_PREFIX = 'pairing_code:';
    private const DEVICE_PREFIX = 'pairing_device:';
    private const CODE_TTL_MINUTES = 10;

    /**
     * Create a new repository instance.
     *
     * @param CacheRepository $cache
     */
    public function __construct(
        private readonly CacheRepository $cache,
    ) {
    }

    /**
     * Get the model class name.
     *
     * @return class-string<Device>
     */
    public function model(): string
    {
        return Device::class;
    }

    /**
     * Find a device by UUID.
     *
     * @param string $uuid
     * @return Device|null
     */
    public function findByUuid(string $uuid): ?Device
    {
        return $this->where('uuid', $uuid)->first();
    }

    /**
     * Update a device model.
     *
     * @param Device $device
     * @param array<string, mixed> $attributes
     * @return Device
     */
    public function updateDevice(Device $device, array $attributes): Device
    {
        $device->update($attributes);

        return $device->fresh();
    }

    /**
     * Generate a pairing code for a device.
     *
     * @param Device $device
     * @return string
     */
    public function generatePairingCode(Device $device): string
    {
        $this->clearPairingCode($device);

        $code = $this->generateUniqueCode();

        $this->cache->put(
            self::CODE_PREFIX . $code,
            $device->uuid,
            now()->addMinutes(self::CODE_TTL_MINUTES)
        );

        $this->cache->put(
            self::DEVICE_PREFIX . $device->uuid,
            $code,
            now()->addMinutes(self::CODE_TTL_MINUTES)
        );

        return $code;
    }

    /**
     * Get the pairing code for a device.
     *
     * @param Device $device
     * @return string|null
     */
    public function getPairingCode(Device $device): ?string
    {
        return $this->cache->get(self::DEVICE_PREFIX . $device->uuid);
    }

    /**
     * Find a device by its pairing code.
     *
     * @param string $code
     * @return Device|null
     */
    public function findByPairingCode(string $code): ?Device
    {
        $deviceUuid = $this->cache->get(self::CODE_PREFIX . $code);

        if (! $deviceUuid) {
            return null;
        }

        return $this->findByUuid($deviceUuid);
    }

    /**
     * Pair a device with a user.
     *
     * @param string $code
     * @param User $user
     * @return Device|null
     */
    public function pairWithUser(string $code, User $user): ?Device
    {
        $device = $this->findByPairingCode($code);

        if (! $device) {
            return null;
        }

        $device->update([
            'user_id' => $user->id,
            'status' => 'online',
        ]);

        $this->clearCode($code, $device->uuid);

        return $device->fresh();
    }

    /**
     * Clear a device's pairing code.
     *
     * @param Device $device
     * @return void
     */
    public function clearPairingCode(Device $device): void
    {
        $existingCode = $this->cache->get(self::DEVICE_PREFIX . $device->uuid);

        if ($existingCode) {
            $this->clearCode($existingCode, $device->uuid);
        }
    }

    /**
     * Generate a unique 6-digit pairing code.
     *
     * @return string
     */
    private function generateUniqueCode(): string
    {
        do {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while ($this->cache->has(self::CODE_PREFIX . $code));

        return $code;
    }

    /**
     * Clear a pairing code from cache.
     *
     * @param string $code
     * @param string $deviceUuid
     * @return void
     */
    private function clearCode(string $code, string $deviceUuid): void
    {
        $this->cache->forget(self::CODE_PREFIX . $code);
        $this->cache->forget(self::DEVICE_PREFIX . $deviceUuid);
    }
}
