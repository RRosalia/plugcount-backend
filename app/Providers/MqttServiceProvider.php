<?php

namespace App\Providers;

use App\Services\Mqtt\AwsIotMqttService;
use App\Services\Mqtt\MqttServiceInterface;
use App\Services\Mqtt\PhpMqttService;
use Illuminate\Support\ServiceProvider;

class MqttServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MqttServiceInterface::class, function ($app) {
            $driver = config('mqtt.driver', 'php-mqtt');

            return match ($driver) {
                'aws-iot' => $app->make(AwsIotMqttService::class),
                default => $app->make(PhpMqttService::class),
            };
        });
    }

    public function boot(): void
    {
        //
    }
}
