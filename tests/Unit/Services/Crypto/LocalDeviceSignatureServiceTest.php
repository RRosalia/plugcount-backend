<?php

/**
 * LocalDeviceSignatureServiceTest
 *
 * Unit tests for the LocalDeviceSignatureService class.
 * Tests simulated signature verification for development devices.
 *
 * @package Tests\Unit\Services\Crypto
 */

namespace Tests\Unit\Services\Crypto;

use App\Models\DeviceKey;
use App\Services\Crypto\LocalDeviceSignatureService;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalDeviceSignatureServiceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_verifies_valid_simulated_signature(): void
    {
        $cache = $this->partialMock(CacheRepository::class);
        $service = new LocalDeviceSignatureService($cache);

        $deviceKey = DeviceKey::factory()->create();
        $challenge = bin2hex(random_bytes(32));
        $signature = $service->generateSimulatedSignature($deviceKey->device_uuid, $challenge);

        $result = $service->verify($deviceKey, $challenge, $signature);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_rejects_invalid_simulated_signature(): void
    {
        $cache = $this->partialMock(CacheRepository::class);
        $service = new LocalDeviceSignatureService($cache);

        $deviceKey = DeviceKey::factory()->create();
        $challenge = bin2hex(random_bytes(32));
        $invalidSignature = base64_encode('invalid-signature');

        $result = $service->verify($deviceKey, $challenge, $invalidSignature);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_rejects_signature_with_wrong_device_uuid(): void
    {
        $cache = $this->partialMock(CacheRepository::class);
        $service = new LocalDeviceSignatureService($cache);

        $deviceKey = DeviceKey::factory()->create();
        $challenge = bin2hex(random_bytes(32));
        $signatureWithWrongUuid = $service->generateSimulatedSignature('wrong-uuid', $challenge);

        $result = $service->verify($deviceKey, $challenge, $signatureWithWrongUuid);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_rejects_signature_with_wrong_challenge(): void
    {
        $cache = $this->partialMock(CacheRepository::class);
        $service = new LocalDeviceSignatureService($cache);

        $deviceKey = DeviceKey::factory()->create();
        $challenge = bin2hex(random_bytes(32));
        $wrongChallenge = bin2hex(random_bytes(32));
        $signature = $service->generateSimulatedSignature($deviceKey->device_uuid, $wrongChallenge);

        $result = $service->verify($deviceKey, $challenge, $signature);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_generates_consistent_simulated_signature(): void
    {
        $cache = $this->partialMock(CacheRepository::class);
        $service = new LocalDeviceSignatureService($cache);

        $deviceUuid = 'test-uuid';
        $challenge = 'test-challenge';

        $signature1 = $service->generateSimulatedSignature($deviceUuid, $challenge);
        $signature2 = $service->generateSimulatedSignature($deviceUuid, $challenge);

        $this->assertEquals($signature1, $signature2);
    }

    /** @test */
    public function it_generates_different_signatures_for_different_challenges(): void
    {
        $cache = $this->partialMock(CacheRepository::class);
        $service = new LocalDeviceSignatureService($cache);

        $deviceUuid = 'test-uuid';
        $challenge1 = 'challenge-1';
        $challenge2 = 'challenge-2';

        $signature1 = $service->generateSimulatedSignature($deviceUuid, $challenge1);
        $signature2 = $service->generateSimulatedSignature($deviceUuid, $challenge2);

        $this->assertNotEquals($signature1, $signature2);
    }

    /** @test */
    public function it_generates_different_signatures_for_different_devices(): void
    {
        $cache = $this->partialMock(CacheRepository::class);
        $service = new LocalDeviceSignatureService($cache);

        $challenge = 'same-challenge';

        $signature1 = $service->generateSimulatedSignature('device-1', $challenge);
        $signature2 = $service->generateSimulatedSignature('device-2', $challenge);

        $this->assertNotEquals($signature1, $signature2);
    }

    /** @test */
    public function simulated_signature_is_base64_encoded(): void
    {
        $cache = $this->partialMock(CacheRepository::class);
        $service = new LocalDeviceSignatureService($cache);

        $signature = $service->generateSimulatedSignature('uuid', 'challenge');

        $decoded = base64_decode($signature, true);
        $this->assertNotFalse($decoded);
        $this->assertEquals(32, strlen($decoded));
    }
}
