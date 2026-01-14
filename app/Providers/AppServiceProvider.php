<?php

/**
 * AppServiceProvider
 *
 * Core application service provider.
 * Registers Socialite OAuth providers for third-party integrations.
 *
 * @package App\Providers
 */

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Google\GoogleExtendSocialite;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Shopify\ShopifyExtendSocialite;
use SocialiteProviders\Stripe\StripeExtendSocialite;
use SocialiteProviders\Twitter\TwitterExtendSocialite;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerSocialiteProviders();
    }

    /**
     * Register Socialite OAuth providers.
     */
    private function registerSocialiteProviders(): void
    {
        Event::listen(SocialiteWasCalled::class, GoogleExtendSocialite::class . '@handle');
        Event::listen(SocialiteWasCalled::class, TwitterExtendSocialite::class . '@handle');
        Event::listen(SocialiteWasCalled::class, ShopifyExtendSocialite::class . '@handle');
        Event::listen(SocialiteWasCalled::class, StripeExtendSocialite::class . '@handle');
    }
}
