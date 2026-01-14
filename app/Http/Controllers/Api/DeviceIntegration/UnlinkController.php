<?php

/**
 * UnlinkController
 *
 * Unlinks an integration from a device.
 *
 * Endpoint: DELETE /api/device-integrations/{id}
 *
 * @package App\Http\Controllers\Api\DeviceIntegration
 */

namespace App\Http\Controllers\Api\DeviceIntegration;

use App\Infrastructure\Repositories\Contracts\DeviceIntegrationContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UnlinkController
{
    /**
     * Create a new controller instance.
     *
     * @param DeviceIntegrationContract $deviceIntegrations
     */
    public function __construct(
        private readonly DeviceIntegrationContract $deviceIntegrations
    ) {
    }

    /**
     * Handle the request.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function __invoke(Request $request, int $id): JsonResponse
    {
        $deviceIntegration = $this->deviceIntegrations
            ->with(['device'])
            ->find($id);

        if (! $deviceIntegration || $deviceIntegration->device->user_id !== $request->user()->id) {
            return response()->json([
                'error' => ['message' => 'Not found'],
            ], 404);
        }

        $deviceIntegration->delete();

        return response()->json(null, 204);
    }
}
