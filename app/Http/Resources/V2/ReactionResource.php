<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Handle both channel-wide and single message reactions
        if (isset($this->resource['analyzed_messages'])) {
            // Channel-wide reactions
            return [
                'data' => [
                    'channel' => $this->resource['channel'],
                    'analyzed_messages' => $this->resource['analyzed_messages'],
                    'messages_with_reactions' => $this->resource['messages_with_reactions'],
                    'total_reactions' => $this->resource['total_reactions'],
                    'average_reactions_per_message' => $this->resource['average_reactions_per_message'],
                    'engagement_rate' => $this->resource['engagement_rate'],
                    'reaction_types' => $this->resource['reaction_types'] ?? []
                ],
                'meta' => [
                    'api_version' => config('app.api_version', '2.0.0'),
                    'cache' => $this->resource['_cache_meta'] ?? [
                        'from_cache' => false,
                        'cached_at' => null,
                        'expires_at' => null
                    ]
                ]
            ];
        } else {
            // Single message reactions
            return [
                'data' => [
                    'channel' => $this->resource['channel'],
                    'message_id' => $this->resource['message_id'],
                    'message_preview' => $this->resource['message_preview'] ?? null,
                    'total_reactions' => $this->resource['total_reactions'] ?? 0,
                    'reactions' => $this->resource['reactions'] ?? [],
                    'message_date' => $this->resource['message_date'] ?? null
                ],
                'meta' => [
                    'api_version' => config('app.api_version', '2.0.0'),
                    'cache' => $this->resource['_cache_meta'] ?? [
                        'from_cache' => false,
                        'cached_at' => null,
                        'expires_at' => null
                    ]
                ]
            ];
        }
    }
}