<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V2\GetLastMessageRequest;
use App\Http\Resources\V2\MessageResource;
use App\Http\Responses\Api\V2\ErrorResponse;
use App\Services\Telegram\MessageService;
use App\Services\TelegramChannelService;
use Illuminate\Support\Facades\Log;

class MessageController extends Controller
{
    public function __construct(
        private TelegramChannelService $telegramService,
        private MessageService $messageService,
    ) {}

    /**
     * Get the last message ID for a channel
     */
    public function getLastMessageId(GetLastMessageRequest $request)
    {
        try {
            $channel = $request->getChannel();
            $lastMessageId = $this->telegramService->getLastMessageId($channel);

            if ($lastMessageId === null) {
                return (new ErrorResponse('Channel not found or no messages available', 404))->toResponse();
            }

            $cacheInfo = $this->messageService->getCacheInfo($channel);

            return new MessageResource([
                'channel' => $channel,
                'last_message_id' => $lastMessageId,
                'from_cache' => $cacheInfo['from_cache'],
                'cache_age_seconds' => $cacheInfo['cache_age'],
            ]);

        } catch (\Exception $e) {
            Log::error('Error in V2 getLastMessageId: ' . $e->getMessage());

            return (new ErrorResponse('Internal server error', 500))->toResponse();
        }
    }
}
