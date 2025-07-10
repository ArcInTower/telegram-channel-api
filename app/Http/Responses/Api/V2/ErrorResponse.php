<?php

namespace App\Http\Responses\Api\V2;

use Illuminate\Http\JsonResponse;

class ErrorResponse
{
    public function __construct(
        private string $message,
        private int $status,
    ) {}

    public function toResponse(): JsonResponse
    {
        return response()->json([
            'errors' => [[
                'status' => (string) $this->status,
                'title' => $this->getErrorTitle(),
                'detail' => $this->message,
            ]],
            'meta' => [
                'timestamp' => now()->toISOString(),
                'api_version' => 'v2',
            ],
            'jsonapi' => [
                'version' => '1.1',
            ],
        ], $this->status);
    }

    private function getErrorTitle(): string
    {
        return match ($this->status) {
            400 => 'Bad Request',
            404 => 'Not Found',
            500 => 'Internal Server Error',
            default => 'Error',
        };
    }
}
