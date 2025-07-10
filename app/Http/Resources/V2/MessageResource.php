<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    /**
     * Transform the resource into an array following JSON:API 1.1 spec.
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => [
                'type' => 'channel-message',
                'id' => $this->resource['channel'],
                'attributes' => [
                    'last_message_id' => $this->resource['last_message_id'],
                    'cache' => [
                        'from_cache' => $this->resource['from_cache'],
                        'age_seconds' => $this->resource['cache_age_seconds'],
                    ],
                ],
            ],
            'meta' => [
                'timestamp' => now()->toISOString(),
                'api_version' => 'v2',
            ],
            'jsonapi' => [
                'version' => '1.1',
            ],
        ];
    }
}
