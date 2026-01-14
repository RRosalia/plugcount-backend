<?php

/**
 * OAuthRedirectController
 *
 * Handles the initial OAuth redirect to the provider's authorization page.
 * Returns the authorization URL for the frontend to redirect the user.
 *
 * Endpoint: GET /api/oauth/{provider}/redirect
 *
 * @package App\Http\Controllers\Api\OAuth
 */

namespace App\Http\Controllers\Api\OAuth;

use App\Infrastructure\Repositories\Contracts\IntegrationContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse;

class OAuthRedirectController
{
    /**
     * Supported OAuth providers.
     *
     * @var array<string>
     */
    private const SUPPORTED_PROVIDERS = ['google', 'github', 'twitter', 'shopify', 'stripe'];

    /**
     * Provider-specific scopes for accessing metrics.
     *
     * @var array<string, array<string>>
     */
    private const PROVIDER_SCOPES = [
        'google' => [
            'https://www.googleapis.com/auth/youtube.readonly',
        ],
        'github' => [
            'read:user',
            'repo',
        ],
        'twitter' => [
            'tweet.read',
            'users.read',
        ],
        'shopify' => [
            'read_orders',
            'read_customers',
        ],
        'stripe' => [
            'read_only',
        ],
    ];

    /**
     * Create a new controller instance.
     *
     * @param IntegrationContract $integrations
     */
    public function __construct(
        private readonly IntegrationContract $integrations
    ) {
    }

    /**
     * Handle the OAuth redirect request.
     *
     * @param Request $request
     * @param string $provider
     * @return RedirectResponse|JsonResponse
     */
    public function __invoke(Request $request, string $provider): RedirectResponse|JsonResponse
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

        $scopes = self::PROVIDER_SCOPES[$provider] ?? [];

        $driver = Socialite::driver($provider)
            ->scopes($scopes)
            ->stateless();

        if ($request->wantsJson()) {
            return response()->json([
                'data' => [
                    'redirect_url' => $driver->redirect()->getTargetUrl(),
                ],
            ]);
        }

        return $driver->redirect();
    }
}
