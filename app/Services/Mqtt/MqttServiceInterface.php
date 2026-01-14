<?php

namespace App\Services\Mqtt;

interface MqttServiceInterface
{
    /**
     * Publish a message to a topic.
     */
    public function publish(string $topic, string|array $message, int $qos = 0, bool $retain = false): void;

    /**
     * Subscribe to a topic with a callback.
     */
    public function subscribe(string $topic, callable $callback, int $qos = 0): void;

    /**
     * Disconnect from the broker.
     */
    public function disconnect(): void;

    /**
     * Check if connected to the broker.
     */
    public function isConnected(): bool;
}
