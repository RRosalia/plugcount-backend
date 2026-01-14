<?php

/**
 * IntegrationResource
 *
 * JSON API resource for Integration model.
 * Transforms integration data for API responses.
 *
 * @package App\Http\Resources
 */

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Integration
 */
class IntegrationResource extends JsonResource
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
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'icon_url' => $this->icon_url,
        ];
    }
}
