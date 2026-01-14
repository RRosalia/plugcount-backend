<?php

/**
 * DeviceSignatureService
 *
 * Handles cryptographic signature verification for device authentication.
 * Supports ECDSA P-256 signatures from ATECC608A secure elements.
 *
 * Challenge-Response Flow:
 * 1. Device requests challenge from server
 * 2. Server generates random 32-byte challenge (stored in Redis DB 2)
 * 3. Device signs challenge with private key
 * 4. Server verifies signature against stored public key
 *
 * @package App\Services\Crypto
 */

namespace App\Services\Crypto;

use App\Models\DeviceKey;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

class DeviceSignatureService
{
    /**
     * Challenge TTL in seconds (60 seconds).
     *
     * @var int
     */
    protected const CHALLENGE_TTL = 60;

    /**
     * Challenge cache key prefix.
     *
     * @var string
     */
    protected const CHALLENGE_PREFIX = 'device_challenge:';

    /**
     * Create a new DeviceSignatureService instance.
     *
     * @param CacheRepository $cache The challenges cache store (Redis DB 2)
     */
    public function __construct(
        protected readonly CacheRepository $cache
    ) {
    }

    /**
     * Generate a cryptographic challenge for device authentication.
     *
     * The challenge is a random 32-byte hex string stored in cache
     * with a 60-second TTL.
     *
     * @param string $deviceUuid The device's UUID
     * @return array{challenge: string, expires_in: int}
     */
    public function generateChallenge(string $deviceUuid): array
    {
        $challenge = bin2hex(random_bytes(32));

        $this->cache->put(
            self::CHALLENGE_PREFIX . $deviceUuid,
            $challenge,
            self::CHALLENGE_TTL
        );

        return [
            'challenge' => $challenge,
            'expires_in' => self::CHALLENGE_TTL,
        ];
    }

    /**
     * Retrieve a stored challenge for a device.
     *
     * @param string $deviceUuid The device's UUID
     * @return string|null The challenge or null if expired/not found
     */
    public function getChallenge(string $deviceUuid): ?string
    {
        return $this->cache->get(self::CHALLENGE_PREFIX . $deviceUuid);
    }

    /**
     * Clear a stored challenge after verification.
     *
     * @param string $deviceUuid The device's UUID
     * @return void
     */
    public function clearChallenge(string $deviceUuid): void
    {
        $this->cache->forget(self::CHALLENGE_PREFIX . $deviceUuid);
    }

    /**
     * Verify a device's signature against the stored challenge.
     *
     * Verifies ECDSA P-256 signature using OpenSSL.
     *
     * @param DeviceKey $deviceKey The device's key record
     * @param string $challenge The challenge that was signed
     * @param string $signature The signature (base64 encoded)
     * @return bool True if signature is valid
     */
    public function verify(DeviceKey $deviceKey, string $challenge, string $signature): bool
    {
        return $this->verifyEcdsa($deviceKey->public_key, $challenge, $signature);
    }

    /**
     * Verify an ECDSA P-256 signature.
     *
     * Uses OpenSSL to verify the signature against the public key.
     * The signature should be in DER format, base64 encoded.
     *
     * @param string $publicKeyPem The public key in PEM format
     * @param string $challenge The challenge that was signed
     * @param string $signature The signature (base64 encoded DER)
     * @return bool True if signature is valid
     */
    protected function verifyEcdsa(string $publicKeyPem, string $challenge, string $signature): bool
    {
        $publicKey = openssl_pkey_get_public($publicKeyPem);

        if ($publicKey === false) {
            return false;
        }

        $signatureBytes = base64_decode($signature, true);

        if ($signatureBytes === false) {
            return false;
        }

        $result = openssl_verify(
            $challenge,
            $signatureBytes,
            $publicKey,
            OPENSSL_ALGO_SHA256
        );

        return $result === 1;
    }

    /**
     * Generate an ECDSA P-256 key pair for testing.
     *
     * Returns a new key pair that can be used for test fixtures.
     *
     * @return array{private_key: string, public_key: string}
     */
    public function generateKeyPair(): array
    {
        $config = [
            'curve_name' => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
        ];

        $privateKey = openssl_pkey_new($config);
        openssl_pkey_export($privateKey, $privateKeyPem);

        $details = openssl_pkey_get_details($privateKey);
        $publicKeyPem = $details['key'];

        return [
            'private_key' => $privateKeyPem,
            'public_key' => $publicKeyPem,
        ];
    }
}
