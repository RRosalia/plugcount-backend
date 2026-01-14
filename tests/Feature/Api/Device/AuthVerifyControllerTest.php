<?php

/**
 * AuthVerifyControllerTest
 *
 * Feature tests for the AuthVerifyController.
 * Tests HTTP layer including routes, request validation, and response formatting.
 * Action classes are mocked as they are tested separately.
 *
 * @package Tests\Feature\Api\Device
 */

namespace Tests\Feature\Api\Device;

use App\Actions\Device\VerifySignatureAction;
use App\Exceptions\Device\ChallengeExpiredException;
use App\Exceptions\Device\ChallengeMismatchException;
use App\Exceptions\Device\DeviceNotRegisteredException;
use App\Exceptions\Device\InvalidSignatureException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthVerifyControllerTest extends TestCase
{
    use RefreshDatabase;

    private array $validPayload;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validPayload = [
            'device_uuid' => '550e8400-e29b-41d4-a716-446655440000',
            'challenge' => bin2hex(random_bytes(32)),
            'signature' => base64_encode('valid-signature'),
            'mac_address' => 'AA:BB:CC:DD:EE:FF',
            'ip_address' => '192.168.1.100',
            'firmware_version' => '1.0.0',
        ];
    }

    /** @test */
    public function it_returns_pairing_code_and_mqtt_config_for_valid_signature(): void
    {
        $expectedResponse = [
            'pairing_code' => '123456',
            'mqtt' => [
                'broker' => 'mqtt.example.com',
                'port' => 1883,
            ],
            'topics' => [
                'integration' => 'devices/550e8400-e29b-41d4-a716-446655440000/integration',
                'config' => 'devices/550e8400-e29b-41d4-a716-446655440000/config',
                'command' => 'devices/550e8400-e29b-41d4-a716-446655440000/command',
                'status' => 'devices/550e8400-e29b-41d4-a716-446655440000/status',
                'heartbeat' => 'devices/550e8400-e29b-41d4-a716-446655440000/heartbeat',
            ],
        ];

        $this->partialMock(VerifySignatureAction::class, function ($mock) use ($expectedResponse) {
            $mock->shouldReceive('execute')
                ->once()
                ->with($this->validPayload)
                ->andReturn($expectedResponse);
        });

        $response = $this->postJson('/api/devices/auth/verify', $this->validPayload);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'pairing_code',
                    'mqtt' => ['broker', 'port'],
                    'topics' => ['integration', 'config', 'command', 'status', 'heartbeat'],
                ],
            ])
            ->assertJson([
                'data' => $expectedResponse,
            ]);
    }

    /** @test */
    public function it_returns_404_for_unregistered_device(): void
    {
        $this->partialMock(VerifySignatureAction::class, function ($mock) {
            $mock->shouldReceive('execute')
                ->once()
                ->andThrow(new DeviceNotRegisteredException($this->validPayload['device_uuid']));
        });

        $response = $this->postJson('/api/devices/auth/verify', $this->validPayload);

        $response->assertStatus(404)
            ->assertJson([
                'error' => [
                    'message' => 'Device not registered',
                ],
            ]);
    }

    /** @test */
    public function it_returns_410_for_expired_challenge(): void
    {
        $this->partialMock(VerifySignatureAction::class, function ($mock) {
            $mock->shouldReceive('execute')
                ->once()
                ->andThrow(new ChallengeExpiredException());
        });

        $response = $this->postJson('/api/devices/auth/verify', $this->validPayload);

        $response->assertStatus(410)
            ->assertJson([
                'error' => [
                    'message' => 'Challenge expired or not found',
                ],
            ]);
    }

    /** @test */
    public function it_returns_400_for_challenge_mismatch(): void
    {
        $this->partialMock(VerifySignatureAction::class, function ($mock) {
            $mock->shouldReceive('execute')
                ->once()
                ->andThrow(new ChallengeMismatchException());
        });

        $response = $this->postJson('/api/devices/auth/verify', $this->validPayload);

        $response->assertStatus(400)
            ->assertJson([
                'error' => [
                    'message' => 'Challenge mismatch',
                ],
            ]);
    }

    /** @test */
    public function it_returns_401_for_invalid_signature(): void
    {
        $this->partialMock(VerifySignatureAction::class, function ($mock) {
            $mock->shouldReceive('execute')
                ->once()
                ->andThrow(new InvalidSignatureException());
        });

        $response = $this->postJson('/api/devices/auth/verify', $this->validPayload);

        $response->assertStatus(401)
            ->assertJson([
                'error' => [
                    'message' => 'Invalid signature',
                ],
            ]);
    }

    /** @test */
    public function it_requires_device_uuid(): void
    {
        $payload = $this->validPayload;
        unset($payload['device_uuid']);

        $response = $this->postJson('/api/devices/auth/verify', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['device_uuid']);
    }

    /** @test */
    public function it_requires_challenge(): void
    {
        $payload = $this->validPayload;
        unset($payload['challenge']);

        $response = $this->postJson('/api/devices/auth/verify', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['challenge']);
    }

    /** @test */
    public function it_requires_signature(): void
    {
        $payload = $this->validPayload;
        unset($payload['signature']);

        $response = $this->postJson('/api/devices/auth/verify', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['signature']);
    }

    /** @test */
    public function it_validates_device_uuid_format(): void
    {
        $payload = $this->validPayload;
        $payload['device_uuid'] = 'not-a-valid-uuid';

        $response = $this->postJson('/api/devices/auth/verify', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['device_uuid']);
    }

    /** @test */
    public function it_validates_challenge_length(): void
    {
        $payload = $this->validPayload;
        $payload['challenge'] = 'too-short';

        $response = $this->postJson('/api/devices/auth/verify', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['challenge']);
    }

    /** @test */
    public function it_validates_mac_address_format(): void
    {
        $payload = $this->validPayload;
        $payload['mac_address'] = 'invalid-mac';

        $response = $this->postJson('/api/devices/auth/verify', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['mac_address']);
    }

    /** @test */
    public function it_validates_ip_address_format(): void
    {
        $payload = $this->validPayload;
        $payload['ip_address'] = 'not-an-ip';

        $response = $this->postJson('/api/devices/auth/verify', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ip_address']);
    }

    /** @test */
    public function it_allows_optional_fields_to_be_null(): void
    {
        $payload = [
            'device_uuid' => $this->validPayload['device_uuid'],
            'challenge' => $this->validPayload['challenge'],
            'signature' => $this->validPayload['signature'],
        ];

        $this->partialMock(VerifySignatureAction::class, function ($mock) use ($payload) {
            $mock->shouldReceive('execute')
                ->once()
                ->with($payload)
                ->andReturn([
                    'pairing_code' => '123456',
                    'mqtt' => ['broker' => 'mqtt.example.com', 'port' => 1883],
                    'topics' => [],
                ]);
        });

        $response = $this->postJson('/api/devices/auth/verify', $payload);

        $response->assertStatus(200);
    }

    /** @test */
    public function it_accepts_lowercase_mac_address(): void
    {
        $payload = $this->validPayload;
        $payload['mac_address'] = 'aa:bb:cc:dd:ee:ff';

        $this->partialMock(VerifySignatureAction::class, function ($mock) {
            $mock->shouldReceive('execute')
                ->once()
                ->andReturn([
                    'pairing_code' => '123456',
                    'mqtt' => ['broker' => 'mqtt.example.com', 'port' => 1883],
                    'topics' => [],
                ]);
        });

        $response = $this->postJson('/api/devices/auth/verify', $payload);

        $response->assertStatus(200);
    }

    /** @test */
    public function it_accepts_ipv6_address(): void
    {
        $payload = $this->validPayload;
        $payload['ip_address'] = '2001:0db8:85a3:0000:0000:8a2e:0370:7334';

        $this->partialMock(VerifySignatureAction::class, function ($mock) {
            $mock->shouldReceive('execute')
                ->once()
                ->andReturn([
                    'pairing_code' => '123456',
                    'mqtt' => ['broker' => 'mqtt.example.com', 'port' => 1883],
                    'topics' => [],
                ]);
        });

        $response = $this->postJson('/api/devices/auth/verify', $payload);

        $response->assertStatus(200);
    }

    /** @test */
    public function it_responds_with_json_content_type(): void
    {
        $this->partialMock(VerifySignatureAction::class, function ($mock) {
            $mock->shouldReceive('execute')
                ->andReturn([
                    'pairing_code' => '123456',
                    'mqtt' => ['broker' => 'mqtt.example.com', 'port' => 1883],
                    'topics' => [],
                ]);
        });

        $response = $this->postJson('/api/devices/auth/verify', $this->validPayload);

        $response->assertHeader('Content-Type', 'application/json');
    }
}
