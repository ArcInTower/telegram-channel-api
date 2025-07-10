<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChannelInfoResource extends JsonResource
{
    /**
     * Transform the resource into an array following JSON:API 1.1 spec.
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => [
                'type' => 'channel-info',
                'id' => $this->resource['channel'],
                'attributes' => [
                    'username' => $this->resource['username'],
                    'title' => $this->resource['title'],
                    'participant_count' => $this->resource['participants_count'] ?? null,
                    'last_message_id' => $this->resource['last_message_id'],
                    'cache' => [
                        'from_cache' => false,
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
