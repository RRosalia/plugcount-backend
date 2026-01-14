<?php

/**
 * VerifySignatureActionTest
 *
 * Unit tests for the VerifySignatureAction class.
 * Tests the complete signature verification flow including
 * device creation, pairing code generation, and MQTT config.
 *
 * @package Tests\Unit\Actions\Device
 */

namespace Tests\Unit\Actions\Device;

use App\Actions\Device\VerifySignatureAction;
use App\Exceptions\Device\ChallengeExpiredException;
use App\Exceptions\Device\ChallengeMismatchException;
use App\Exceptions\Device\DeviceNotRegisteredException;
use App\Exceptions\Device\InvalidSignatureException;
use App\Models\Device;
use App\Models\DeviceKey;
use App\Services\Crypto\LocalDeviceSignatureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class VerifySignatureActionTest extends TestCase
{
    use RefreshDatabase;

    private VerifySignatureAction $action;
    private LocalDeviceSignatureService $signatureService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->action = app(VerifySignatureAction::class);
        $this->signatureService = app(LocalDeviceSignatureService::class);
    }

    /** @test */
    public function it_verifies_valid_signature_and_returns_pairing_code(): void
    {
        $deviceKey = DeviceKey::factory()->create();
        $challenge = $this->generateAndStoreChallenge($deviceKey->device_uuid);
        $signature = $this->signatureService->generateSimulatedSignature($deviceKey->device_uuid, $challenge);

        $result = $this->action->execute([
            'device_uuid' => $deviceKey->device_uuid,
            'challenge' => $challenge,
            'signature' => $signature,
        ]);

        $this->assertArrayHasKey('pairing_code', $result);
        $this->assertArrayHasKey('mqtt', $result);
        $this->assertArrayHasKey('topics', $result);
        $this->assertEquals(6, strlen($result['pairing_code']));
        $this->assertTrue(ctype_digit($result['pairing_code']));
    }

    /** @test */
    public function it_returns_mqtt_configuration(): void
    {
        $deviceKey = DeviceKey::factory()->create();
        $challenge = $this->generateAndStoreChallenge($deviceKey->device_uuid);
        $signature = $this->signatureService->generateSimulatedSignature($deviceKey->device_uuid, $challenge);

        $result = $this->action->execute([
            'device_uuid' => $deviceKey->device_uuid,
            'challenge' => $challenge,
            'signature' => $signature,
        ]);

        $this->assertArrayHasKey('broker', $result['mqtt']);
        $this->assertArrayHasKey('port', $result['mqtt']);
        $this->assertIsInt($result['mqtt']['port']);
    }

    /** @test */
    public function it_returns_mqtt_topics(): void
    {
        $deviceKey = DeviceKey::factory()->create();
        $challenge = $this->generateAndStoreChallenge($deviceKey->device_uuid);
        $signature = $this->signatureService->generateSimulatedSignature($deviceKey->device_uuid, $challenge);

        $result = $this->action->execute([
            'device_uuid' => $deviceKey->device_uuid,
            'challenge' => $challenge,
            'signature' => $signature,
        ]);

        $this->assertArrayHasKey('integration', $result['topics']);
        $this->assertArrayHasKey('config', $result['topics']);
        $this->assertArrayHasKey('command', $result['topics']);
        $this->assertArrayHasKey('status', $result['topics']);
        $this->assertArrayHasKey('heartbeat', $result['topics']);

        $this->assertStringContainsString($deviceKey->device_uuid, $result['topics']['integration']);
    }

    /** @test */
    public function it_creates_device_record_if_not_exists(): void
    {
        $deviceKey = DeviceKey::factory()->create();
        $challenge = $this->generateAndStoreChallenge($deviceKey->device_uuid);
        $signature = $this->signatureService->generateSimulatedSignature($deviceKey->device_uuid, $challenge);

        $this->assertNull(Device::where('uuid', $deviceKey->device_uuid)->first());

        $this->action->execute([
            'device_uuid' => $deviceKey->device_uuid,
            'challenge' => $challenge,
            'signature' => $signature,
            'mac_address' => 'AA:BB:CC:DD:EE:FF',
            'ip_address' => '192.168.1.100',
            'firmware_version' => '1.0.0',
        ]);

        $device = Device::where('uuid', $deviceKey->device_uuid)->first();

        $this->assertNotNull($device);
        $this->assertEquals('AA:BB:CC:DD:EE:FF', $device->mac_address);
        $this->assertEquals('192.168.1.100', $device->ip_address);
        $this->assertEquals('1.0.0', $device->firmware_version);
        $this->assertEquals('pairing', $device->status);
    }

    /** @test */
    public function it_updates_existing_device_record(): void
    {
        $deviceKey = DeviceKey::factory()->create();
        $device = Device::create([
            'uuid' => $deviceKey->device_uuid,
            'mac_address' => 'OLD:MA:CA:DD:RE:SS',
            'firmware_version' => '0.9.0',
            'status' => 'offline',
        ]);

        $challenge = $this->generateAndStoreChallenge($deviceKey->device_uuid);
        $signature = $this->signatureService->generateSimulatedSignature($deviceKey->device_uuid, $challenge);

        $this->action->execute([
            'device_uuid' => $deviceKey->device_uuid,
            'challenge' => $challenge,
            'signature' => $signature,
            'mac_address' => 'AA:BB:CC:DD:EE:FF',
            'firmware_version' => '1.0.0',
        ]);

        $device->refresh();

        $this->assertEquals('AA:BB:CC:DD:EE:FF', $device->mac_address);
        $this->assertEquals('1.0.0', $device->firmware_version);
        $this->assertEquals('pairing', $device->status);
    }

    /** @test */
    public function it_marks_device_key_as_activated(): void
    {
        $deviceKey = DeviceKey::factory()->create();
        $this->assertNull($deviceKey->activated_at);

        $challenge = $this->generateAndStoreChallenge($deviceKey->device_uuid);
        $signature = $this->signatureService->generateSimulatedSignature($deviceKey->device_uuid, $challenge);

        $this->action->execute([
            'device_uuid' => $deviceKey->device_uuid,
            'challenge' => $challenge,
            'signature' => $signature,
        ]);

        $deviceKey->refresh();

        $this->assertNotNull($deviceKey->activated_at);
    }

    /** @test */
    public function it_does_not_update_already_activated_device_key(): void
    {
        $activatedAt = now()->subDay();
        $deviceKey = DeviceKey::factory()->activated()->create([
            'activated_at' => $activatedAt,
        ]);

        $challenge = $this->generateAndStoreChallenge($deviceKey->device_uuid);
        $signature = $this->signatureService->generateSimulatedSignature($deviceKey->device_uuid, $challenge);

        $this->action->execute([
            'device_uuid' => $deviceKey->device_uuid,
            'challenge' => $challenge,
            'signature' => $signature,
        ]);

        $deviceKey->refresh();

        $this->assertEquals($activatedAt->timestamp, $deviceKey->activated_at->timestamp);
    }

    /** @test */
    public function it_clears_challenge_after_successful_verification(): void
    {
        $deviceKey = DeviceKey::factory()->create();
        $challenge = $this->generateAndStoreChallenge($deviceKey->device_uuid);
        $signature = $this->signatureService->generateSimulatedSignature($deviceKey->device_uuid, $challenge);

        $this->action->execute([
            'device_uuid' => $deviceKey->device_uuid,
            'challenge' => $challenge,
            'signature' => $signature,
        ]);

        $storedChallenge = Cache::store('challenges')->get("device_challenge:{$deviceKey->device_uuid}");

        $this->assertNull($storedChallenge);
    }

    /** @test */
    public function it_throws_exception_for_unregistered_device(): void
    {
        $this->expectException(DeviceNotRegisteredException::class);

        $this->action->execute([
            'device_uuid' => 'non-existent-uuid',
            'challenge' => 'some-challenge',
            'signature' => 'some-signature',
        ]);
    }

    /** @test */
    public function it_throws_exception_for_expired_challenge(): void
    {
        $deviceKey = DeviceKey::factory()->create();

        $this->expectException(ChallengeExpiredException::class);

        $this->action->execute([
            'device_uuid' => $deviceKey->device_uuid,
            'challenge' => 'expired-or-never-existed',
            'signature' => 'some-signature',
        ]);
    }

    /** @test */
    public function it_throws_exception_for_challenge_mismatch(): void
    {
        $deviceKey = DeviceKey::factory()->create();
        $this->generateAndStoreChallenge($deviceKey->device_uuid);

        $this->expectException(ChallengeMismatchException::class);

        $this->action->execute([
            'device_uuid' => $deviceKey->device_uuid,
            'challenge' => 'wrong-challenge',
            'signature' => 'some-signature',
        ]);
    }

    /** @test */
    public function it_throws_exception_for_invalid_signature(): void
    {
        $deviceKey = DeviceKey::factory()->create();
        $challenge = $this->generateAndStoreChallenge($deviceKey->device_uuid);

        $this->expectException(InvalidSignatureException::class);

        $this->action->execute([
            'device_uuid' => $deviceKey->device_uuid,
            'challenge' => $challenge,
            'signature' => base64_encode('invalid-signature'),
        ]);
    }

    /**
     * Helper to generate and store a challenge.
     *
     * @param string $deviceUuid
     * @return string
     */
    private function generateAndStoreChallenge(string $deviceUuid): string
    {
        $challenge = bin2hex(random_bytes(32));
        Cache::store('challenges')->put("device_challenge:{$deviceUuid}", $challenge, 60);

        return $challenge;
    }
}
