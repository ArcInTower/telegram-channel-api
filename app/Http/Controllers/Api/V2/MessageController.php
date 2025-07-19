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
            $result = $this->messageService->getLastMessageId($channel);

            if ($result === null) {
                return (new ErrorResponse('Channel not found or no messages available', 404))->toResponse();
            }

            // Get cache metadata separately
            $cacheMetadata = $this->messageService->getCacheMetadataForChannel($channel);

            // Pass data with cache metadata in the expected format
            return new MessageResource([
                'data' => $result,
                '_cache_meta' => $cacheMetadata,
            ]);

        } catch (\Exception $e) {
            return $this->handleTelegramException($e, 'V2 getLastMessageId');
        }
    }
}
