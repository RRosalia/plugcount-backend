<?php

namespace App\Models;

use App\Enums\DeviceCapability;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeviceType extends Model
{
    protected $fillable = [
        'name',
        'manufacturer',
        'model',
        'display_type',
        'display_width',
        'display_height',
        'capabilities',
        'description',
        'image_url',
    ];

    protected $casts = [
        'capabilities' => 'array',
        'display_width' => 'integer',
        'display_height' => 'integer',
    ];

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function hasCapability(DeviceCapability $capability): bool
    {
        return in_array($capability->value, $this->capabilities ?? [], true);
    }
}
