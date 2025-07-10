<?php

namespace App\Http\Responses\Api\V2;

use Illuminate\Http\JsonResponse;

class StatisticsResponse
{
    public function __construct(
        private string $channel,
        private array $statistics,
        private int $days,
    ) {}

    public function toResponse(): JsonResponse
    {
        return response()->json([
            'data' => [
                'type' => 'channel-statistics',
                'id' => $this->channel,
                'attributes' => [
                    'statistics' => $this->statistics,
                ],
            ],
            'meta' => [
                'period_days' => $this->days,
                'timestamp' => now()->toISOString(),
                'api_version' => 'v2',
            ],
            'jsonapi' => [
                'version' => '1.1',
            ],
        ]);
    }
}
