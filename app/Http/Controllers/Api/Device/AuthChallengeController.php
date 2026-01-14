<?php

/**
 * AuthChallengeController
 *
 * Handles device authentication challenge requests.
 * Generates a cryptographic challenge for the device to sign.
 *
 * Endpoint: POST /api/devices/auth/challenge
 *
 * @package App\Http\Controllers\Api\Device
 */

namespace App\Http\Controllers\Api\Device;

use App\Actions\Device\GenerateChallengeAction;
use App\Http\Requests\Device\AuthChallengeRequest;
use Illuminate\Http\JsonResponse;

class AuthChallengeController
{
    /**
     * Create a new controller instance.
     *
     * @param GenerateChallengeAction $action
     */
    public function __construct(
        private readonly GenerateChallengeAction $action
    ) {
    }

    /**
     * Handle the incoming request.
     *
     * @param AuthChallengeRequest $request
     * @return JsonResponse
     */
    public function __invoke(AuthChallengeRequest $request): JsonResponse
    {
        return response()->json([
            'data' => $this->action->execute($request->validated('device_uuid')),
        ]);
    }
}
