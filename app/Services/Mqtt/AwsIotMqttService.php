<?php

namespace App\Services\Mqtt;

use Illuminate\Support\Facades\Log;

/**
 * AWS IoT Core MQTT Service
 *
 * Stub implementation for future AWS IoT Core integration.
 * @see https://docs.aws.amazon.com/iot/latest/developerguide/mqtt.html
 */
class AwsIotMqttService implements MqttServiceInterface
{
    public function __construct(
        // AWS SDK client will be injected here
    ) {
    }

    public function publish(string $topic, string|array $message, int $qos = 0, bool $retain = false): void
    {
        // TODO: Implement using AWS SDK for PHP
        // Use AWS\IotDataPlane\IotDataPlaneClient::publish()
        throw new \RuntimeException('AWS IoT MQTT service not yet implemented');
    }

    public function subscribe(string $topic, callable $callback, int $qos = 0): void
    {
        // TODO: Implement using AWS IoT Rules or Lambda
        // Note: Direct subscription not available via AWS SDK, use IoT Rules instead
        throw new \RuntimeException('AWS IoT MQTT service not yet implemented');
    }

    public function disconnect(): void
    {
        // AWS IoT uses HTTP-based publishing, no persistent connection
    }

    public function isConnected(): bool
    {
        // AWS IoT connections are per-request
        return true;
    }
}
