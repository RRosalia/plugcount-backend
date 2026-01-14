<?php

/**
 * ChallengeMismatchException
 *
 * Thrown when the provided challenge does not match the stored challenge.
 * This may indicate a replay attack or request tampering.
 *
 * @package App\Exceptions\Device
 */

namespace App\Exceptions\Device;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChallengeMismatchException extends Exception
{
    /**
     * Create a new exception instance.
     */
    public function __construct()
    {
        parent::__construct('Challenge mismatch');
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
                'message' => 'Challenge mismatch',
            ],
        ], 400);
    }
}
