<?php

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\RegisterController as AuthRegisterController;
use App\Http\Controllers\Api\Auth\UserController;
use App\Http\Controllers\Api\Device\AuthChallengeController;
use App\Http\Controllers\Api\Device\AuthVerifyController;
use App\Http\Controllers\Api\Device\ListController as DeviceListController;
use App\Http\Controllers\Api\Device\PairController;
use App\Http\Controllers\Api\Device\RegisterController;
use App\Http\Controllers\Api\DeviceIntegration\LinkController;
use App\Http\Controllers\Api\DeviceIntegration\ListController;
use App\Http\Controllers\Api\DeviceIntegration\UnlinkController;
use App\Http\Controllers\Api\Integration\ListIntegrationsController;
use App\Http\Controllers\Api\OAuth\OAuthCallbackController;
use App\Http\Controllers\Api\OAuth\OAuthDisconnectController;
use App\Http\Controllers\Api\OAuth\OAuthRedirectController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {
    Route::post('register', AuthRegisterController::class);
    Route::post('login', LoginController::class);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', LogoutController::class);
    });
});

Route::middleware('auth:sanctum')->get('user', UserController::class);

/*
|--------------------------------------------------------------------------
| Device API Routes
|--------------------------------------------------------------------------
|
| Routes for ESP32 device communication.
|
| Public routes (device authentication):
| - POST /devices/auth/challenge - Request authentication challenge
| - POST /devices/auth/verify - Verify signature and get pairing code
| - POST /devices/register - Legacy registration endpoint
|
| Protected routes (user authentication):
| - POST /devices/pair - Pair device with user account
| - GET /devices/{id}/integrations - List device integrations
|
*/

Route::prefix('devices')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('challenge', AuthChallengeController::class);
        Route::post('verify', AuthVerifyController::class);
    });

    Route::post('register', RegisterController::class);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/', DeviceListController::class);
        Route::post('pair', PairController::class);
        Route::get('{deviceId}/integrations', ListController::class);
    });
});

/*
|--------------------------------------------------------------------------
| Integration API Routes
|--------------------------------------------------------------------------
|
| Routes for OAuth integrations (YouTube, Twitter, GitHub, Shopify, Stripe).
|
| Public routes:
| - GET /integrations - List available integrations
|
| Protected routes (user authentication):
| - GET /oauth/{provider}/redirect - Get OAuth authorization URL
| - GET /oauth/{provider}/callback - Handle OAuth callback
| - DELETE /oauth/{provider} - Disconnect integration
|
*/

Route::get('integrations', ListIntegrationsController::class);

Route::prefix('oauth')->middleware('auth:sanctum')->group(function () {
    Route::get('{provider}/redirect', OAuthRedirectController::class);
    Route::get('{provider}/callback', OAuthCallbackController::class);
    Route::delete('{provider}', OAuthDisconnectController::class);
});

/*
|--------------------------------------------------------------------------
| Device Integration API Routes
|--------------------------------------------------------------------------
|
| Routes for linking integrations to devices.
|
| Protected routes (user authentication):
| - POST /device-integrations - Link integration to device
| - DELETE /device-integrations/{id} - Unlink integration from device
|
*/

Route::prefix('device-integrations')->middleware('auth:sanctum')->group(function () {
    Route::post('/', LinkController::class);
    Route::delete('{id}', UnlinkController::class);
});
