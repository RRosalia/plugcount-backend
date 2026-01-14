<?php

/**
 * DeviceKeySeeder
 *
 * Seeds test device keys for development.
 * In local/dev environment, these devices use simulated signatures.
 *
 * @package Database\Seeders
 */

namespace Database\Seeders;

use App\Models\DeviceKey;
use Illuminate\Database\Seeder;

class DeviceKeySeeder extends Seeder
{
    /**
     * Test device UUIDs for development.
     *
     * @var array<string>
     */
    private const TEST_DEVICES = [
        '550e8400-e29b-41d4-a716-446655440001',
        '550e8400-e29b-41d4-a716-446655440002',
        '550e8400-e29b-41d4-a716-446655440003',
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        foreach (self::TEST_DEVICES as $uuid) {
            DeviceKey::firstOrCreate(
                ['device_uuid' => $uuid],
                [
                    'public_key' => $this->generatePlaceholderKey($uuid),
                    'is_simulated' => true,
                ]
            );
        }

        $this->command->info('Created ' . count(self::TEST_DEVICES) . ' test device keys.');
    }

    /**
     * Generate a placeholder public key for testing.
     *
     * In local/dev, simulated signatures don't use the public key,
     * so we just store a placeholder.
     *
     * @param string $uuid
     * @return string
     */
    private function generatePlaceholderKey(string $uuid): string
    {
        return "-----BEGIN PUBLIC KEY-----\nPLACEHOLDER_FOR_DEV_DEVICE_{$uuid}\n-----END PUBLIC KEY-----";
    }
}
