<?php

namespace App\Actions\Device;

use App\Infrastructure\Repositories\Contracts\DeviceContract;
use App\Models\Device;
use Illuminate\Support\Str;

class RegisterDeviceAction
{
    public function __construct(
        private readonly DeviceContract $deviceRepository,
    ) {
    }

    public function execute(array $data): array
    {
        $device = $this->deviceRepository->findByUuid($data['device_id'] ?? '');

        if (!$device) {
            $device = $this->deviceRepository->create([
                'uuid' => $data['device_id'] ?? Str::uuid()->toString(),
                'mac_address' => $data['mac_address'] ?? null,
                'ip_address' => $data['ip_address'] ?? null,
                'firmware_version' => $data['firmware_version'] ?? null,
                'status' => 'pairing',
            ]);
        } else {
            $device = $this->deviceRepository->update($device, [
                'mac_address' => $data['mac_address'] ?? $device->mac_address,
                'ip_address' => $data['ip_address'] ?? $device->ip_address,
                'firmware_version' => $data['firmware_version'] ?? $device->firmware_version,
            ]);
        }

        $pairingCode = $this->deviceRepository->generatePairingCode($device);

        return [
            'device' => $device,
            'pairing_code' => $pairingCode,
            'mqtt' => [
                'broker' => config('mqtt-client.connections.default.host'),
                'port' => config('mqtt-client.connections.default.port'),
            ],
            'topics' => [
                'integration' => "devices/{$device->uuid}/integration",
                'config' => "devices/{$device->uuid}/config",
                'command' => "devices/{$device->uuid}/command",
                'status' => "devices/{$device->uuid}/status",
                'heartbeat' => "devices/{$device->uuid}/heartbeat",
            ],
        ];
    }
}
