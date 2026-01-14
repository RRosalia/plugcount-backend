<?php

/**
 * SyncDeviceIntegrationsJob
 *
 * Scheduled job that syncs all active device integrations.
 * Fetches latest metrics from providers and publishes to MQTT.
 *
 * @package App\Jobs
 */

namespace App\Jobs;

use App\Actions\DeviceIntegration\SyncMetricAction;
use App\Infrastructure\Repositories\Eloquent\PublicDeviceIntegrationRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncDeviceIntegrationsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     *
     * @param PublicDeviceIntegrationRepository $repository
     * @param SyncMetricAction $syncAction
     * @return void
     */
    public function handle(
        PublicDeviceIntegrationRepository $repository,
        SyncMetricAction $syncAction
    ): void {
        $integrations = $repository->getPendingSync();

        Log::info("Syncing {$integrations->count()} device integrations");

        foreach ($integrations as $integration) {
            try {
                $changed = $syncAction->execute($integration);

                if ($changed) {
                    Log::debug("Metric updated for device integration", [
                        'device_integration_id' => $integration->id,
                        'new_value' => $integration->fresh()->last_value,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error("Failed to sync device integration", [
                    'device_integration_id' => $integration->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
