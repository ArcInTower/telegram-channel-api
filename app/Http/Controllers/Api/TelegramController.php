<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetLastMessageRequest;
use App\Http\Resources\V1\LastMessageResource;
use App\Services\Telegram\MessageService;
use App\Services\TelegramChannelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class TelegramController extends Controller
{
    private TelegramChannelService $telegramService;
    private MessageService $messageService;

    public function __construct(
        TelegramChannelService $telegramService,
        MessageService $messageService,
    ) {
        $this->telegramService = $telegramService;
        $this->messageService = $messageService;
    }

    public function getLastMessageId(GetLastMessageRequest $request): JsonResponse
    {
        try {
            $channel = $request->input('channel');
            $lastMessageId = $this->telegramService->getLastMessageId($channel);

            if ($lastMessageId === null) {
                return $this->errorResponse('Channel not found or no messages available', 404, ['channel' => $channel]);
            }

            $cacheInfo = $this->messageService->getCacheInfo($channel);

            $response = new LastMessageResource([
                'channel' => $channel,
                'last_message_id' => $lastMessageId,
                'from_cache' => $cacheInfo['from_cache'],
                'cache_age_seconds' => $cacheInfo['cache_age'],
            ]);

            return $response->response()
                ->header('X-API-Deprecation-Warning', 'This endpoint is deprecated. Please use /api/v2/telegram/channels/{channel}/messages/last-id instead');

        } catch (\Exception $e) {
            Log::error('Error in getLastMessageId: ' . $e->getMessage());

            return $this->errorResponse('Internal server error', 500);
        }
    }

    /**
     * Return a standardized error response
     */
    private function errorResponse(string $message, int $status, array $additional = []): JsonResponse
    {
        return response()->json(array_merge([
            'success' => false,
            'error' => $message,
        ], $additional), $status);
    }
}
