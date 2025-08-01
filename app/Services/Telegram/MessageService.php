<?php

namespace App\Services\Telegram;

use App\Contracts\TelegramApiInterface;
use App\Services\CacheableService;
use Illuminate\Support\Facades\Log;

class MessageService extends CacheableService
{
    private int $cacheTtl;

    public function __construct(
        private TelegramApiInterface $apiClient,
    ) {
        $this->cacheTtl = config('telegram.cache_ttl', 300);
    }

    public function getLastMessageId(string $channelUsername): ?array
    {
        $channelUsername = $this->normalizeUsername($channelUsername);
        $cacheKey = 'telegram_channel:' . $channelUsername;

        return $this->getWithCache($cacheKey, $this->cacheTtl, function () use ($channelUsername) {
            $messageId = $this->fetchLastMessageId($channelUsername);

            if ($messageId === null) {
                return null;
            }

            return [
                'channel' => $channelUsername,
                'last_message_id' => $messageId,
            ];
        });
    }

    /**
     * Get cache info in legacy format for v1 API compatibility
     *
     * @deprecated Use getLastMessageId() which returns cache info in the response
     */
    public function getCacheInfo(string $channelUsername): array
    {
        $channelUsername = $this->normalizeUsername($channelUsername);
        $cacheKey = 'telegram_channel:' . $channelUsername;
        $cacheMetaKey = $cacheKey . ':meta';

        $cachedData = \Illuminate\Support\Facades\Cache::get($cacheKey);
        $cacheMeta = \Illuminate\Support\Facades\Cache::get($cacheMetaKey);

        if ($cachedData === null) {
            return [
                'from_cache' => false,
                'cache_age' => null,
            ];
        }

        // Calculate age from cached_at timestamp
        $cacheAge = null;
        if ($cacheMeta && isset($cacheMeta['cached_at'])) {
            $cachedAt = \Carbon\Carbon::parse($cacheMeta['cached_at']);
            $cacheAge = $cachedAt->diffInSeconds(now());
        }

        return [
            'from_cache' => $cacheAge !== null && $cacheAge < 5, // Consider "from cache" if less than 5 seconds old
            'cache_age' => $cacheAge,
        ];
    }

    private function fetchLastMessageId(string $username): ?int
    {
        try {
            Log::info("Fetching last message for channel: {$username}");

            $channelUsername = '@' . ltrim($username, '@');

            $info = $this->apiClient->getChannelInfo($channelUsername);

            if ($info && !in_array($info['type'], ['channel', 'supergroup'])) {
                Log::warning("Not a public channel: {$username}");

                return null;
            }

            $messages = $this->apiClient->getMessagesHistory($channelUsername, ['limit' => 1]);

            if (!empty($messages['messages'])) {
                $lastMessage = $messages['messages'][0];
                $messageId = $lastMessage['id'];

                Log::info("Found last message ID {$messageId} for channel: {$username}");

                return $messageId;
            }

            Log::warning("No messages found for channel: {$username}");

            return null;

        } catch (\Exception $e) {
            $message = $e->getMessage();

            // Check for authentication errors
            if (str_contains($message, 'AUTH_KEY_UNREGISTERED') ||
                str_contains($message, 'SESSION_REVOKED') ||
                str_contains($message, 'LOGIN_REQUIRED')) {
                throw new \RuntimeException('Telegram authentication required. The bot session has expired or been revoked.');
            }

            Log::error("Error fetching channel {$username}: " . $message);

            return null;
        }
    }

    private function normalizeUsername(string $username): string
    {
        return ltrim(strtolower($username), '@');
    }

    /**
     * Get cache metadata for the last request
     */
    public function getCacheMetadataForChannel(string $channelUsername): array
    {
        $channelUsername = $this->normalizeUsername($channelUsername);
        $cacheKey = 'telegram_channel:' . $channelUsername;

        return $this->getCacheMetadata($cacheKey, $this->cacheTtl);
    }

    /**
     * Get messages from a channel within a date range
     */
    public function getMessagesByDateRange(string $channelUsername, \Carbon\Carbon $fromDate, \Carbon\Carbon $toDate, int $limit = 100): ?array
    {
        $channelUsername = $this->normalizeUsername($channelUsername);
        $channelUsername = '@' . ltrim($channelUsername, '@');

        try {
            Log::info("Fetching messages for channel {$channelUsername} from {$fromDate} to {$toDate}");

            // Verify channel exists and is accessible
            $info = $this->apiClient->getChannelInfo($channelUsername);
            if ($info && !in_array($info['type'], ['channel', 'supergroup'])) {
                Log::warning("Not a public channel: {$channelUsername}");
                return null;
            }

            $allMessages = [];
            $offsetId = 0;
            $foundOldMessage = false;
            
            // Convert dates to timestamps
            $fromTimestamp = $fromDate->timestamp;
            $toTimestamp = $toDate->timestamp;

            while (count($allMessages) < $limit && !$foundOldMessage) {
                // Fetch messages in batches
                $params = [
                    'limit' => min(100, $limit - count($allMessages)),
                    'offset_id' => $offsetId,
                ];

                $messages = $this->apiClient->getMessagesHistory($channelUsername, $params);

                if (empty($messages['messages'])) {
                    break;
                }

                foreach ($messages['messages'] as $message) {
                    // Skip if message doesn't have a date
                    if (!isset($message['date'])) {
                        continue;
                    }

                    $messageTimestamp = $message['date'];

                    // If message is older than our range, stop searching
                    if ($messageTimestamp < $fromTimestamp) {
                        $foundOldMessage = true;
                        break;
                    }

                    // If message is within our date range, add it
                    if ($messageTimestamp >= $fromTimestamp && $messageTimestamp <= $toTimestamp) {
                        $allMessages[] = $message;
                        
                        if (count($allMessages) >= $limit) {
                            break;
                        }
                    }

                    // Update offset for next iteration
                    $offsetId = $message['id'];
                }

                // If we've processed all messages in this batch but haven't found old messages yet
                if (!$foundOldMessage && !empty($messages['messages'])) {
                    $lastMessage = end($messages['messages']);
                    $offsetId = $lastMessage['id'];
                }
            }

            Log::info("Found " . count($allMessages) . " messages in date range for channel: {$channelUsername}");

            return $allMessages;

        } catch (\Exception $e) {
            $message = $e->getMessage();

            // Check for authentication errors
            if (str_contains($message, 'AUTH_KEY_UNREGISTERED') ||
                str_contains($message, 'SESSION_REVOKED') ||
                str_contains($message, 'LOGIN_REQUIRED')) {
                throw new \RuntimeException('Telegram authentication required. The bot session has expired or been revoked.');
            }

            Log::error("Error fetching messages for channel {$channelUsername}: " . $message);
            throw $e;
        }
    }
}
