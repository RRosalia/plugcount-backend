<?php

/**
 * DeviceSignatureServiceTest
 *
 * Unit tests for the DeviceSignatureService class.
 * Tests challenge generation, retrieval, clearing, and ECDSA verification.
 *
 * @package Tests\Unit\Services\Crypto
 */

namespace Tests\Unit\Services\Crypto;

use App\Models\DeviceKey;
use App\Services\Crypto\DeviceSignatureService;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceSignatureServiceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_generates_challenge_with_correct_format(): void
    {
        $deviceUuid = 'test-device-uuid';

        $cache = $this->partialMock(CacheRepository::class, function ($mock) use ($deviceUuid) {
            $mock->shouldReceive('put')
                ->once()
                ->withArgs(function ($key, $value, $ttl) use ($deviceUuid) {
                    return $key === "device_challenge:{$deviceUuid}"
                        && strlen($value) === 64
                        && ctype_xdigit($value)
                        && $ttl === 60;
                });
        });

        $service = new DeviceSignatureService($cache);
        $result = $service->generateChallenge($deviceUuid);

        $this->assertArrayHasKey('challenge', $result);
        $this->assertArrayHasKey('expires_in', $result);
        $this->assertEquals(64, strlen($result['challenge']));
        $this->assertTrue(ctype_xdigit($result['challenge']));
        $this->assertEquals(60, $result['expires_in']);
    }

    /** @test */
    public function it_retrieves_stored_challenge(): void
    {
        $deviceUuid = 'test-device-uuid';
        $expectedChallenge = bin2hex(random_bytes(32));

        $cache = $this->partialMock(CacheRepository::class, function ($mock) use ($deviceUuid, $expectedChallenge) {
            $mock->shouldReceive('get')
                ->once()
                ->with("device_challenge:{$deviceUuid}")
                ->andReturn($expectedChallenge);
        });

        $service = new DeviceSignatureService($cache);
        $result = $service->getChallenge($deviceUuid);

        $this->assertEquals($expectedChallenge, $result);
    }

    /** @test */
    public function it_returns_null_for_non_existent_challenge(): void
    {
        $deviceUuid = 'non-existent-uuid';

        $cache = $this->partialMock(CacheRepository::class, function ($mock) use ($deviceUuid) {
            $mock->shouldReceive('get')
                ->once()
                ->with("device_challenge:{$deviceUuid}")
                ->andReturn(null);
        });

        $service = new DeviceSignatureService($cache);
        $result = $service->getChallenge($deviceUuid);

        $this->assertNull($result);
    }

    /** @test */
    public function it_clears_challenge_from_cache(): void
    {
        $deviceUuid = 'test-device-uuid';

        $cache = $this->partialMock(CacheRepository::class, function ($mock) use ($deviceUuid) {
            $mock->shouldReceive('forget')
                ->once()
                ->with("device_challenge:{$deviceUuid}");
        });

        $service = new DeviceSignatureService($cache);
        $service->clearChallenge($deviceUuid);
    }

    /** @test */
    public function it_verifies_valid_ecdsa_signature(): void
    {
        $cache = $this->partialMock(CacheRepository::class);
        $service = new DeviceSignatureService($cache);

        $keyPair = $service->generateKeyPair();
        $challenge = bin2hex(random_bytes(32));

        $privateKey = openssl_pkey_get_private($keyPair['private_key']);
        openssl_sign($challenge, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        $signatureBase64 = base64_encode($signature);

        $deviceKey = DeviceKey::factory()->create([
            'public_key' => $keyPair['public_key'],
        ]);

        $result = $service->verify($deviceKey, $challenge, $signatureBase64);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_rejects_invalid_ecdsa_signature(): void
    {
        $cache = $this->partialMock(CacheRepository::class);
        $service = new DeviceSignatureService($cache);

        $keyPair = $service->generateKeyPair();
        $challenge = bin2hex(random_bytes(32));
        $invalidSignature = base64_encode('invalid-signature');

        $deviceKey = DeviceKey::factory()->create([
            'public_key' => $keyPair['public_key'],
        ]);

        $result = $service->verify($deviceKey, $challenge, $invalidSignature);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_rejects_signature_with_wrong_public_key(): void
    {
        $cache = $this->partialMock(CacheRepository::class);
        $service = new DeviceSignatureService($cache);

        $keyPair1 = $service->generateKeyPair();
        $keyPair2 = $service->generateKeyPair();
        $challenge = bin2hex(random_bytes(32));

        $privateKey = openssl_pkey_get_private($keyPair1['private_key']);
        openssl_sign($challenge, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        $signatureBase64 = base64_encode($signature);

        $deviceKey = DeviceKey::factory()->create([
            'public_key' => $keyPair2['public_key'],
        ]);

        $result = $service->verify($deviceKey, $challenge, $signatureBase64);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_rejects_invalid_public_key_format(): void
    {
        $cache = $this->partialMock(CacheRepository::class);
        $service = new DeviceSignatureService($cache);

        $challenge = bin2hex(random_bytes(32));
        $signature = base64_encode('some-signature');

        $deviceKey = DeviceKey::factory()->create([
            'public_key' => 'invalid-public-key',
        ]);

        $result = $service->verify($deviceKey, $challenge, $signature);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_generates_valid_ecdsa_key_pair(): void
    {
        $cache = $this->partialMock(CacheRepository::class);
        $service = new DeviceSignatureService($cache);

        $keyPair = $service->generateKeyPair();

        $this->assertArrayHasKey('private_key', $keyPair);
        $this->assertArrayHasKey('public_key', $keyPair);
        $this->assertStringContainsString('BEGIN PRIVATE KEY', $keyPair['private_key']);
        $this->assertStringContainsString('BEGIN PUBLIC KEY', $keyPair['public_key']);

        $privateKey = openssl_pkey_get_private($keyPair['private_key']);
        $publicKey = openssl_pkey_get_public($keyPair['public_key']);

        $this->assertNotFalse($privateKey);
        $this->assertNotFalse($publicKey);
    }
}
