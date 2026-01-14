<?php

namespace App\Actions\Device;

use App\Infrastructure\Repositories\Contracts\DeviceContract;
use App\Models\Device;
use App\Models\User;

class PairDeviceAction
{
    public function __construct(
        private readonly DeviceContract $deviceRepository,
    ) {
    }

    public function execute(string $pairingCode, User $user): ?Device
    {
        return $this->deviceRepository->pairWithUser($pairingCode, $user);
    }
}
