<?php

/**
 * DeviceContract
 *
 * Repository contract for managing devices.
 * Handles device persistence and pairing code management.
 *
 * @package App\Infrastructure\Repositories\Contracts
 *
 * @extends BaseRepositoryInterface<Device>
 */

namespace App\Infrastructure\Repositories\Contracts;

use App\Models\Device;
use App\Models\User;

interface DeviceContract extends BaseRepositoryInterface
{
    /**
     * Find a device by UUID.
     *
     * @param string $uuid
     * @return Device|null
     */
    public function findByUuid(string $uuid): ?Device;

    /**
     * Update a device model.
     *
     * @param Device $device
     * @param array<string, mixed> $attributes
     * @return Device
     */
    public function updateDevice(Device $device, array $attributes): Device;

    /**
     * Generate a pairing code for a device.
     *
     * @param Device $device
     * @return string
     */
    public function generatePairingCode(Device $device): string;

    /**
     * Get the pairing code for a device.
     *
     * @param Device $device
     * @return string|null
     */
    public function getPairingCode(Device $device): ?string;

    /**
     * Find a device by its pairing code.
     *
     * @param string $code
     * @return Device|null
     */
    public function findByPairingCode(string $code): ?Device;

    /**
     * Pair a device with a user.
     *
     * @param string $code
     * @param User $user
     * @return Device|null
     */
    public function pairWithUser(string $code, User $user): ?Device;

    /**
     * Clear a device's pairing code.
     *
     * @param Device $device
     * @return void
     */
    public function clearPairingCode(Device $device): void;
}
