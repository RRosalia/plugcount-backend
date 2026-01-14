<?php

/**
 * Integration Model
 *
 * Represents a third-party integration provider (e.g., YouTube, Twitter, GitHub).
 * Each integration stores OAuth configuration and available metrics that can
 * be displayed on devices.
 *
 * Supported integrations:
 * - YouTube: subscribers, views, videos
 * - Twitter/X: followers, tweets, likes
 * - GitHub: stars, forks, followers
 * - Shopify: orders, revenue, customers
 * - Stripe: revenue, customers, subscriptions
 *
 * @package App\Models
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Integration
 *
 * @property int $id
 * @property string $slug Unique identifier (e.g., 'youtube', 'twitter')
 * @property string $name Display name (e.g., 'YouTube', 'Twitter/X')
 * @property string|null $description Brief description of the integration
 * @property string|null $icon_url URL to the integration's icon
 * @property array|null $oauth_config OAuth configuration (client_id, scopes, endpoints)
 * @property array|null $webhook_config Webhook configuration (verification, events)
 * @property bool $is_active Whether the integration is enabled
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<UserIntegration> $userIntegrations
 * @property-read \Illuminate\Database\Eloquent\Collection<WebhookEvent> $webhookEvents
 */
class Integration extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'slug',
        'name',
        'description',
        'icon_url',
        'oauth_config',
        'webhook_config',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'oauth_config' => 'array',
        'webhook_config' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get all user integrations for this provider.
     *
     * @return HasMany<UserIntegration>
     */
    public function userIntegrations(): HasMany
    {
        return $this->hasMany(UserIntegration::class);
    }

    /**
     * Get all webhook events for this integration.
     *
     * @return HasMany<WebhookEvent>
     */
    public function webhookEvents(): HasMany
    {
        return $this->hasMany(WebhookEvent::class);
    }

    /**
     * Get the OAuth client ID.
     *
     * @return string|null
     */
    public function getClientId(): ?string
    {
        return $this->oauth_config['client_id'] ?? null;
    }

    /**
     * Get the OAuth client secret.
     *
     * @return string|null
     */
    public function getClientSecret(): ?string
    {
        return $this->oauth_config['client_secret'] ?? null;
    }

    /**
     * Get the OAuth scopes.
     *
     * @return array<string>
     */
    public function getScopes(): array
    {
        return $this->oauth_config['scopes'] ?? [];
    }

    /**
     * Get the OAuth authorization endpoint.
     *
     * @return string|null
     */
    public function getAuthorizationEndpoint(): ?string
    {
        return $this->oauth_config['authorization_endpoint'] ?? null;
    }

    /**
     * Get the OAuth token endpoint.
     *
     * @return string|null
     */
    public function getTokenEndpoint(): ?string
    {
        return $this->oauth_config['token_endpoint'] ?? null;
    }
}
