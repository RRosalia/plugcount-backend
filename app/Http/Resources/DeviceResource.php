<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeviceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'mac_address' => $this->mac_address,
            'firmware_version' => $this->firmware_version,
            'is_online' => $this->is_online,
            'last_seen_at' => $this->last_seen_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
