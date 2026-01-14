<?php

namespace App\Http\Controllers\Api\Device;

use App\Actions\Device\RegisterDeviceAction;
use App\Http\Requests\Device\RegisterDeviceRequest;
use Illuminate\Http\JsonResponse;

/**
 * Register a new device or retrieve pairing information for an existing one.
 *
 * This endpoint is called by ESP32 devices after connecting to WiFi.
 * It creates/updates the device record and generates a 6-digit pairing
 * code that the device displays on its screen. Users enter this code
 * in the web dashboard to link the device to their account.
 */
class RegisterController
{
    public function __construct(
        private readonly RegisterDeviceAction $action,
    ) {
    }

    public function __invoke(RegisterDeviceRequest $request): JsonResponse
    {
        $result = $this->action->execute($request->validated());

        return response()->json([
            'success' => true,
            'pairing_code' => $result['pairing_code'],
            'mqtt' => $result['mqtt'],
            'topics' => $result['topics'],
        ]);
    }
}
