<?php

/**
 * LinkController
 *
 * Links a user's integration to a device for metric display.
 *
 * Endpoint: POST /api/device-integrations
 *
 * @package App\Http\Controllers\Api\DeviceIntegration
 */

namespace App\Http\Controllers\Api\DeviceIntegration;

use App\Actions\DeviceIntegration\LinkIntegrationAction;
use App\Http\Requests\DeviceIntegration\LinkRequest;
use App\Http\Resources\DeviceIntegrationResource;
use App\Infrastructure\Repositories\Contracts\DeviceContract;
use App\Models\UserIntegration;
use Illuminate\Http\JsonResponse;

class LinkController
{
    /**
     * Create a new controller instance.
     *
     * @param DeviceContract $devices
     * @param LinkIntegrationAction $linkAction
     */
    public function __construct(
        private readonly DeviceContract $devices,
        private readonly LinkIntegrationAction $linkAction
    ) {
    }

    /**
     * Handle the request.
     *
     * @param LinkRequest $request
     * @return DeviceIntegrationResource|JsonResponse
     */
    public function __invoke(LinkRequest $request): DeviceIntegrationResource|JsonResponse
    {
        $device = $this->devices->find($request->validated('device_id'));

        if (! $device || $device->user_id !== $request->user()->id) {
            return response()->json([
                'error' => ['message' => 'Not found'],
            ], 404);
        }

        $userIntegration = UserIntegration::where('id', $request->validated('user_integration_id'))
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $userIntegration) {
            return response()->json([
                'error' => ['message' => 'Not found'],
            ], 404);
        }

        $deviceIntegration = $this->linkAction->execute(
            $device,
            $userIntegration,
            $request->validated('metric_type'),
            $request->validated('display_config', [])
        );

        return new DeviceIntegrationResource($deviceIntegration->load('userIntegration.integration'));
    }
}
