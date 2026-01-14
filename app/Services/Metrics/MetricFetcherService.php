<?php

/**
 * MetricFetcherService
 *
 * Fetches metrics from third-party providers using their APIs.
 * Supports YouTube, GitHub, Twitter, Shopify, and Stripe.
 *
 * @package App\Services\Metrics
 */

namespace App\Services\Metrics;

use App\Models\UserIntegration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetricFetcherService
{
    /**
     * Fetch a metric value from the provider.
     *
     * @param UserIntegration $userIntegration
     * @param string $metricType
     * @return string|null The metric value or null on failure
     */
    public function fetch(UserIntegration $userIntegration, string $metricType): ?string
    {
        $provider = $userIntegration->integration->slug;

        try {
            return match ($provider) {
                'google' => $this->fetchYouTubeMetric($userIntegration, $metricType),
                'github' => $this->fetchGitHubMetric($userIntegration, $metricType),
                'twitter' => $this->fetchTwitterMetric($userIntegration, $metricType),
                'shopify' => $this->fetchShopifyMetric($userIntegration, $metricType),
                'stripe' => $this->fetchStripeMetric($userIntegration, $metricType),
                default => null,
            };
        } catch (\Exception $e) {
            Log::error("Failed to fetch metric", [
                'provider' => $provider,
                'metric_type' => $metricType,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Fetch YouTube metrics (subscribers, views, videos).
     *
     * @param UserIntegration $userIntegration
     * @param string $metricType
     * @return string|null
     */
    private function fetchYouTubeMetric(UserIntegration $userIntegration, string $metricType): ?string
    {
        $response = Http::withToken($userIntegration->access_token)
            ->get('https://www.googleapis.com/youtube/v3/channels', [
                'part' => 'statistics',
                'mine' => 'true',
            ]);

        if (! $response->successful()) {
            return null;
        }

        $statistics = $response->json('items.0.statistics');

        return match ($metricType) {
            'subscribers' => $statistics['subscriberCount'] ?? null,
            'views' => $statistics['viewCount'] ?? null,
            'videos' => $statistics['videoCount'] ?? null,
            default => null,
        };
    }

    /**
     * Fetch GitHub metrics (stars, forks, followers).
     *
     * @param UserIntegration $userIntegration
     * @param string $metricType
     * @return string|null
     */
    private function fetchGitHubMetric(UserIntegration $userIntegration, string $metricType): ?string
    {
        if ($metricType === 'followers') {
            $response = Http::withToken($userIntegration->access_token)
                ->get('https://api.github.com/user');

            if (! $response->successful()) {
                return null;
            }

            return (string) $response->json('followers');
        }

        if (in_array($metricType, ['stars', 'forks'])) {
            $response = Http::withToken($userIntegration->access_token)
                ->get('https://api.github.com/user/repos', [
                    'per_page' => 100,
                    'sort' => 'updated',
                ]);

            if (! $response->successful()) {
                return null;
            }

            $repos = $response->json();
            $field = $metricType === 'stars' ? 'stargazers_count' : 'forks_count';

            $total = collect($repos)->sum($field);

            return (string) $total;
        }

        return null;
    }

    /**
     * Fetch Twitter metrics (followers, tweets, likes).
     *
     * @param UserIntegration $userIntegration
     * @param string $metricType
     * @return string|null
     */
    private function fetchTwitterMetric(UserIntegration $userIntegration, string $metricType): ?string
    {
        $userId = $userIntegration->external_user_id;

        $response = Http::withToken($userIntegration->access_token)
            ->get("https://api.twitter.com/2/users/{$userId}", [
                'user.fields' => 'public_metrics',
            ]);

        if (! $response->successful()) {
            return null;
        }

        $metrics = $response->json('data.public_metrics');

        return match ($metricType) {
            'followers' => (string) ($metrics['followers_count'] ?? null),
            'tweets' => (string) ($metrics['tweet_count'] ?? null),
            'likes' => (string) ($metrics['like_count'] ?? null),
            default => null,
        };
    }

    /**
     * Fetch Shopify metrics (orders, revenue, customers).
     *
     * @param UserIntegration $userIntegration
     * @param string $metricType
     * @return string|null
     */
    private function fetchShopifyMetric(UserIntegration $userIntegration, string $metricType): ?string
    {
        $shopDomain = $userIntegration->metadata['shop_domain'] ?? null;

        if (! $shopDomain) {
            return null;
        }

        $baseUrl = "https://{$shopDomain}/admin/api/2024-01";

        return match ($metricType) {
            'orders' => $this->fetchShopifyOrderCount($userIntegration, $baseUrl),
            'customers' => $this->fetchShopifyCustomerCount($userIntegration, $baseUrl),
            'revenue' => $this->fetchShopifyRevenue($userIntegration, $baseUrl),
            default => null,
        };
    }

    /**
     * Fetch Shopify order count.
     */
    private function fetchShopifyOrderCount(UserIntegration $userIntegration, string $baseUrl): ?string
    {
        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $userIntegration->access_token,
        ])->get("{$baseUrl}/orders/count.json");

        return $response->successful() ? (string) $response->json('count') : null;
    }

    /**
     * Fetch Shopify customer count.
     */
    private function fetchShopifyCustomerCount(UserIntegration $userIntegration, string $baseUrl): ?string
    {
        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $userIntegration->access_token,
        ])->get("{$baseUrl}/customers/count.json");

        return $response->successful() ? (string) $response->json('count') : null;
    }

    /**
     * Fetch Shopify total revenue.
     */
    private function fetchShopifyRevenue(UserIntegration $userIntegration, string $baseUrl): ?string
    {
        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $userIntegration->access_token,
        ])->get("{$baseUrl}/orders.json", [
            'status' => 'any',
            'financial_status' => 'paid',
            'fields' => 'total_price',
        ]);

        if (! $response->successful()) {
            return null;
        }

        $orders = $response->json('orders');
        $total = collect($orders)->sum('total_price');

        return number_format($total, 2);
    }

    /**
     * Fetch Stripe metrics (revenue, customers, subscriptions).
     *
     * @param UserIntegration $userIntegration
     * @param string $metricType
     * @return string|null
     */
    private function fetchStripeMetric(UserIntegration $userIntegration, string $metricType): ?string
    {
        $stripeUserId = $userIntegration->metadata['stripe_user_id'] ?? null;

        return match ($metricType) {
            'customers' => $this->fetchStripeCustomerCount($userIntegration),
            'subscriptions' => $this->fetchStripeSubscriptionCount($userIntegration),
            'revenue' => $this->fetchStripeRevenue($userIntegration),
            default => null,
        };
    }

    /**
     * Fetch Stripe customer count.
     */
    private function fetchStripeCustomerCount(UserIntegration $userIntegration): ?string
    {
        $response = Http::withBasicAuth($userIntegration->access_token, '')
            ->get('https://api.stripe.com/v1/customers', [
                'limit' => 1,
            ]);

        if (! $response->successful()) {
            return null;
        }

        // Stripe returns total_count in list responses with expand
        $response = Http::withBasicAuth($userIntegration->access_token, '')
            ->asForm()
            ->get('https://api.stripe.com/v1/customers/search', [
                'query' => 'created>0',
                'limit' => 1,
            ]);

        return $response->successful() ? (string) $response->json('total_count', 0) : null;
    }

    /**
     * Fetch Stripe active subscription count.
     */
    private function fetchStripeSubscriptionCount(UserIntegration $userIntegration): ?string
    {
        $response = Http::withBasicAuth($userIntegration->access_token, '')
            ->get('https://api.stripe.com/v1/subscriptions', [
                'status' => 'active',
                'limit' => 100,
            ]);

        if (! $response->successful()) {
            return null;
        }

        return (string) count($response->json('data', []));
    }

    /**
     * Fetch Stripe total revenue (balance).
     */
    private function fetchStripeRevenue(UserIntegration $userIntegration): ?string
    {
        $response = Http::withBasicAuth($userIntegration->access_token, '')
            ->get('https://api.stripe.com/v1/balance');

        if (! $response->successful()) {
            return null;
        }

        $available = $response->json('available', []);
        $total = collect($available)->sum('amount');

        // Convert from cents
        return number_format($total / 100, 2);
    }
}
