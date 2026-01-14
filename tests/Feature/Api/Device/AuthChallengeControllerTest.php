<?php

/**
 * AuthChallengeControllerTest
 *
 * Feature tests for the AuthChallengeController.
 * Tests HTTP layer including routes, request validation, and response formatting.
 * Action classes are mocked as they are tested separately.
 *
 * @package Tests\Feature\Api\Device
 */

namespace Tests\Feature\Api\Device;

use App\Actions\Device\GenerateChallengeAction;
use App\Exceptions\Device\DeviceNotRegisteredException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthChallengeControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_returns_challenge_for_valid_device_uuid(): void
    {
        $deviceUuid = '550e8400-e29b-41d4-a716-446655440000';
        $expectedChallenge = bin2hex(random_bytes(32));

        $this->partialMock(GenerateChallengeAction::class, function ($mock) use ($deviceUuid, $expectedChallenge) {
            $mock->shouldReceive('execute')
                ->once()
                ->with($deviceUuid)
                ->andReturn([
                    'challenge' => $expectedChallenge,
                    'expires_in' => 60,
                ]);
        });

        $response = $this->postJson('/api/devices/auth/challenge', [
            'device_uuid' => $deviceUuid,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'challenge',
                    'expires_in',
                ],
            ])
            ->assertJson([
                'data' => [
                    'challenge' => $expectedChallenge,
                    'expires_in' => 60,
                ],
            ]);
    }

    /** @test */
    public function it_returns_404_for_unregistered_device(): void
    {
        $deviceUuid = '550e8400-e29b-41d4-a716-446655440000';

        $this->partialMock(GenerateChallengeAction::class, function ($mock) use ($deviceUuid) {
            $mock->shouldReceive('execute')
                ->once()
                ->with($deviceUuid)
                ->andThrow(new DeviceNotRegisteredException($deviceUuid));
        });

        $response = $this->postJson('/api/devices/auth/challenge', [
            'device_uuid' => $deviceUuid,
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'error' => [
                    'message' => 'Device not registered',
                ],
            ]);
    }

    /** @test */
    public function it_requires_device_uuid(): void
    {
        $response = $this->postJson('/api/devices/auth/challenge', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['device_uuid']);
    }

    /** @test */
    public function it_validates_device_uuid_format(): void
    {
        $response = $this->postJson('/api/devices/auth/challenge', [
            'device_uuid' => 'not-a-valid-uuid',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['device_uuid']);
    }

    /** @test */
    public function it_rejects_empty_device_uuid(): void
    {
        $response = $this->postJson('/api/devices/auth/challenge', [
            'device_uuid' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['device_uuid']);
    }

    /** @test */
    public function it_rejects_non_string_device_uuid(): void
    {
        $response = $this->postJson('/api/devices/auth/challenge', [
            'device_uuid' => 12345,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['device_uuid']);
    }

    /** @test */
    public function it_responds_with_json_content_type(): void
    {
        $deviceUuid = '550e8400-e29b-41d4-a716-446655440000';

        $this->partialMock(GenerateChallengeAction::class, function ($mock) {
            $mock->shouldReceive('execute')
                ->andReturn([
                    'challenge' => bin2hex(random_bytes(32)),
                    'expires_in' => 60,
                ]);
        });

        $response = $this->postJson('/api/devices/auth/challenge', [
            'device_uuid' => $deviceUuid,
        ]);

        $response->assertHeader('Content-Type', 'application/json');
    }
}
