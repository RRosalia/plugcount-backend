<?php

namespace App\Http\Controllers\Api\Device;

use App\Actions\Device\PairDeviceAction;
use App\Http\Requests\Device\PairDeviceRequest;
use Illuminate\Http\JsonResponse;

/**
 * Pair a device to the authenticated user's account.
 *
 * This endpoint is called by authenticated users from the web dashboard
 * when they enter a pairing code displayed on their physical device.
 * Upon successful pairing, the device is linked to the user's account
 * and can receive data updates via MQTT.
 */
class PairController
{
    public function __construct(
        private readonly PairDeviceAction $action,
    ) {
    }

    public function __invoke(PairDeviceRequest $request): JsonResponse
    {
        $device = $this->action->execute(
            $request->validated('pairing_code'),
            $request->user()
        );

        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired pairing code',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'device' => [
                'id' => $device->id,
                'uuid' => $device->uuid,
                'name' => $device->name,
                'status' => $device->status,
            ],
        ]);
    }
}
