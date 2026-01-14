<?php

/**
 * DeviceIntegrationContract
 *
 * Repository contract for managing device-to-integration links.
 * Handles the relationship between devices and user integrations
 * for displaying metrics on devices.
 *
 * @package App\Infrastructure\Repositories\Contracts
 *
 * @extends BaseRepositoryInterface<DeviceIntegration>
 */

namespace App\Infrastructure\Repositories\Contracts;

use App\Models\DeviceIntegration;
use Illuminate\Database\Eloquent\Collection;

interface DeviceIntegrationContract extends BaseRepositoryInterface
{
    /**
     * Get all device integrations for a device.
     *
     * @param int $deviceId
     * @return Collection<int, DeviceIntegration>
     */
    public function getForDevice(int $deviceId): Collection;

    /**
     * Get all device integrations that need syncing.
     *
     * @return Collection<int, DeviceIntegration>
     */
    public function getPendingSync(): Collection;

    /**
     * Find by device and user integration.
     *
     * @param int $deviceId
     * @param int $userIntegrationId
     * @return DeviceIntegration|null
     */
    public function findByDeviceAndIntegration(int $deviceId, int $userIntegrationId): ?DeviceIntegration;
}
