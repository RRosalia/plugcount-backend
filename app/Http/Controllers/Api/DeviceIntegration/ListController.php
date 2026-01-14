<?php

/**
 * ListController
 *
 * Lists device integrations for a specific device.
 *
 * Endpoint: GET /api/devices/{deviceId}/integrations
 *
 * @package App\Http\Controllers\Api\DeviceIntegration
 */

namespace App\Http\Controllers\Api\DeviceIntegration;

use App\Http\Resources\DeviceIntegrationResource;
use App\Infrastructure\Repositories\Contracts\DeviceContract;
use App\Infrastructure\Repositories\Eloquent\PublicDeviceIntegrationRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ListController
{
    /**
     * Create a new controller instance.
     *
     * @param DeviceContract $devices
     * @param PublicDeviceIntegrationRepository $deviceIntegrations
     */
    public function __construct(
        private readonly DeviceContract $devices,
        private readonly PublicDeviceIntegrationRepository $deviceIntegrations
    ) {
    }

    /**
     * Handle the request.
     *
     * @param Request $request
     * @param int $deviceId
     * @return AnonymousResourceCollection|JsonResponse
     */
    public function __invoke(Request $request, int $deviceId): AnonymousResourceCollection|JsonResponse
    {
        $device = $this->devices->find($deviceId);

        if (! $device || $device->user_id !== $request->user()->id) {
            return response()->json([
                'error' => ['message' => 'Not found'],
            ], 404);
        }

        $integrations = $this->deviceIntegrations->getForDevice($deviceId);

        return DeviceIntegrationResource::collection($integrations);
    }
}
