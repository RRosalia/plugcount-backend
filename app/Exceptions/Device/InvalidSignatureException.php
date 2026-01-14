<?php

/**
 * InvalidSignatureException
 *
 * Thrown when the device's signature verification fails.
 * The signature must match the challenge signed with the device's private key.
 *
 * @package App\Exceptions\Device
 */

namespace App\Exceptions\Device;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvalidSignatureException extends Exception
{
    /**
     * Create a new exception instance.
     */
    public function __construct()
    {
        parent::__construct('Invalid signature');
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
                'message' => 'Invalid signature',
            ],
        ], 401);
    }
}
