<?php

/**
 * GenerateChallengeActionTest
 *
 * Unit tests for the GenerateChallengeAction class.
 * Tests challenge generation flow with real repositories and services.
 *
 * @package Tests\Unit\Actions\Device
 */

namespace Tests\Unit\Actions\Device;

use App\Actions\Device\GenerateChallengeAction;
use App\Exceptions\Device\DeviceNotRegisteredException;
use App\Infrastructure\Repositories\Contracts\DeviceKeyContract;
use App\Models\DeviceKey;
use App\Services\Crypto\DeviceSignatureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class GenerateChallengeActionTest extends TestCase
{
    use RefreshDatabase;

    private GenerateChallengeAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->action = app(GenerateChallengeAction::class);
    }

    /** @test */
    public function it_generates_challenge_for_registered_device(): void
    {
        $deviceKey = DeviceKey::factory()->create();

        $result = $this->action->execute($deviceKey->device_uuid);

        $this->assertArrayHasKey('challenge', $result);
        $this->assertArrayHasKey('expires_in', $result);
        $this->assertEquals(64, strlen($result['challenge']));
        $this->assertEquals(60, $result['expires_in']);
    }

    /** @test */
    public function it_throws_exception_for_unregistered_device(): void
    {
        $this->expectException(DeviceNotRegisteredException::class);

        $this->action->execute('non-existent-uuid');
    }

    /** @test */
    public function it_stores_challenge_in_cache(): void
    {
        $deviceKey = DeviceKey::factory()->create();

        $result = $this->action->execute($deviceKey->device_uuid);

        $storedChallenge = Cache::store('challenges')->get("device_challenge:{$deviceKey->device_uuid}");

        $this->assertEquals($result['challenge'], $storedChallenge);
    }

    /** @test */
    public function it_generates_unique_challenges_for_same_device(): void
    {
        $deviceKey = DeviceKey::factory()->create();

        $result1 = $this->action->execute($deviceKey->device_uuid);
        $result2 = $this->action->execute($deviceKey->device_uuid);

        $this->assertNotEquals($result1['challenge'], $result2['challenge']);
    }

    /** @test */
    public function it_generates_unique_challenges_for_different_devices(): void
    {
        $deviceKey1 = DeviceKey::factory()->create();
        $deviceKey2 = DeviceKey::factory()->create();

        $result1 = $this->action->execute($deviceKey1->device_uuid);
        $result2 = $this->action->execute($deviceKey2->device_uuid);

        $this->assertNotEquals($result1['challenge'], $result2['challenge']);
    }

    /** @test */
    public function challenge_is_hex_encoded(): void
    {
        $deviceKey = DeviceKey::factory()->create();

        $result = $this->action->execute($deviceKey->device_uuid);

        $this->assertTrue(ctype_xdigit($result['challenge']));
    }
}
