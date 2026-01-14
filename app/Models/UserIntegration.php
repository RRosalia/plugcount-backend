<?php

/**
 * UserIntegration Model
 *
 * Represents a user's connection to a third-party integration provider.
 * Stores OAuth tokens and external account information.
 *
 * OAuth tokens are encrypted at rest and hidden from serialization
 * for security purposes.
 *
 * @package App\Models
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class UserIntegration
 *
 * @property int $id
 * @property int $user_id Foreign key to users table
 * @property int $integration_id Foreign key to integrations table
 * @property string|null $access_token OAuth access token (encrypted)
 * @property string|null $refresh_token OAuth refresh token (encrypted)
 * @property \Carbon\Carbon|null $token_expires_at When the access token expires
 * @property string|null $external_user_id User's ID on the external platform
 * @property string|null $external_username User's username on the external platform
 * @property array|null $metadata Platform-specific data (e.g., channel ID, profile URL)
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read User $user The user who owns this integration
 * @property-read Integration $integration The integration provider
 * @property-read \Illuminate\Database\Eloquent\Collection<DeviceIntegration> $deviceIntegrations
 */
class UserIntegration extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'integration_id',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'external_user_id',
        'external_username',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'token_expires_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<string>
     */
    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    /**
     * Get the user who owns this integration.
     *
     * @return BelongsTo<User, UserIntegration>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the integration provider.
     *
     * @return BelongsTo<Integration, UserIntegration>
     */
    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }

    /**
     * Get all device integrations linked to this user integration.
     *
     * @return HasMany<DeviceIntegration>
     */
    public function deviceIntegrations(): HasMany
    {
        return $this->hasMany(DeviceIntegration::class);
    }

    /**
     * Check if the access token has expired.
     *
     * @return bool
     */
    public function isTokenExpired(): bool
    {
        if ($this->token_expires_at === null) {
            return false;
        }

        return $this->token_expires_at->isPast();
    }

    /**
     * Update the OAuth tokens.
     *
     * @param string $accessToken The new access token
     * @param string|null $refreshToken The new refresh token (keeps existing if null)
     * @param \DateTimeInterface|null $expiresAt When the token expires
     * @return void
     */
    public function updateTokens(string $accessToken, ?string $refreshToken, ?\DateTimeInterface $expiresAt): void
    {
        $this->update([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken ?? $this->refresh_token,
            'token_expires_at' => $expiresAt,
        ]);
    }
}
