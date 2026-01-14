<?php

/**
 * AuthVerifyController
 *
 * Handles device signature verification requests.
 * Verifies the signature and returns pairing code with MQTT config.
 *
 * Endpoint: POST /api/devices/auth/verify
 *
 * @package App\Http\Controllers\Api\Device
 */

namespace App\Http\Controllers\Api\Device;

use App\Actions\Device\VerifySignatureAction;
use App\Http\Requests\Device\AuthVerifyRequest;
use Illuminate\Http\JsonResponse;

class AuthVerifyController
{
    /**
     * Create a new controller instance.
     *
     * @param VerifySignatureAction $action
     */
    public function __construct(
        private readonly VerifySignatureAction $action
    ) {
    }

    /**
     * Handle the incoming request.
     *
     * @param AuthVerifyRequest $request
     * @return JsonResponse
     */
    public function __invoke(AuthVerifyRequest $request): JsonResponse
    {
        return response()->json([
            'data' => $this->action->execute($request->validated()),
        ]);
    }
}
