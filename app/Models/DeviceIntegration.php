<?php

/**
 * DeviceIntegration Model
 *
 * Links a device to a user's integration, specifying which metric
 * should be displayed on that device.
 *
 * For example, a device might display YouTube subscriber count
 * from a user's connected YouTube account.
 *
 * The display_config allows customization of how the metric
 * is shown (label, color, refresh interval).
 *
 * @package App\Models
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class DeviceIntegration
 *
 * @property int $id
 * @property int $device_id Foreign key to devices table
 * @property int $user_integration_id Foreign key to user_integrations table
 * @property string $metric_type The metric to display (e.g., 'subscribers', 'followers')
 * @property array|null $display_config Display configuration (label, color, refresh_interval)
 * @property bool $is_active Whether this integration is currently active
 * @property string|null $last_value The last fetched metric value
 * @property \Carbon\Carbon|null $last_synced_at When the metric was last synced
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Device $device The device displaying this metric
 * @property-read UserIntegration $userIntegration The user's integration connection
 */
class DeviceIntegration extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'device_id',
        'user_integration_id',
        'metric_type',
        'display_config',
        'is_active',
        'last_value',
        'last_synced_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'display_config' => 'array',
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    /**
     * Get the device that displays this metric.
     *
     * @return BelongsTo<Device, DeviceIntegration>
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    /**
     * Get the user integration that provides this metric.
     *
     * @return BelongsTo<UserIntegration, DeviceIntegration>
     */
    public function userIntegration(): BelongsTo
    {
        return $this->belongsTo(UserIntegration::class);
    }

    /**
     * Get the display label for this metric.
     *
     * @return string
     */
    public function getLabel(): string
    {
        return $this->display_config['label'] ?? ucfirst($this->metric_type);
    }

    /**
     * Get the display color for this metric.
     *
     * @return string Hex color code
     */
    public function getColor(): string
    {
        return $this->display_config['color'] ?? '#FFFFFF';
    }

    /**
     * Get the refresh interval in seconds.
     *
     * @return int
     */
    public function getRefreshInterval(): int
    {
        return $this->display_config['refresh_interval'] ?? 60;
    }

    /**
     * Update the metric value and sync timestamp.
     *
     * @param string $value The new metric value
     * @return void
     */
    public function updateValue(string $value): void
    {
        $this->update([
            'last_value' => $value,
            'last_synced_at' => now(),
        ]);
    }
}
