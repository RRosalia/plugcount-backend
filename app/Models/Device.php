<?php

/**
 * Device Model
 *
 * Represents a physical counter display device (ESP32 with LCD).
 * Devices can be paired with users and linked to integrations
 * to display real-time metrics.
 *
 * Device states:
 * - pairing: Device is waiting to be paired with a user
 * - online: Device is connected and active
 * - offline: Device is not connected
 *
 * @package App\Models
 */

namespace App\Models;

use App\Enums\DeviceCapability;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class Device
 *
 * @property int $id
 * @property string $uuid Unique device identifier
 * @property int|null $device_type_id Foreign key to device_types table
 * @property int|null $user_id Foreign key to users table (owner)
 * @property string|null $name User-assigned device name
 * @property string|null $mac_address Device MAC address
 * @property string|null $ip_address Current IP address
 * @property string|null $firmware_version Current firmware version
 * @property string $status Device status (pairing, online, offline)
 * @property \Carbon\Carbon|null $last_seen_at Last heartbeat timestamp
 * @property array|null $display_config Display configuration
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read User|null $user The device owner
 * @property-read DeviceType|null $deviceType The device type/model
 * @property-read DeviceKey|null $deviceKey The device's cryptographic key
 * @property-read \Illuminate\Database\Eloquent\Collection<DeviceIntegration> $deviceIntegrations
 */
class Device extends Model
{
    protected $fillable = [
        'uuid',
        'device_type_id',
        'user_id',
        'name',
        'mac_address',
        'ip_address',
        'firmware_version',
        'status',
        'last_seen_at',
        'display_config',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'display_config' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function deviceType(): BelongsTo
    {
        return $this->belongsTo(DeviceType::class);
    }

    /**
     * Get the device's cryptographic key.
     *
     * @return HasOne<DeviceKey>
     */
    public function deviceKey(): HasOne
    {
        return $this->hasOne(DeviceKey::class, 'device_uuid', 'uuid');
    }

    /**
     * Get all integrations linked to this device.
     *
     * @return HasMany<DeviceIntegration>
     */
    public function deviceIntegrations(): HasMany
    {
        return $this->hasMany(DeviceIntegration::class);
    }

    public function isPaired(): bool
    {
        return $this->user_id !== null;
    }

    public function hasCapability(DeviceCapability $capability): bool
    {
        return $this->deviceType?->hasCapability($capability) ?? false;
    }

    public function markOnline(): void
    {
        $this->update([
            'status' => 'online',
            'last_seen_at' => now(),
        ]);
    }

    public function markOffline(): void
    {
        $this->update(['status' => 'offline']);
    }
}
