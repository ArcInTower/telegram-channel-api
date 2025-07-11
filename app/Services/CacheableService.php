<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

abstract class CacheableService
{
    /**
     * Get data with cache support
     *
     * @param  string  $cacheKey  The cache key
     * @param  int  $cacheTtl  Cache TTL in seconds
     * @param  callable  $dataFetcher  Function that fetches fresh data
     *
     * @return array Always returns array with 'data' and '_cache_meta'
     */
    protected function getWithCache(string $cacheKey, int $cacheTtl, callable $dataFetcher): array
    {
        // Try to get from cache first
        $cachedData = Cache::get($cacheKey);
        $cacheMetaKey = $cacheKey . ':meta';
        $cacheMeta = Cache::get($cacheMetaKey);

        if ($cachedData !== null) {
            Log::info("Cache hit for key: {$cacheKey}");

            // If metadata doesn't exist (old cache), create it now
            if ($cacheMeta === null) {
                $cacheMeta = ['cached_at' => now()->toISOString()];
                Cache::put($cacheMetaKey, $cacheMeta, $cacheTtl);
            }

            // Return cached data with metadata
            return [
                'data' => $cachedData,
                '_cache_meta' => [
                    'cached_at' => $cacheMeta['cached_at'],
                    'from_cache' => true,
                    'cache_ttl' => $cacheTtl,
                ],
            ];
        }

        Log::info("Cache miss for key: {$cacheKey}, fetching fresh data");

        // Fetch fresh data
        try {
            $freshData = $dataFetcher();

            // Cache the data
            if ($freshData !== null || $this->shouldCacheNull()) {
                Cache::put($cacheKey, $freshData, $cacheTtl);
                Cache::put($cacheMetaKey, ['cached_at' => now()->toISOString()], $cacheTtl);
                Log::info("Cached data for key: {$cacheKey} for {$cacheTtl} seconds");
            }

            // Return fresh data without from_cache flag
            return [
                'data' => $freshData,
                '_cache_meta' => [
                    'cached_at' => null,
                    'from_cache' => false,
                    'cache_ttl' => $cacheTtl,
                ],
            ];

        } catch (\Exception $e) {
            Log::error("Error fetching data for cache key {$cacheKey}: " . $e->getMessage());

            // Cache null result to prevent repeated failed attempts
            if ($this->shouldCacheErrors()) {
                $errorTtl = min($cacheTtl, 300); // Cache errors for max 5 minutes
                Cache::put($cacheKey, null, $errorTtl);
                Cache::put($cacheMetaKey, ['cached_at' => now()->toISOString()], $errorTtl);
            }

            throw $e;
        }
    }

    /**
     * Clear cache for a specific key
     */
    protected function clearCache(string $cacheKey): void
    {
        Cache::forget($cacheKey);
        Cache::forget($cacheKey . ':meta');
        Log::info("Cleared cache for key: {$cacheKey}");
    }

    /**
     * Whether to cache null results (override in child classes)
     */
    protected function shouldCacheNull(): bool
    {
        return true;
    }

    /**
     * Whether to cache error results (override in child classes)
     */
    protected function shouldCacheErrors(): bool
    {
        return true;
    }
}
