<?php

/**
 * IntegrationContract
 *
 * Repository contract for managing integration providers.
 * Integrations represent third-party OAuth providers like
 * YouTube, Twitter, GitHub, Shopify, and Stripe.
 *
 * @package App\Infrastructure\Repositories\Contracts
 *
 * @extends BaseRepositoryInterface<Integration>
 */

namespace App\Infrastructure\Repositories\Contracts;

use App\Models\Integration;

interface IntegrationContract extends BaseRepositoryInterface
{
    /**
     * Find an integration by slug.
     *
     * @param string $slug
     * @return Integration|null
     */
    public function findBySlug(string $slug): ?Integration;

    /**
     * Find an active integration by slug.
     *
     * @param string $slug
     * @return Integration|null
     */
    public function findActiveBySlug(string $slug): ?Integration;

    /**
     * Scope query to only active integrations.
     *
     * @return static
     */
    public function active(): static;
}
