<?php

/**
 * PublicDeviceIntegrationRepository
 *
 * Repository for active device integrations only.
 * All queries are automatically scoped to is_active = true.
 *
 * @package App\Infrastructure\Repositories\Eloquent
 *
 * @extends DeviceIntegrationRepository
 */

namespace App\Infrastructure\Repositories\Eloquent;

use Illuminate\Contracts\Database\Eloquent\Builder;

class PublicDeviceIntegrationRepository extends DeviceIntegrationRepository
{
    /**
     * Create a new query builder instance scoped to active records.
     *
     * @return Builder
     */
    public function newQuery(): Builder
    {
        return parent::newQuery()->where('is_active', true);
    }
}
