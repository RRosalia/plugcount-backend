<?php

/**
 * DeviceKeyRepository
 *
 * Eloquent implementation of the DeviceKeyContract.
 * Manages device cryptographic keys for authentication.
 *
 * @package App\Infrastructure\Repositories\Eloquent
 *
 * @extends BaseRepository<DeviceKey>
 */

namespace App\Infrastructure\Repositories\Eloquent;

use App\Infrastructure\Repositories\Contracts\DeviceKeyContract;
use App\Models\DeviceKey;

class DeviceKeyRepository extends BaseRepository implements DeviceKeyContract
{
    /**
     * Get the model class name.
     *
     * @return class-string<DeviceKey>
     */
    public function model(): string
    {
        return DeviceKey::class;
    }

    /**
     * Find a device key by UUID.
     *
     * @param string $deviceUuid The device's UUID
     * @return DeviceKey|null
     */
    public function findByUuid(string $deviceUuid): ?DeviceKey
    {
        return $this->where('device_uuid', $deviceUuid)->first();
    }

    /**
     * Mark a device key as activated.
     *
     * @param DeviceKey $deviceKey
     * @return DeviceKey
     */
    public function markActivated(DeviceKey $deviceKey): DeviceKey
    {
        $deviceKey->markActivated();

        return $deviceKey->fresh();
    }
}
