<?php

namespace App\Actions\Device;

use App\Models\Device;
use App\Services\Mqtt\MqttServiceInterface;

class PublishToDeviceAction
{
    public function __construct(
        private readonly MqttServiceInterface $mqtt,
    ) {
    }

    /**
     * Publish display data to a device via MQTT.
     *
     * @param array{
     *     displayType: string,
     *     value: string|int,
     *     label?: string,
     *     color?: string,
     *     trend?: string
     * } $payload
     */
    public function execute(Device $device, array $payload): void
    {
        $topic = "devices/{$device->uuid}/integration";

        $this->mqtt->publish($topic, $payload);
    }
}
