<?php

/**
 * WebhookEvent Model
 *
 * Logs incoming webhook events from integration providers.
 * Used for debugging, replay, and audit purposes.
 *
 * Events are marked as processed once handled, with any
 * errors recorded for troubleshooting.
 *
 * @package App\Models
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class WebhookEvent
 *
 * @property int $id
 * @property int $integration_id Foreign key to integrations table
 * @property string|null $event_type The type of webhook event (platform-specific)
 * @property array|null $payload The raw webhook payload
 * @property \Carbon\Carbon|null $processed_at When the event was processed
 * @property string|null $error Error message if processing failed
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Integration $integration The integration that sent this event
 */
class WebhookEvent extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'integration_id',
        'event_type',
        'payload',
        'processed_at',
        'error',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'payload' => 'array',
        'processed_at' => 'datetime',
    ];

    /**
     * Get the integration that sent this event.
     *
     * @return BelongsTo<Integration, WebhookEvent>
     */
    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }

    /**
     * Check if the event has been processed.
     *
     * @return bool
     */
    public function isProcessed(): bool
    {
        return $this->processed_at !== null;
    }

    /**
     * Check if the event processing failed.
     *
     * @return bool
     */
    public function hasFailed(): bool
    {
        return $this->error !== null;
    }

    /**
     * Mark the event as successfully processed.
     *
     * @return void
     */
    public function markProcessed(): void
    {
        $this->update(['processed_at' => now()]);
    }

    /**
     * Mark the event as failed with an error message.
     *
     * @param string $error The error message
     * @return void
     */
    public function markFailed(string $error): void
    {
        $this->update([
            'processed_at' => now(),
            'error' => $error,
        ]);
    }
}
