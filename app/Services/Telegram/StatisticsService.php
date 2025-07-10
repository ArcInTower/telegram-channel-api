<?php

namespace App\Services\Telegram;

use App\Contracts\TelegramApiInterface;
use App\Services\Telegram\Statistics\StatisticsCalculator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class StatisticsService
{
    public function __construct(
        private TelegramApiInterface $apiClient,
        private StatisticsCalculator $calculator,
    ) {}

    public function getChannelStatistics(string $channelUsername, int $days = 7): ?array
    {
        try {
            $channelUsername = $this->normalizeUsername($channelUsername);

            // Create cache key based on channel and days
            $cacheKey = "telegram_stats:{$channelUsername}:{$days}";
            $cacheTtl = config('telegram.statistics_cache_ttl', 3600); // 1 hour default

            // Try to get from cache first
            $cachedStats = Cache::get($cacheKey);
            if ($cachedStats !== null) {
                Log::info("Returning cached statistics for channel: {$channelUsername} for {$days} days");

                return $cachedStats;
            }

            Log::info("Getting fresh statistics for channel: {$channelUsername} for {$days} days");

            $channelPeer = '@' . ltrim($channelUsername, '@');

            $info = $this->apiClient->getChannelInfo($channelPeer);

            if (!$info || !in_array($info['type'], ['channel', 'supergroup'])) {
                Log::warning("Not a public channel: {$channelUsername} (type: " . ($info['type'] ?? 'unknown') . ')');

                return null;
            }

            $endDate = now();
            $startDate = now()->subDays($days);

            $messages = $this->fetchMessagesInDateRange($channelPeer, $startDate, $endDate);

            if (empty($messages['allMessages'])) {
                $stats = $this->createEmptyStatistics($startDate, $endDate, $days);
            } else {
                $stats = $this->calculator->calculate(
                    $messages['allMessages'],
                    $startDate,
                    $endDate,
                    $messages['userInfoCache'],
                );
            }

            // Cache the statistics
            Cache::put($cacheKey, $stats, $cacheTtl);
            Log::info("Cached statistics for channel: {$channelUsername} for {$cacheTtl} seconds");

            return $stats;

        } catch (\Exception $e) {
            Log::error('Error getting channel statistics: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return null;
        }
    }

    private function fetchMessagesInDateRange(string $channelPeer, Carbon $startDate, Carbon $endDate): array
    {
        $allMessages = [];
        $userInfoCache = [];
        $offsetId = 0;
        $hasMore = true;

        Log::info('Starting to fetch messages for statistics');

        while ($hasMore) {
            $params = [
                'limit' => 100,
                'offset_id' => $offsetId,
            ];

            $messages = $this->apiClient->getMessagesHistory($channelPeer, $params);

            if (empty($messages['messages'])) {
                Log::info('No more messages to fetch');
                break;
            }

            Log::info('Fetched batch with ' . count($messages['messages']) . ' messages');

            $this->extractUserInfo($messages, $userInfoCache);

            $result = $this->processMessageBatch($messages['messages'], $startDate, $endDate, $allMessages);

            if (!$result['hasMore']) {
                $hasMore = false;
                break;
            }

            if (count($messages['messages']) < 100) {
                break;
            }

            $lastMessage = end($messages['messages']);
            if ($lastMessage && isset($lastMessage['id'])) {
                $offsetId = $lastMessage['id'];
            } else {
                break;
            }
        }

        Log::info('Total messages collected for statistics: ' . count($allMessages));

        return [
            'allMessages' => $allMessages,
            'userInfoCache' => $userInfoCache,
        ];
    }

    private function extractUserInfo(array $messages, array &$userInfoCache): void
    {
        if (isset($messages['users'])) {
            foreach ($messages['users'] as $user) {
                if (isset($user['id'])) {
                    $userInfoCache[$user['id']] = [
                        'first_name' => $user['first_name'] ?? '',
                        'last_name' => $user['last_name'] ?? '',
                        'username' => $user['username'] ?? null,
                    ];
                }
            }
        }
    }

    private function processMessageBatch(array $messages, Carbon $startDate, Carbon $endDate, array &$allMessages): array
    {
        $hasMore = true;

        foreach ($messages as $message) {
            if (!isset($message['date'])) {
                continue;
            }

            $messageDate = Carbon::createFromTimestamp($message['date']);

            if ($messageDate->isBefore($startDate)) {
                $hasMore = false;
                break;
            }

            if ($messageDate->isBetween($startDate, $endDate)) {
                $allMessages[] = $message;
            }
        }

        return ['hasMore' => $hasMore];
    }

    private function createEmptyStatistics(Carbon $startDate, Carbon $endDate, int $days): array
    {
        Log::warning("No messages found for statistics in the last {$days} days");

        return [
            'period' => [
                'start' => $startDate->toISOString(),
                'end' => $endDate->toISOString(),
                'days' => $days,
            ],
            'summary' => [
                'total_messages' => 0,
                'unique_users' => 0,
                'total_replies' => 0,
                'reply_rate' => 0,
                'average_messages_per_user' => 0,
                'average_message_length' => 0,
            ],
            'top_users' => [],
            'activity_patterns' => [
                'by_hour' => array_combine(
                    array_map(fn ($h) => sprintf('%02d:00', $h), range(0, 23)),
                    array_fill(0, 24, 0),
                ),
                'by_weekday' => array_combine(
                    ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
                    array_fill(0, 7, 0),
                ),
                'by_date' => [],
            ],
            'peak_activity' => [
                'hour' => 'N/A',
                'weekday' => 'N/A',
            ],
        ];
    }

    private function normalizeUsername(string $username): string
    {
        return ltrim(strtolower($username), '@');
    }
}
