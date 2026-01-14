<?php

/**
 * StoreUserIntegrationAction
 *
 * Stores or updates a user's OAuth integration after successful authorization.
 * Encrypts tokens and extracts provider-specific metadata.
 *
 * @package App\Actions\OAuth
 */

namespace App\Actions\OAuth;

use App\Models\Integration;
use App\Models\User;
use App\Models\UserIntegration;
use Carbon\Carbon;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class StoreUserIntegrationAction
{
    /**
     * Execute the action.
     *
     * @param User $user
     * @param Integration $integration
     * @param SocialiteUser $socialiteUser
     * @return UserIntegration
     */
    public function execute(User $user, Integration $integration, SocialiteUser $socialiteUser): UserIntegration
    {
        $expiresAt = null;

        if (isset($socialiteUser->expiresIn) && $socialiteUser->expiresIn) {
            $expiresAt = Carbon::now()->addSeconds($socialiteUser->expiresIn);
        }

        return UserIntegration::updateOrCreate(
            [
                'user_id' => $user->id,
                'integration_id' => $integration->id,
            ],
            [
                'access_token' => $socialiteUser->token,
                'refresh_token' => $socialiteUser->refreshToken ?? null,
                'token_expires_at' => $expiresAt,
                'external_user_id' => $socialiteUser->getId(),
                'external_username' => $socialiteUser->getNickname() ?? $socialiteUser->getName(),
                'metadata' => $this->extractMetadata($integration->slug, $socialiteUser),
            ]
        );
    }

    /**
     * Extract provider-specific metadata from the Socialite user.
     *
     * @param string $provider
     * @param SocialiteUser $socialiteUser
     * @return array<string, mixed>
     */
    private function extractMetadata(string $provider, SocialiteUser $socialiteUser): array
    {
        $raw = $socialiteUser->getRaw();

        return match ($provider) {
            'google' => [
                'email' => $socialiteUser->getEmail(),
                'avatar' => $socialiteUser->getAvatar(),
            ],
            'github' => [
                'login' => $raw['login'] ?? null,
                'html_url' => $raw['html_url'] ?? null,
                'public_repos' => $raw['public_repos'] ?? null,
                'followers' => $raw['followers'] ?? null,
            ],
            'twitter' => [
                'username' => $raw['username'] ?? $socialiteUser->getNickname(),
                'profile_image_url' => $raw['profile_image_url'] ?? null,
            ],
            'shopify' => [
                'shop_domain' => $raw['shop'] ?? null,
            ],
            'stripe' => [
                'stripe_user_id' => $raw['stripe_user_id'] ?? $socialiteUser->getId(),
            ],
            default => [],
        };
    }
}
