<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Resources\V2\ReactionResource;
use App\Http\Responses\Api\V2\ErrorResponse;
use App\Services\Telegram\ReactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReactionController extends Controller
{
    public function __construct(
        private ReactionService $reactionService
    ) {
    }

    /**
     * Get reactions analysis for a channel
     */
    public function channelReactions(Request $request, string $channel)
    {
        try {
            // Validate channel name
            $request->merge(['channel' => $channel]);
            $request->validate([
                'channel' => ['required', 'string', 'regex:/^[a-zA-Z0-9_]+$/'],
                'period' => ['sometimes', 'string', 'in:1hour,1day,7days,30days,3months,6months,1year'],
                'limit' => ['sometimes', 'integer', 'min:1', 'max:1000']
            ]);

            $period = $request->input('period', '7days');
            $limit = $request->input('limit', 100);

            $data = $this->reactionService->getChannelReactions($channel, $period, $limit);
            
            // Get cache metadata
            $cacheMetadata = $this->reactionService->getCacheMetadataForChannel($channel, $period, $limit);
            
            // Return resource with cache metadata
            return new ReactionResource(array_merge($data, [
                '_cache_meta' => $cacheMetadata
            ]));

        } catch (\Exception $e) {
            return $this->handleTelegramException($e, 'channelReactions');
        }
    }

    /**
     * Get reactions for a specific message
     */
    public function messageReactions(Request $request, string $channel, int $messageId)
    {
        try {
            // Validate inputs
            $request->merge(['channel' => $channel, 'messageId' => $messageId]);
            $request->validate([
                'channel' => ['required', 'string', 'regex:/^[a-zA-Z0-9_]+$/'],
                'messageId' => ['required', 'integer', 'min:1']
            ]);

            $data = $this->reactionService->getMessageReactions($channel, $messageId);
            
            if (!$data) {
                return (new ErrorResponse('Message not found or no reactions available', 404))->toResponse();
            }

            // Get cache metadata
            $cacheMetadata = $this->reactionService->getCacheMetadataForMessage($channel, $messageId);
            
            // Return resource with cache metadata
            return new ReactionResource(array_merge($data, [
                '_cache_meta' => $cacheMetadata
            ]));

        } catch (\Exception $e) {
            return $this->handleTelegramException($e, 'messageReactions');
        }
    }

}