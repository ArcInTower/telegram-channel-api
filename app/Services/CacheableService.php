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
     * @return mixed Returns the cached or fresh data directly
     */
    protected function getWithCache(string $cacheKey, int $cacheTtl, callable $dataFetcher): mixed
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

            // Return only the cached data
            return $cachedData;
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

            // Return only the fresh data
            return $freshData;

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

    /**
     * Get cache metadata for a specific key
     */
    protected function getCacheMetadata(string $cacheKey, int $cacheTtl): array
    {
        $cacheMetaKey = $cacheKey . ':meta';
        $cacheMeta = Cache::get($cacheMetaKey);
        $cachedData = Cache::get($cacheKey);

        if ($cachedData !== null && $cacheMeta !== null) {
            return [
                'from_cache' => true,
                'cached_at' => $cacheMeta['cached_at'],
                'cache_ttl_seconds' => $cacheTtl,
            ];
        }

        return [
            'from_cache' => false,
            'cached_at' => null,
            'cache_ttl_seconds' => $cacheTtl,
        ];
    }
}
