<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Resources\Json\JsonResource;

abstract class BaseResource extends JsonResource
{
    /**
     * Extract cache metadata if present
     */
    protected function extractCacheMeta(): array
    {
        $data = $this->resource;

        if (is_array($data) && isset($data['_cache_meta'])) {
            $cacheMeta = $data['_cache_meta'];
            $meta = [];

            // Always show from_cache status
            $meta['from_cache'] = $cacheMeta['from_cache'] ?? false;

            // Show cached_at when from cache, null otherwise
            if ($meta['from_cache'] && !empty($cacheMeta['cached_at'])) {
                $meta['cached_at'] = $cacheMeta['cached_at'];
            } else {
                $meta['cached_at'] = null;
            }

            if (isset($cacheMeta['cache_ttl'])) {
                $meta['cache_ttl_seconds'] = $cacheMeta['cache_ttl'];
            }

            return $meta;
        }

        return [
            'from_cache' => false,
            'cached_at' => null,
        ];
    }

    /**
     * Build the meta section with cache info
     */
    protected function buildMeta(array $additionalMeta = []): array
    {
        $baseMeta = [
            'timestamp' => now()->toISOString(),
            'api_version' => 'v2',
        ];

        return array_merge($baseMeta, $this->extractCacheMeta(), $additionalMeta);
    }

    /**
     * Extract data from potentially wrapped response
     */
    protected function extractData(?string $key = null): mixed
    {
        $data = $this->resource;

        // If data is wrapped with cache metadata, extract the actual data
        if (is_array($data) && isset($data['data'])) {
            $actualData = $data['data'];
        } else {
            $actualData = $data;
        }

        // If a specific key is requested, return that
        if ($key !== null && is_array($actualData)) {
            return $actualData[$key] ?? null;
        }

        return $actualData;
    }
}
