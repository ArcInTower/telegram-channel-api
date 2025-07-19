<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V2\GetPollsRequest;
use App\Http\Requests\Api\V2\GetSinglePollRequest;
use App\Http\Resources\V2\PollResource;
use App\Http\Resources\V2\PollsResource;
use App\Http\Responses\Api\V2\ErrorResponse;
use App\Services\Telegram\PollService;
use Illuminate\Support\Facades\Log;

class PollController extends Controller
{
    public function __construct(
        private PollService $pollService
    ) {
    }

    /**
     * Get polls from a channel within a time period
     */
    public function channelPolls(GetPollsRequest $request)
    {
        try {
            $channel = $request->getChannel();
            $period = $request->getPeriod();
            $limit = $request->getLimit();
            $offset = $request->getOffset();

            $data = $this->pollService->getChannelPolls($channel, $period, $limit, $offset);
            
            // Get cache metadata
            $cacheMetadata = $offset === null 
                ? $this->pollService->getCacheMetadataForPolls($channel, $period, $limit)
                : ['from_cache' => false, 'cached_at' => null, 'cache_ttl_seconds' => 0];

            // Return resource with cache metadata
            return new PollsResource([
                'data' => $data,
                '_cache_meta' => $cacheMetadata,
            ]);

        } catch (\Exception $e) {
            return $this->handleTelegramException($e, 'channelPolls');
        }
    }

    /**
     * Get a specific poll by message ID
     */
    public function getPoll(GetSinglePollRequest $request)
    {
        try {
            $channel = $request->getChannel();
            $messageId = $request->getMessageId();

            $poll = $this->pollService->getPollByMessageId($channel, $messageId);
            
            if (!$poll) {
                return (new ErrorResponse('Poll not found', 404))->toResponse();
            }

            // Get cache metadata
            $cacheMetadata = $this->pollService->getCacheMetadataForPoll($channel, $messageId);

            // Return resource with cache metadata
            return new PollResource([
                'data' => [
                    'channel' => $channel,
                    'poll' => $poll
                ],
                '_cache_meta' => $cacheMetadata,
            ]);

        } catch (\Exception $e) {
            return $this->handleTelegramException($e, 'getPoll');
        }
    }
}