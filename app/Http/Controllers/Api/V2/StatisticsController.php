<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V2\GetStatisticsRequest;
use App\Http\Resources\V2\StatisticsResource;
use App\Http\Responses\Api\V2\ErrorResponse;
use App\Services\TelegramChannelService;
use Illuminate\Support\Facades\Log;

class StatisticsController extends Controller
{
    public function __construct(
        private TelegramChannelService $telegramService,
    ) {}

    /**
     * Get channel statistics
     */
    public function getStatistics(GetStatisticsRequest $request)
    {
        try {
            $channel = $request->getChannel();
            $days = $request->getDays();

            $stats = $this->telegramService->getChannelStatistics($channel, $days);

            if ($stats === null || (is_array($stats) && isset($stats['data']) && $stats['data'] === null)) {
                return (new ErrorResponse('Channel not found or unable to retrieve messages', 404))->toResponse();
            }

            return new StatisticsResource([
                'channel' => $channel,
                'stats' => $stats,
                'days' => $days,
            ]);

        } catch (\RuntimeException $e) {
            Log::error('Error in V2 getStatistics: ' . $e->getMessage());

            // Check if it's an authentication error
            if (str_contains($e->getMessage(), 'authentication required')) {
                return (new ErrorResponse($e->getMessage(), 401))->toResponse();
            }

            return (new ErrorResponse('Internal server error', 500))->toResponse();
        } catch (\Exception $e) {
            Log::error('Error in V2 getStatistics: ' . $e->getMessage());

            return (new ErrorResponse('Internal server error', 500))->toResponse();
        }
    }
}
