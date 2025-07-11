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
        $response = [
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
        ];

        // Add authentication link for 401 errors
        if ($this->status === 401) {
            $response['errors'][0]['links'] = [
                'about' => url('/telegram-auth'),
            ];
            $response['errors'][0]['meta'] = [
                'help' => 'Please authenticate with Telegram to access this resource.',
            ];
        }

        return response()->json($response, $this->status);
    }

    private function getErrorTitle(): string
    {
        return match ($this->status) {
            400 => 'Bad Request',
            401 => 'Unauthorized',
            404 => 'Not Found',
            500 => 'Internal Server Error',
            default => 'Error',
        };
    }
}
