<?php

/**
 * ListIntegrationsController
 *
 * Returns a list of available integration providers.
 * Only active integrations are returned.
 *
 * Endpoint: GET /api/integrations
 *
 * @package App\Http\Controllers\Api\Integration
 */

namespace App\Http\Controllers\Api\Integration;

use App\Http\Resources\IntegrationResource;
use App\Infrastructure\Repositories\Contracts\IntegrationContract;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ListIntegrationsController
{
    /**
     * Create a new controller instance.
     *
     * @param IntegrationContract $integrations
     */
    public function __construct(
        private readonly IntegrationContract $integrations
    ) {
    }

    /**
     * Handle the request.
     *
     * @return AnonymousResourceCollection
     */
    public function __invoke(): AnonymousResourceCollection
    {
        $integrations = $this->integrations->active()->get();

        return IntegrationResource::collection($integrations);
    }
}
