<?php

/**
 * LocalDeviceSignatureService
 *
 * Development-only extension of DeviceSignatureService that supports
 * simulated signatures for devices without ATECC608A secure elements.
 *
 * This service is ONLY registered in non-production environments.
 * In production, the base DeviceSignatureService is used which
 * requires real ECDSA signatures.
 *
 * Simulated Signature Algorithm:
 * - signature = base64(SHA256(device_uuid + challenge))
 *
 * @package App\Services\Crypto
 */

namespace App\Services\Crypto;

use App\Models\DeviceKey;

class LocalDeviceSignatureService extends DeviceSignatureService
{
    /**
     * Verify a device's signature against the stored challenge.
     *
     * In local/dev environment, accepts simulated signatures:
     * - signature = base64(SHA256(device_uuid + challenge))
     *
     * @param DeviceKey $deviceKey The device's key record
     * @param string $challenge The challenge that was signed
     * @param string $signature The signature (base64 encoded)
     * @return bool True if signature is valid
     */
    public function verify(DeviceKey $deviceKey, string $challenge, string $signature): bool
    {
        $expectedSignature = $this->generateSimulatedSignature($deviceKey->device_uuid, $challenge);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Generate a simulated signature.
     *
     * Use this to generate signatures that the ESP32 firmware
     * should produce in development mode.
     *
     * @param string $deviceUuid The device's UUID
     * @param string $challenge The challenge to sign
     * @return string Base64 encoded signature
     */
    public function generateSimulatedSignature(string $deviceUuid, string $challenge): string
    {
        return base64_encode(
            hash('sha256', $deviceUuid . $challenge, true)
        );
    }
}
