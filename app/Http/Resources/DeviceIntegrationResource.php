<?php

/**
 * DeviceIntegrationResource
 *
 * JSON API resource for DeviceIntegration model.
 * Transforms device integration data for API responses.
 *
 * @package App\Http\Resources
 */

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\DeviceIntegration
 */
class DeviceIntegrationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'device_id' => $this->device_id,
            'metric_type' => $this->metric_type,
            'display_config' => [
                'label' => $this->getLabel(),
                'color' => $this->getColor(),
                'refresh_interval' => $this->getRefreshInterval(),
            ],
            'is_active' => $this->is_active,
            'last_value' => $this->last_value,
            'last_synced_at' => $this->last_synced_at?->toIso8601String(),
            'user_integration' => new UserIntegrationResource($this->whenLoaded('userIntegration')),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
