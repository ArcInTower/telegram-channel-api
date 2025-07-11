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

            if ($result === null || (is_array($result) && isset($result['data']) && $result['data'] === null)) {
                return (new ErrorResponse('Channel not found or no messages available', 404))->toResponse();
            }

            return new MessageResource($result);

        } catch (\RuntimeException $e) {
            Log::error('Error in V2 getLastMessageId: ' . $e->getMessage());

            // Check if it's an authentication error
            if (str_contains($e->getMessage(), 'authentication required')) {
                return (new ErrorResponse($e->getMessage(), 401))->toResponse();
            }

            return (new ErrorResponse('Internal server error', 500))->toResponse();
        } catch (\Exception $e) {
            Log::error('Error in V2 getLastMessageId: ' . $e->getMessage());

            return (new ErrorResponse('Internal server error', 500))->toResponse();
        }
    }
}
