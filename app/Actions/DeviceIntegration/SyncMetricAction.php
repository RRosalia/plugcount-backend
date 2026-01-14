<?php

/**
 * SyncMetricAction
 *
 * Fetches the latest metric value from a provider and publishes
 * it to the device via MQTT if the value has changed.
 *
 * @package App\Actions\DeviceIntegration
 */

namespace App\Actions\DeviceIntegration;

use App\Models\DeviceIntegration;
use App\Services\Mqtt\MqttServiceInterface;
use App\Services\Metrics\MetricFetcherService;

class SyncMetricAction
{
    /**
     * Create a new action instance.
     *
     * @param MetricFetcherService $metricFetcher
     * @param MqttServiceInterface $mqtt
     */
    public function __construct(
        private readonly MetricFetcherService $metricFetcher,
        private readonly MqttServiceInterface $mqtt
    ) {
    }

    /**
     * Execute the action.
     *
     * @param DeviceIntegration $deviceIntegration
     * @return bool True if value changed and was published
     */
    public function execute(DeviceIntegration $deviceIntegration): bool
    {
        $userIntegration = $deviceIntegration->userIntegration;

        if (! $userIntegration || $userIntegration->isTokenExpired()) {
            return false;
        }

        $value = $this->metricFetcher->fetch(
            $userIntegration,
            $deviceIntegration->metric_type
        );

        if ($value === null) {
            return false;
        }

        $valueChanged = $value !== $deviceIntegration->last_value;

        $deviceIntegration->updateValue($value);

        if ($valueChanged) {
            $this->publishToDevice($deviceIntegration, $value);
        }

        return $valueChanged;
    }

    /**
     * Publish the metric value to the device via MQTT.
     *
     * @param DeviceIntegration $deviceIntegration
     * @param string $value
     * @return void
     */
    private function publishToDevice(DeviceIntegration $deviceIntegration, string $value): void
    {
        $device = $deviceIntegration->device;
        $topic = "devices/{$device->uuid}/integration";

        $this->mqtt->publish($topic, [
            'type' => $deviceIntegration->metric_type,
            'value' => $value,
            'label' => $deviceIntegration->getLabel(),
            'color' => $deviceIntegration->getColor(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
