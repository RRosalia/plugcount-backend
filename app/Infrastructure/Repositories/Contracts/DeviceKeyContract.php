<?php

/**
 * DeviceKeyContract
 *
 * Repository contract for managing device cryptographic keys.
 * Device keys are pre-registered during manufacturing and used
 * for challenge-response authentication.
 *
 * @package App\Infrastructure\Repositories\Contracts
 *
 * @extends BaseRepositoryInterface<DeviceKey>
 */

namespace App\Infrastructure\Repositories\Contracts;

use App\Models\DeviceKey;

interface DeviceKeyContract extends BaseRepositoryInterface
{
    /**
     * Find a device key by UUID.
     *
     * @param string $deviceUuid The device's UUID
     * @return DeviceKey|null
     */
    public function findByUuid(string $deviceUuid): ?DeviceKey;

    /**
     * Mark a device key as activated.
     *
     * @param DeviceKey $deviceKey
     * @return DeviceKey
     */
    public function markActivated(DeviceKey $deviceKey): DeviceKey;
}
