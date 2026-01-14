<?php

/**
 * UserIntegrationResource
 *
 * JSON API resource for UserIntegration model.
 * Transforms user integration data for API responses.
 *
 * @package App\Http\Resources
 */

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\UserIntegration
 */
class UserIntegrationResource extends JsonResource
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
            'integration' => new IntegrationResource($this->whenLoaded('integration')),
            'external_user_id' => $this->external_user_id,
            'external_username' => $this->external_username,
            'token_expires_at' => $this->token_expires_at,
            'is_token_expired' => $this->isTokenExpired(),
            'metadata' => $this->metadata,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}