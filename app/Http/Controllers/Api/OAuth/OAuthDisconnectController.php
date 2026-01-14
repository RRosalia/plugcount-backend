<?php

/**
 * OAuthDisconnectController
 *
 * Handles disconnecting a user's OAuth integration.
 * Removes the user integration and any linked device integrations.
 *
 * Endpoint: DELETE /api/oauth/{provider}
 *
 * @package App\Http\Controllers\Api\OAuth
 */

namespace App\Http\Controllers\Api\OAuth;

use App\Infrastructure\Repositories\Contracts\IntegrationContract;
use App\Models\UserIntegration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OAuthDisconnectController
{
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
     * Handle the disconnect request.
     *
     * @param Request $request
     * @param string $provider
     * @return JsonResponse
     */
    public function __invoke(Request $request, string $provider): JsonResponse
    {
        $integration = $this->integrations->findBySlug($provider);

        if (! $integration) {
            return response()->json([
                'error' => [
                    'message' => 'Integration not found',
                ],
            ], 404);
        }

        $userIntegration = UserIntegration::where('user_id', $request->user()->id)
            ->where('integration_id', $integration->id)
            ->first();

        if (! $userIntegration) {
            return response()->json([
                'error' => [
                    'message' => 'Integration not connected',
                ],
            ], 404);
        }

        // Delete linked device integrations first
        $userIntegration->deviceIntegrations()->delete();

        // Delete the user integration
        $userIntegration->delete();

        return response()->json([
            'data' => [
                'message' => 'Integration disconnected successfully',
            ],
        ]);
    }
}
