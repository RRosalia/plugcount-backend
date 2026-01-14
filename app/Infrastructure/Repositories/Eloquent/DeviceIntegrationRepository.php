<?php

/**
 * DeviceIntegrationRepository
 *
 * Eloquent implementation of the DeviceIntegrationContract.
 * Manages device-to-integration links for displaying metrics.
 *
 * @package App\Infrastructure\Repositories\Eloquent
 *
 * @extends BaseRepository<DeviceIntegration>
 */

namespace App\Infrastructure\Repositories\Eloquent;

use App\Infrastructure\Repositories\Contracts\DeviceIntegrationContract;
use App\Models\DeviceIntegration;
use Illuminate\Database\Eloquent\Collection;

class DeviceIntegrationRepository extends BaseRepository implements DeviceIntegrationContract
{
    /**
     * Get the model class name.
     *
     * @return class-string<DeviceIntegration>
     */
    public function model(): string
    {
        return DeviceIntegration::class;
    }

    /**
     * Get all device integrations for a device.
     *
     * @param int $deviceId
     * @return Collection<int, DeviceIntegration>
     */
    public function getForDevice(int $deviceId): Collection
    {
        return $this->where('device_id', $deviceId)
            ->with(['userIntegration.integration'])
            ->get();
    }

    /**
     * Get all device integrations that need syncing.
     *
     * @return Collection<int, DeviceIntegration>
     */
    public function getPendingSync(): Collection
    {
        return $this->where(function ($query) {
                $query->whereNull('last_synced_at')
                    ->orWhere('last_synced_at', '<', now()->subMinutes(1));
            })
            ->with(['device', 'userIntegration.integration'])
            ->get();
    }

    /**
     * Find by device and user integration.
     *
     * @param int $deviceId
     * @param int $userIntegrationId
     * @return DeviceIntegration|null
     */
    public function findByDeviceAndIntegration(int $deviceId, int $userIntegrationId): ?DeviceIntegration
    {
        return $this->where('device_id', $deviceId)
            ->where('user_integration_id', $userIntegrationId)
            ->first();
    }
}
