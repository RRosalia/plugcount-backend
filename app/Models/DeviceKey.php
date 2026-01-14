<?php

/**
 * DeviceKey Model
 *
 * Represents a pre-registered device's cryptographic key pair.
 * Each device manufactured has its UUID and public key stored here
 * before it can be activated by a user.
 *
 * For development devices without ATECC608A secure element,
 * the is_simulated flag allows simplified authentication.
 *
 * @package App\Models
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class DeviceKey
 *
 * @property int $id
 * @property string $device_uuid Unique device identifier (UUID v4)
 * @property string $public_key ECDSA P-256 public key in PEM format
 * @property \Carbon\Carbon|null $activated_at When the device was first activated
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Device|null $device The device associated with this key
 */
class DeviceKey extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'device_uuid',
        'public_key',
        'activated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'activated_at' => 'datetime',
    ];

    /**
     * Get the device associated with this key.
     *
     * @return HasOne<Device>
     */
    public function device(): HasOne
    {
        return $this->hasOne(Device::class, 'uuid', 'device_uuid');
    }

    /**
     * Check if the device has been activated.
     *
     * @return bool
     */
    public function isActivated(): bool
    {
        return $this->activated_at !== null;
    }

    /**
     * Mark the device as activated.
     *
     * @return void
     */
    public function markActivated(): void
    {
        $this->update(['activated_at' => now()]);
    }
}
