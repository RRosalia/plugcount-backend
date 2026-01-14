<?php

/**
 * RepositoryServiceProvider
 *
 * Registers repository bindings and configures specialized cache stores.
 *
 * Cache Stores:
 * - challenges: Redis database 2, used for device authentication challenges
 *
 * Device Signature Service:
 * - Production: DeviceSignatureService (real ECDSA verification only)
 * - Local/Dev: LocalDeviceSignatureService (simulated signatures)
 *
 * @package App\Providers
 */

namespace App\Providers;

use App\Infrastructure\Repositories\Contracts\DeviceContract;
use App\Infrastructure\Repositories\Contracts\DeviceIntegrationContract;
use App\Infrastructure\Repositories\Contracts\DeviceKeyContract;
use App\Infrastructure\Repositories\Contracts\IntegrationContract;
use App\Infrastructure\Repositories\Eloquent\DeviceIntegrationRepository;
use App\Infrastructure\Repositories\Eloquent\DeviceRepository;
use App\Infrastructure\Repositories\Eloquent\DeviceKeyRepository;
use App\Infrastructure\Repositories\Eloquent\IntegrationRepository;
use App\Infrastructure\Repositories\Eloquent\PublicDeviceIntegrationRepository;
use App\Services\Crypto\DeviceSignatureService;
use App\Services\Crypto\LocalDeviceSignatureService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Repository contract bindings.
     *
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        DeviceContract::class => DeviceRepository::class,
        DeviceKeyContract::class => DeviceKeyRepository::class,
        IntegrationContract::class => IntegrationRepository::class,
        DeviceIntegrationContract::class => DeviceIntegrationRepository::class,
    ];

    /**
     * Singleton bindings.
     *
     * @var array<class-string, class-string>
     */
    public array $singletons = [
        PublicDeviceIntegrationRepository::class => PublicDeviceIntegrationRepository::class,
    ];

    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->registerDeviceSignatureService();
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        //
    }

    /**
     * Register the DeviceSignatureService with the challenges cache store.
     *
     * Uses Redis database 2 to isolate challenge data from other caches.
     * In non-production, uses LocalDeviceSignatureService for simulated signatures.
     *
     * @return void
     */
    private function registerDeviceSignatureService(): void
    {
        $this->app->singleton(DeviceSignatureService::class, function () {
            $cache = Cache::store('challenges');

            if ($this->app->environment('production')) {
                return new DeviceSignatureService($cache);
            }

            return new LocalDeviceSignatureService($cache);
        });
    }
}
