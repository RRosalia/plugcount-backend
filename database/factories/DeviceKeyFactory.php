<?php

/**
 * DeviceKeyFactory
 *
 * Factory for creating DeviceKey model instances for testing.
 *
 * @package Database\Factories
 */

namespace Database\Factories;

use App\Models\DeviceKey;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeviceKey>
 */
class DeviceKeyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<DeviceKey>
     */
    protected $model = DeviceKey::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'device_uuid' => $this->faker->uuid(),
            'public_key' => $this->generatePlaceholderPublicKey(),
            'activated_at' => null,
        ];
    }

    /**
     * Indicate that the device has been activated.
     *
     * @return static
     */
    public function activated(): static
    {
        return $this->state(fn (array $attributes) => [
            'activated_at' => now(),
        ]);
    }

    /**
     * Generate a placeholder public key for testing.
     *
     * @return string
     */
    private function generatePlaceholderPublicKey(): string
    {
        return "-----BEGIN PUBLIC KEY-----\nTEST_PUBLIC_KEY_" . $this->faker->sha256() . "\n-----END PUBLIC KEY-----";
    }
}
