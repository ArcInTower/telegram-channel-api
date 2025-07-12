<?php

namespace App\Services;

use App\Contracts\TelegramApiInterface;
use App\Services\Telegram\MessageService;
use App\Services\Telegram\StatisticsService;

class TelegramChannelService
{
    public function __construct(
        private MessageService $messageService,
        private StatisticsService $statisticsService,
        private TelegramApiInterface $apiClient,
    ) {}

    public function getLastMessageId(string $channelUsername): ?int
    {
        $result = $this->messageService->getLastMessageId($channelUsername);

        // Extract the message ID from the new format
        if (is_array($result) && isset($result['last_message_id'])) {
            return $result['last_message_id'];
        }

        return null;
    }

    public function getChannelInfo(string $channelUsername): ?array
    {
        $info = $this->apiClient->getChannelInfo($channelUsername);
        if ($info) {
            $info['last_message_id'] = $this->getLastMessageId($channelUsername);
        }

        return $info;
    }

    public function getChannelStatistics(string $channelUsername, int $days = 7): ?array
    {
        $stats = $this->statisticsService->getChannelStatistics($channelUsername, $days);

        // If we have stats, wrap them with cache metadata for V2 API
        if ($stats !== null) {
            $cacheMetadata = $this->statisticsService->getCacheMetadataForStats($channelUsername, $days);

            return [
                'data' => $stats,
                '_cache_meta' => $cacheMetadata,
            ];
        }

        return null;
    }
}
