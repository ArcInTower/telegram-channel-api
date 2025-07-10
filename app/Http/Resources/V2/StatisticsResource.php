<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StatisticsResource extends JsonResource
{
    /**
     * Transform the resource into an array following JSON:API 1.1 spec.
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => [
                'type' => 'channel-statistics',
                'id' => $this->resource['channel'],
                'attributes' => [
                    'channel' => $this->resource['channel'],
                    'statistics' => $this->resource['stats'],
                ],
            ],
            'meta' => [
                'timestamp' => now()->toISOString(),
                'api_version' => 'v2',
                'period_days' => $this->resource['days'],
            ],
            'jsonapi' => [
                'version' => '1.1',
            ],
        ];
    }
}
