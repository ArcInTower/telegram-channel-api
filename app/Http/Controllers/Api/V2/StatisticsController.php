<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V2\GetStatisticsRequest;
use App\Http\Responses\Api\V2\ErrorResponse;
use App\Http\Responses\Api\V2\StatisticsResponse;
use App\Services\TelegramChannelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class StatisticsController extends Controller
{
    public function __construct(
        private TelegramChannelService $telegramService,
    ) {}

    /**
     * Get channel statistics
     */
    public function getStatistics(GetStatisticsRequest $request): JsonResponse
    {
        try {
            $channel = $request->getChannel();
            $days = $request->getDays();

            $stats = $this->telegramService->getChannelStatistics($channel, $days);

            if ($stats === null) {
                return (new ErrorResponse('Channel not found or unable to retrieve messages', 404))->toResponse();
            }

            return (new StatisticsResponse(
                $channel,
                $stats,
                $days,
            ))->toResponse();

        } catch (\Exception $e) {
            Log::error('Error in V2 getStatistics: ' . $e->getMessage());

            return (new ErrorResponse('Internal server error', 500))->toResponse();
        }
    }
}
