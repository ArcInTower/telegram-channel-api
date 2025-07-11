<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Request;

class ComparisonResource extends BaseResource
{
    /**
     * Transform the resource into an array following JSON:API 1.1 spec.
     */
    public function toArray(Request $request): array
    {
        $comparisons = $this->resource['comparisons'];
        $errors = $this->resource['errors'] ?? [];

        // Calculate aggregated cache meta
        $cacheMeta = $this->calculateAggregatedCacheMeta($comparisons);

        return [
            'data' => [
                'type' => 'channel-comparison',
                'id' => implode('-', array_column($comparisons, 'channel')),
                'attributes' => [
                    'comparison' => $this->formatComparison($comparisons),
                    'summary' => $this->calculateSummary($comparisons),
                    'errors' => $errors,
                ],
            ],
            'meta' => $this->buildMetaWithCache($cacheMeta),
            'jsonapi' => [
                'version' => '1.1',
            ],
        ];
    }

    private function formatComparison(array $comparisons): array
    {
        $formatted = [];

        foreach ($comparisons as $comparison) {
            $stats = $comparison['statistics'];

            $formatted[] = [
                'channel' => $comparison['channel'],
                'total_messages' => $stats['summary']['total_messages'] ?? 0,
                'active_users' => $stats['summary']['active_users'] ?? 0,
                'average_message_length' => $stats['summary']['average_message_length'] ?? 0,
                'reply_rate' => $stats['summary']['reply_rate'] ?? 0,
                'peak_hour' => $stats['peak_activity']['hour'] ?? 'N/A',
                'peak_day' => $stats['peak_activity']['weekday'] ?? 'N/A',
                'period_days' => $stats['period']['days'] ?? $this->resource['days'],
                // Include channel info if available
                'total_participants' => $stats['channel_info']['total_participants'] ?? null,
                'channel_title' => $stats['channel_info']['title'] ?? null,
                'channel_type' => $stats['channel_info']['type'] ?? null,
                'approx_total_messages' => $stats['channel_info']['approx_total_messages'] ?? null,
            ];
        }

        return $formatted;
    }

    private function calculateSummary(array $comparisons): array
    {
        $totals = [
            'total_messages' => 0,
            'total_users' => 0,
            'channels_analyzed' => count($comparisons),
        ];

        $mostActive = null;
        $maxMessages = 0;

        foreach ($comparisons as $comparison) {
            $messages = $comparison['statistics']['summary']['total_messages'] ?? 0;
            $users = $comparison['statistics']['summary']['active_users'] ?? 0;

            $totals['total_messages'] += $messages;
            $totals['total_users'] += $users;

            if ($messages > $maxMessages) {
                $maxMessages = $messages;
                $mostActive = $comparison['channel'];
            }
        }

        $totals['most_active_channel'] = $mostActive;
        $totals['average_messages_per_channel'] = count($comparisons) > 0
            ? round($totals['total_messages'] / count($comparisons))
            : 0;
        $totals['total_unique_users'] = $totals['total_users']; // For clarity in the API response

        return $totals;
    }

    private function calculateAggregatedCacheMeta(array $comparisons): array
    {
        $fromCache = true;
        $oldestCacheTime = null;
        $cacheTtl = null;

        foreach ($comparisons as $comparison) {
            $meta = $comparison['cache_meta'] ?? [];

            // If any channel is not from cache, the whole comparison is not from cache
            if (!($meta['from_cache'] ?? false)) {
                $fromCache = false;
            }

            // Find the oldest cache time
            if (isset($meta['cached_at'])) {
                if ($oldestCacheTime === null || $meta['cached_at'] < $oldestCacheTime) {
                    $oldestCacheTime = $meta['cached_at'];
                }
            }

            // Use the smallest TTL
            if (isset($meta['cache_ttl'])) {
                if ($cacheTtl === null || $meta['cache_ttl'] < $cacheTtl) {
                    $cacheTtl = $meta['cache_ttl'];
                }
            }
        }

        return [
            'from_cache' => $fromCache,
            'cached_at' => $fromCache ? $oldestCacheTime : null,
            'cache_ttl' => $cacheTtl,
        ];
    }

    private function buildMetaWithCache(array $cacheMeta): array
    {
        // Temporarily set resource to cache meta for extraction
        $this->resource = ['_cache_meta' => $cacheMeta];

        return $this->buildMeta([
            'period_days' => $this->resource['days'] ?? 7,
            'channels_compared' => count($this->resource['comparisons'] ?? []),
        ]);
    }
}
