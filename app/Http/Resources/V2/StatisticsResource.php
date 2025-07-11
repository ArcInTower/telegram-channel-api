<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Request;

class StatisticsResource extends BaseResource
{
    /**
     * Transform the resource into an array following JSON:API 1.1 spec.
     */
    public function toArray(Request $request): array
    {
        // Store the original resource for cache meta extraction
        $originalResource = $this->resource;

        return [
            'data' => [
                'type' => 'channel-statistics',
                'id' => $originalResource['channel'],
                'attributes' => [
                    'channel' => $originalResource['channel'],
                    'statistics' => $this->extractStatisticsData($originalResource['stats']),
                ],
            ],
            'meta' => $this->buildMetaFromStats($originalResource['stats']),
            'jsonapi' => [
                'version' => '1.1',
            ],
        ];
    }

    /**
     * Extract statistics data from the potentially wrapped response
     */
    private function extractStatisticsData($stats): ?array
    {
        // If stats is wrapped with cache metadata, extract the actual data
        if (is_array($stats) && isset($stats['data'])) {
            return $stats['data'];
        }

        return $stats;
    }

    /**
     * Build meta from stats which contains cache info
     */
    private function buildMetaFromStats($stats): array
    {
        // Temporarily set resource to stats for cache meta extraction
        $this->resource = $stats;
        $meta = $this->buildMeta();

        return $meta;
    }
}
