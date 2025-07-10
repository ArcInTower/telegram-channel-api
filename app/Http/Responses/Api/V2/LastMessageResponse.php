<?php

namespace App\Http\Responses\Api\V2;

use Illuminate\Http\JsonResponse;

class LastMessageResponse
{
    public function __construct(
        private string $channel,
        private int $lastMessageId,
        private bool $fromCache,
        private ?float $cacheAge,
    ) {}

    public function toResponse(): JsonResponse
    {
        return response()->json([
            'data' => [
                'type' => 'channel-message',
                'id' => $this->channel,
                'attributes' => [
                    'last_message_id' => $this->lastMessageId,
                    'cache' => [
                        'from_cache' => $this->fromCache,
                        'age_seconds' => $this->cacheAge,
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
        ]);
    }
}
