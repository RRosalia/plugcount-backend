<?php

/**
 * OAuthCallbackController
 *
 * Handles the OAuth callback from the provider after user authorization.
 * Exchanges the authorization code for tokens and stores the user integration.
 *
 * Endpoint: GET /api/oauth/{provider}/callback
 *
 * @package App\Http\Controllers\Api\OAuth
 */

namespace App\Http\Controllers\Api\OAuth;

use App\Actions\OAuth\StoreUserIntegrationAction;
use App\Infrastructure\Repositories\Contracts\IntegrationContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class OAuthCallbackController
{
    /**
     * Supported OAuth providers.
     *
     * @var array<string>
     */
    private const SUPPORTED_PROVIDERS = ['google', 'github', 'twitter', 'shopify', 'stripe'];

    /**
     * Create a new controller instance.
     *
     * @param IntegrationContract $integrations
     * @param StoreUserIntegrationAction $storeAction
     */
    public function __construct(
        private readonly IntegrationContract $integrations,
        private readonly StoreUserIntegrationAction $storeAction
    ) {
    }

    /**
     * Handle the OAuth callback request.
     *
     * @param Request $request
     * @param string $provider
     * @return JsonResponse
     */
    public function __invoke(Request $request, string $provider): JsonResponse
    {
        if (! in_array($provider, self::SUPPORTED_PROVIDERS, true)) {
            return response()->json([
                'error' => [
                    'message' => 'Unsupported OAuth provider',
                ],
            ], 400);
        }

        $integration = $this->integrations->findActiveBySlug($provider);

        if (! $integration) {
            return response()->json([
                'error' => [
                    'message' => 'Integration not available',
                ],
            ], 404);
        }

        try {
            $socialiteUser = Socialite::driver($provider)->stateless()->user();

            $userIntegration = $this->storeAction->execute(
                user: $request->user(),
                integration: $integration,
                socialiteUser: $socialiteUser
            );

            return response()->json([
                'data' => [
                    'id' => $userIntegration->id,
                    'provider' => $provider,
                    'external_username' => $userIntegration->external_username,
                    'connected_at' => $userIntegration->created_at->toIso8601String(),
                ],
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'error' => [
                    'message' => 'Failed to complete OAuth flow',
                ],
            ], 400);
        }
    }
}
