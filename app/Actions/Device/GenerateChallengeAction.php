<?php

/**
 * GenerateChallengeAction
 *
 * Generates a cryptographic challenge for device authentication.
 * The challenge is a random 32-byte hex string with a 60-second TTL.
 *
 * Flow:
 * 1. Device sends its UUID
 * 2. Server verifies device exists in device_keys table
 * 3. Server generates and stores challenge in Redis
 * 4. Server returns challenge to device
 *
 * @package App\Actions\Device
 */

namespace App\Actions\Device;

use App\Exceptions\Device\DeviceNotRegisteredException;
use App\Infrastructure\Repositories\Contracts\DeviceKeyContract;
use App\Services\Crypto\DeviceSignatureService;

class GenerateChallengeAction
{
    /**
     * Create a new action instance.
     *
     * @param DeviceKeyContract $deviceKeyRepository
     * @param DeviceSignatureService $signatureService
     */
    public function __construct(
        private readonly DeviceKeyContract $deviceKeyRepository,
        private readonly DeviceSignatureService $signatureService,
    ) {
    }

    /**
     * Execute the action.
     *
     * @param string $deviceUuid The device's UUID
     * @return array{challenge: string, expires_in: int}
     * @throws DeviceNotRegisteredException
     */
    public function execute(string $deviceUuid): array
    {
        $deviceKey = $this->deviceKeyRepository->findByUuid($deviceUuid);

        if (!$deviceKey) {
            throw new DeviceNotRegisteredException($deviceUuid);
        }

        return $this->signatureService->generateChallenge($deviceUuid);
    }
}
