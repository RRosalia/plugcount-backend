<?php

/**
 * DeviceNotRegisteredException
 *
 * Thrown when a device UUID is not found in the device_keys table.
 * The device must be pre-registered during manufacturing.
 *
 * @package App\Exceptions\Device
 */

namespace App\Exceptions\Device;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceNotRegisteredException extends Exception
{
    /**
     * Create a new exception instance.
     *
     * @param string $deviceUuid
     */
    public function __construct(string $deviceUuid)
    {
        parent::__construct("Device not registered: {$deviceUuid}");
    }

    /**
     * Render the exception as an HTTP response.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'error' => [
                'message' => 'Device not registered',
            ],
        ], 404);
    }
}
