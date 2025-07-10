<?php

namespace App\Http\Responses\Api\V2;

use Illuminate\Http\JsonResponse;

class ChannelInfoResponse
{
    public function __construct(
        private string $channel,
        private array $info,
    ) {}

    public function toResponse(): JsonResponse
    {
        return response()->json([
            'data' => [
                'type' => 'channel-info',
                'id' => $this->channel,
                'attributes' => $this->info,
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
