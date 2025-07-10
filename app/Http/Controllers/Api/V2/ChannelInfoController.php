<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V2\GetChannelInfoRequest;
use App\Http\Responses\Api\V2\ChannelInfoResponse;
use App\Http\Responses\Api\V2\ErrorResponse;
use App\Services\TelegramChannelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ChannelInfoController extends Controller
{
    public function __construct(
        private TelegramChannelService $telegramService,
    ) {}

    /**
     * Get channel information
     */
    public function getChannelInfo(GetChannelInfoRequest $request): JsonResponse
    {
        try {
            $channel = $request->getChannel();

            $info = $this->telegramService->getChannelInfo($channel);

            if ($info === null) {
                return (new ErrorResponse('Channel not found', 404))->toResponse();
            }

            return (new ChannelInfoResponse(
                $channel,
                $info,
            ))->toResponse();

        } catch (\Exception $e) {
            Log::error('Error in V2 getChannelInfo: ' . $e->getMessage());

            return (new ErrorResponse('Internal server error', 500))->toResponse();
        }
    }
}
