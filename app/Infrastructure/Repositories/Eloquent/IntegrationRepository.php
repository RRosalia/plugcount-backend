<?php

/**
 * IntegrationRepository
 *
 * Eloquent implementation of the IntegrationContract.
 * Manages third-party OAuth integration providers.
 *
 * @package App\Infrastructure\Repositories\Eloquent
 *
 * @extends BaseRepository<Integration>
 */

namespace App\Infrastructure\Repositories\Eloquent;

use App\Infrastructure\Repositories\Contracts\IntegrationContract;
use App\Models\Integration;

class IntegrationRepository extends BaseRepository implements IntegrationContract
{
    /**
     * Get the model class name.
     *
     * @return class-string<Integration>
     */
    public function model(): string
    {
        return Integration::class;
    }

    /**
     * Find an integration by slug.
     *
     * @param string $slug
     * @return Integration|null
     */
    public function findBySlug(string $slug): ?Integration
    {
        return $this->where('slug', $slug)->first();
    }

    /**
     * Find an active integration by slug.
     *
     * @param string $slug
     * @return Integration|null
     */
    public function findActiveBySlug(string $slug): ?Integration
    {
        return $this->active()
            ->where('slug', $slug)
            ->first();
    }

    /**
     * Scope query to only active integrations.
     *
     * @return static
     */
    public function active(): static
    {
        return $this->where('is_active', true);
    }
}
