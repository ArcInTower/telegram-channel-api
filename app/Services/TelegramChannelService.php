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
        return $this->messageService->getLastMessageId($channelUsername);
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
        return $this->statisticsService->getChannelStatistics($channelUsername, $days);
    }
}
