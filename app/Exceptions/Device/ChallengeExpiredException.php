<?php

/**
 * ChallengeExpiredException
 *
 * Thrown when the authentication challenge has expired or was not found.
 * Challenges have a 60-second TTL.
 *
 * @package App\Exceptions\Device
 */

namespace App\Exceptions\Device;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChallengeExpiredException extends Exception
{
    /**
     * Create a new exception instance.
     */
    public function __construct()
    {
        parent::__construct('Challenge expired or not found');
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
                'message' => 'Challenge expired or not found',
            ],
        ], 410);
    }
}
