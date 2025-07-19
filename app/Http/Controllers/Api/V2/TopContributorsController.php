<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V2\GetTopContributorsRequest;
use App\Http\Resources\V2\TopContributorsResource;
use App\Http\Responses\Api\V2\ErrorResponse;
use App\Services\Telegram\TopContributorsService;
use Illuminate\Support\Facades\Log;

class TopContributorsController extends Controller
{
    public function __construct(
        private TopContributorsService $topContributorsService
    ) {
    }

    /**
     * Get top contributors rankings for a channel
     */
    public function channelTopContributors(GetTopContributorsRequest $request)
    {
        try {
            $channel = $request->getChannel();
            $days = $request->getDays();
            $limit = $request->getLimit();
            $offset = $request->getOffset();

            $data = $this->topContributorsService->getChannelTopContributors($channel, $days, $limit, $offset);
            
            // Get cache metadata
            $cacheMetadata = $offset === null 
                ? $this->topContributorsService->getCacheMetadataForTopContributors($channel, $days, $limit)
                : ['from_cache' => false, 'cached_at' => null, 'cache_ttl_seconds' => 0];

            // Return resource with cache metadata
            return new TopContributorsResource([
                'data' => $data,
                '_cache_meta' => $cacheMetadata,
            ]);

        } catch (\Exception $e) {
            return $this->handleTelegramException($e, 'channelTopContributors');
        }
    }
}