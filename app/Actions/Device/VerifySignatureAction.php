<?php

/**
 * VerifySignatureAction
 *
 * Verifies a device's signature and completes authentication.
 * On success, creates/updates the device record and generates a pairing code.
 *
 * Flow:
 * 1. Device sends UUID, challenge, signature, and device info
 * 2. Server retrieves stored challenge from Redis
 * 3. Server verifies signature against device's public key
 * 4. Server creates/updates device record
 * 5. Server generates 6-digit pairing code
 * 6. Server returns pairing code and MQTT configuration
 *
 * @package App\Actions\Device
 */

namespace App\Actions\Device;

use App\Exceptions\Device\ChallengeExpiredException;
use App\Exceptions\Device\ChallengeMismatchException;
use App\Exceptions\Device\DeviceNotRegisteredException;
use App\Exceptions\Device\InvalidSignatureException;
use App\Infrastructure\Repositories\Contracts\DeviceContract;
use App\Infrastructure\Repositories\Contracts\DeviceKeyContract;
use App\Models\Device;
use App\Services\Crypto\DeviceSignatureService;

class VerifySignatureAction
{
    /**
     * Create a new action instance.
     *
     * @param DeviceKeyContract $deviceKeyRepository
     * @param DeviceContract $deviceRepository
     * @param DeviceSignatureService $signatureService
     */
    public function __construct(
        private readonly DeviceKeyContract $deviceKeyRepository,
        private readonly DeviceContract $deviceRepository,
        private readonly DeviceSignatureService $signatureService,
    ) {
    }

    /**
     * Execute the action.
     *
     * @param array{
     *     device_uuid: string,
     *     challenge: string,
     *     signature: string,
     *     mac_address?: string,
     *     ip_address?: string,
     *     firmware_version?: string
     * } $data
     * @return array{
     *     pairing_code: string,
     *     mqtt: array{broker: string, port: int},
     *     topics: array{integration: string, config: string, command: string, status: string, heartbeat: string}
     * }
     * @throws DeviceNotRegisteredException
     * @throws ChallengeExpiredException
     * @throws ChallengeMismatchException
     * @throws InvalidSignatureException
     */
    public function execute(array $data): array
    {
        $deviceKey = $this->deviceKeyRepository->findByUuid($data['device_uuid']);

        if (!$deviceKey) {
            throw new DeviceNotRegisteredException($data['device_uuid']);
        }

        $storedChallenge = $this->signatureService->getChallenge($data['device_uuid']);

        if (!$storedChallenge) {
            throw new ChallengeExpiredException();
        }

        if ($storedChallenge !== $data['challenge']) {
            throw new ChallengeMismatchException();
        }

        $isValid = $this->signatureService->verify(
            $deviceKey,
            $data['challenge'],
            $data['signature']
        );

        if (!$isValid) {
            throw new InvalidSignatureException();
        }

        $this->signatureService->clearChallenge($data['device_uuid']);

        if (!$deviceKey->isActivated()) {
            $this->deviceKeyRepository->markActivated($deviceKey);
        }

        $device = $this->findOrCreateDevice($data);
        $pairingCode = $this->deviceRepository->generatePairingCode($device);

        return [
            'pairing_code' => $pairingCode,
            'mqtt' => [
                'broker' => config('mqtt.host', 'localhost'),
                'port' => (int) config('mqtt.port', 1883),
            ],
            'topics' => [
                'integration' => "devices/{$device->uuid}/integration",
                'config' => "devices/{$device->uuid}/config",
                'command' => "devices/{$device->uuid}/command",
                'status' => "devices/{$device->uuid}/status",
                'heartbeat' => "devices/{$device->uuid}/heartbeat",
            ],
        ];
    }

    /**
     * Find an existing device or create a new one.
     *
     * @param array{device_uuid: string, mac_address?: string, ip_address?: string, firmware_version?: string} $data
     * @return Device
     */
    private function findOrCreateDevice(array $data): Device
    {
        $device = $this->deviceRepository->findByUuid($data['device_uuid']);

        $attributes = [
            'mac_address' => $data['mac_address'] ?? null,
            'ip_address' => $data['ip_address'] ?? null,
            'firmware_version' => $data['firmware_version'] ?? null,
            'status' => 'pairing',
        ];

        if ($device) {
            return $this->deviceRepository->updateDevice($device, $attributes);
        }

        return $this->deviceRepository->create([
            'uuid' => $data['device_uuid'],
            ...$attributes,
        ]);
    }
}
