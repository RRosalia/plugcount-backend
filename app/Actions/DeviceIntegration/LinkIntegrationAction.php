<?php

/**
 * LinkIntegrationAction
 *
 * Links a user's integration to a device, specifying which metric
 * should be displayed on that device.
 *
 * @package App\Actions\DeviceIntegration
 */

namespace App\Actions\DeviceIntegration;

use App\Infrastructure\Repositories\Contracts\DeviceIntegrationContract;
use App\Models\Device;
use App\Models\DeviceIntegration;
use App\Models\UserIntegration;

class LinkIntegrationAction
{
    /**
     * Create a new action instance.
     *
     * @param DeviceIntegrationContract $deviceIntegrations
     */
    public function __construct(
        private readonly DeviceIntegrationContract $deviceIntegrations
    ) {
    }

    /**
     * Execute the action.
     *
     * @param Device $device
     * @param UserIntegration $userIntegration
     * @param string $metricType
     * @param array<string, mixed> $displayConfig
     * @return DeviceIntegration
     */
    public function execute(
        Device $device,
        UserIntegration $userIntegration,
        string $metricType,
        array $displayConfig = []
    ): DeviceIntegration {
        $existing = $this->deviceIntegrations->findByDeviceAndIntegration(
            $device->id,
            $userIntegration->id
        );

        if ($existing) {
            $existing->update([
                'metric_type' => $metricType,
                'display_config' => $displayConfig,
                'is_active' => true,
            ]);

            return $existing->fresh();
        }

        return $this->deviceIntegrations->create([
            'device_id' => $device->id,
            'user_integration_id' => $userIntegration->id,
            'metric_type' => $metricType,
            'display_config' => $displayConfig,
            'is_active' => true,
        ]);
    }
}
