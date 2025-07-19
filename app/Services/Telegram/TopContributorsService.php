<?php

namespace App\Services\Telegram;

use App\Contracts\TelegramApiInterface;
use App\Services\CacheableService;
use App\Services\Telegram\Statistics\TopContributorsCalculator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TopContributorsService extends CacheableService
{
    private TelegramApiInterface $telegramClient;
    private TopContributorsCalculator $valueCalculator;
    private int $cacheTtl;

    public function __construct(TelegramApiInterface $telegramClient, TopContributorsCalculator $valueCalculator)
    {
        $this->telegramClient = $telegramClient;
        $this->valueCalculator = $valueCalculator;
        $this->cacheTtl = 3600; // 1 hour cache for user values (intensive calculation)
    }

    /**
     * Get top contributors rankings for a channel
     */
    public function getChannelTopContributors(string $channelUsername, int $days = 7, int $limit = 50, ?int $offset = null): array
    {
        $channelUsername = $this->normalizeUsername($channelUsername);
        
        // If offset is provided, don't use cache (pagination)
        if ($offset !== null) {
            return $this->calculateUserValues($channelUsername, $days, $limit, $offset);
        }
        
        // Normal cached request
        $cacheKey = "top_contributors_{$channelUsername}_{$days}_{$limit}";

        return $this->getWithCache($cacheKey, $this->cacheTtl, function () use ($channelUsername, $days, $limit) {
            return $this->calculateUserValues($channelUsername, $days, $limit, null);
        });
    }

    /**
     * Calculate user values for the channel
     */
    private function calculateUserValues(string $channelUsername, int $days, int $limit, ?int $offset): array
    {
        try {
            // Calculate the cutoff timestamp
            $endDate = now();
            $startDate = $endDate->copy()->subDays($days);
            $cutoffTimestamp = $startDate->timestamp;

            // Fetch messages from the channel
            $messages = [];
            $userInfoCache = [];
            $hasMore = true;
            $lastMessageId = null;
            $totalMessagesScanned = 0;
            $maxMessages = 10000; // Safety limit

            while ($hasMore && $totalMessagesScanned < $maxMessages) {
                $params = [
                    'limit' => 100,
                ];
                
                if ($lastMessageId !== null) {
                    $params['offset_id'] = $lastMessageId;
                }
                
                $batch = $this->telegramClient->getMessagesHistory($channelUsername, $params);

                if (empty($batch) || empty($batch['messages'])) {
                    break;
                }

                // Extract user information from the batch
                if (isset($batch['users'])) {
                    foreach ($batch['users'] as $user) {
                        if (isset($user['id'])) {
                            $userInfoCache[$user['id']] = [
                                'first_name' => $user['first_name'] ?? '',
                                'last_name' => $user['last_name'] ?? '',
                                'username' => $user['username'] ?? null,
                            ];
                        }
                    }
                }

                // Filter messages within the time period
                foreach ($batch['messages'] as $message) {
                    if (isset($message['date']) && $message['date'] >= $cutoffTimestamp) {
                        $messages[] = $message;
                    } elseif (isset($message['date']) && $message['date'] < $cutoffTimestamp) {
                        // We've gone past our time period
                        $hasMore = false;
                        break;
                    }
                }

                $totalMessagesScanned += count($batch['messages']);
                
                // Check if we have more messages to fetch
                if (count($batch['messages']) < 100 || !$hasMore) {
                    $hasMore = false;
                } else {
                    $lastMessage = end($batch['messages']);
                    $lastMessageId = $lastMessage['id'] ?? null;
                }
            }

            // Calculate user values using the calculator with user info cache
            $result = $this->valueCalculator->calculateUserValues($messages, $days, $userInfoCache);
            
            // Apply pagination if offset is provided
            $rankings = $result['user_rankings'];
            $totalUsers = count($rankings);
            
            if ($offset !== null) {
                $rankings = array_slice($rankings, $offset, $limit);
            } else {
                $rankings = array_slice($rankings, 0, $limit);
            }

            return [
                'channel' => $channelUsername,
                'period' => $result['period'],
                'rankings' => $rankings,
                'summary' => $result['summary'],
                'total_users' => $totalUsers,
                'messages_analyzed' => count($messages),
                'messages_scanned' => $totalMessagesScanned,
                'has_more' => $offset !== null ? ($offset + $limit < $totalUsers) : ($limit < $totalUsers),
                'next_offset' => ($offset !== null && $offset + $limit < $totalUsers) ? $offset + $limit : null
            ];

        } catch (\Exception $e) {
            Log::error('Error calculating user values', [
                'channel' => $channelUsername,
                'days' => $days,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get cache metadata for top contributors
     */
    public function getCacheMetadataForTopContributors(string $channelUsername, int $days, int $limit): array
    {
        $channelUsername = $this->normalizeUsername($channelUsername);
        $cacheKey = "top_contributors_{$channelUsername}_{$days}_{$limit}";
        
        return $this->getCacheMetadata($cacheKey, $this->cacheTtl);
    }

    /**
     * Normalize channel username
     */
    private function normalizeUsername(string $username): string
    {
        return ltrim($username, '@');
    }
}