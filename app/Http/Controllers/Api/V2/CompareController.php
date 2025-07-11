<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Resources\V2\ComparisonResource;
use App\Http\Responses\Api\V2\ErrorResponse;
use App\Services\TelegramChannelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * @beta This controller is experimental and may change without notice
 */
class CompareController extends Controller
{
    public function __construct(
        private TelegramChannelService $telegramService,
    ) {}

    /**
     * Compare statistics of multiple channels
     *
     * @beta This endpoint is experimental and subject to change without notice
     */
    public function compareChannels(Request $request)
    {
        try {
            // Validate request
            $request->validate([
                'channels' => 'required|array|min:2|max:4',
                'channels.*' => 'required|string|regex:/^[a-zA-Z0-9_]+$/',
                'days' => 'integer|min:1|max:7',
            ]);

            $channels = $request->input('channels');
            $days = $request->input('days', 7);

            // Remove duplicates
            $channels = array_unique($channels);

            if (count($channels) < 2) {
                return (new ErrorResponse('At least 2 different channels are required for comparison', 400))->toResponse();
            }

            $comparisons = [];
            $errors = [];

            // Fetch statistics for each channel
            foreach ($channels as $channel) {
                try {
                    $stats = $this->telegramService->getChannelStatistics($channel, $days);

                    if ($stats !== null && isset($stats['data'])) {
                        $comparisons[] = [
                            'channel' => $channel,
                            'statistics' => $stats['data'],
                            'cache_meta' => $stats['_cache_meta'] ?? null,
                        ];
                    } else {
                        $errors[] = [
                            'channel' => $channel,
                            'error' => 'Channel not found or no data available',
                        ];
                    }
                } catch (\Exception $e) {
                    $errors[] = [
                        'channel' => $channel,
                        'error' => 'Failed to fetch statistics',
                    ];
                }
            }

            if (empty($comparisons)) {
                return (new ErrorResponse('No valid channels found for comparison', 404))->toResponse();
            }

            $response = new ComparisonResource([
                'comparisons' => $comparisons,
                'errors' => $errors,
                'days' => $days,
            ]);

            return $response->response()
                ->header('X-API-Beta', 'This endpoint is experimental and may change without notice');

        } catch (\Exception $e) {
            Log::error('Error in compareChannels: ' . $e->getMessage());

            return (new ErrorResponse('Internal server error', 500))->toResponse();
        }
    }
}
