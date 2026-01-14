<?php

namespace App\Services\Mqtt;

use PhpMqtt\Client\Facades\MQTT;
use PhpMqtt\Client\Contracts\MqttClient;
use Illuminate\Support\Facades\Log;

class PhpMqttService implements MqttServiceInterface
{
    private ?MqttClient $client = null;

    public function publish(string $topic, string|array $message, int $qos = 0, bool $retain = false): void
    {
        $payload = is_array($message) ? json_encode($message) : $message;

        try {
            MQTT::publish($topic, $payload, $qos, $retain);
            Log::debug("MQTT published to {$topic}", ['payload' => $payload]);
        } catch (\Exception $e) {
            Log::error("MQTT publish failed: {$e->getMessage()}", [
                'topic' => $topic,
                'payload' => $payload,
            ]);
            throw $e;
        }
    }

    public function subscribe(string $topic, callable $callback, int $qos = 0): void
    {
        try {
            $this->client = MQTT::connection();
            $this->client->subscribe($topic, function (string $topic, string $message) use ($callback) {
                $decoded = json_decode($message, true);
                $callback($topic, $decoded ?? $message);
            }, $qos);
        } catch (\Exception $e) {
            Log::error("MQTT subscribe failed: {$e->getMessage()}", [
                'topic' => $topic,
            ]);
            throw $e;
        }
    }

    public function disconnect(): void
    {
        if ($this->client !== null) {
            $this->client->disconnect();
            $this->client = null;
        }
    }

    public function isConnected(): bool
    {
        return $this->client !== null && $this->client->isConnected();
    }
}
