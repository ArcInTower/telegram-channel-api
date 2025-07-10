<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ErrorResource extends JsonResource
{
    private int $status;

    public function __construct($resource, int $status = 500)
    {
        parent::__construct($resource);
        $this->status = $status;
    }

    /**
     * Transform the resource into an array following JSON:API 1.1 spec.
     */
    public function toArray(Request $request): array
    {
        return [
            'errors' => [
                [
                    'status' => (string) $this->status,
                    'title' => $this->status === 404 ? 'Not Found' : 'Internal Server Error',
                    'detail' => $this->resource,
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

    public function withResponse($request, $response)
    {
        $response->setStatusCode($this->status);
    }
}
