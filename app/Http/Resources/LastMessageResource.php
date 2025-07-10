<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LastMessageResource extends JsonResource
{
    /**
     * Disable wrapping the resource in a data key
     */
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'success' => true,
            'channel' => $this->resource['channel'],
            'last_message_id' => $this->resource['last_message_id'],
            'from_cache' => $this->resource['from_cache'],
            'cache_age_seconds' => $this->resource['cache_age_seconds'],
            'timestamp' => now()->toISOString(),
            'deprecation' => [
                'warning' => 'This endpoint is deprecated',
                'use_instead' => '/api/v2/telegram/channels/{channel}/messages/last-id',
            ],
        ];
    }
}
