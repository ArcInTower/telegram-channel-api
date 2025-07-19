<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PollResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = $this->resource['data'];
        $cacheMeta = $this->resource['_cache_meta'] ?? null;

        $response = [
            'data' => $data,
            'meta' => [
                'api_version' => '2.0.0',
            ],
        ];

        // Add cache metadata to meta
        if ($cacheMeta) {
            $response['meta']['cache'] = [
                'from_cache' => $cacheMeta['from_cache'] ?? false,
                'cached_at' => $cacheMeta['cached_at'],
                'expires_at' => $cacheMeta['cached_at'] && $cacheMeta['cache_ttl_seconds']
                    ? \Carbon\Carbon::parse($cacheMeta['cached_at'])
                        ->addSeconds($cacheMeta['cache_ttl_seconds'])
                        ->toIso8601String()
                    : null,
            ];
        }

        return $response;
    }
}