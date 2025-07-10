<?php

namespace App\Services\Telegram;

use App\Contracts\TelegramApiInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MessageService
{
    private int $cacheTtl;

    public function __construct(
        private TelegramApiInterface $apiClient,
    ) {
        $this->cacheTtl = config('telegram.cache_ttl', 300);
    }

    public function getLastMessageId(string $channelUsername): ?int
    {
        $channelUsername = $this->normalizeUsername($channelUsername);
        $cacheKey = 'telegram_channel:' . $channelUsername;

        // Try to get from cache
        $cachedData = Cache::get($cacheKey);

        if ($cachedData !== null) {
            Log::info("Cache hit for channel: {$channelUsername}");

            return $cachedData['last_message_id'];
        }

        // Fetch fresh data
        $messageId = $this->fetchLastMessageId($channelUsername);

        if ($messageId !== null) {
            // Cache the result
            $data = [
                'last_message_id' => $messageId,
                'last_checked_at' => now()->toISOString(),
            ];
            Cache::put($cacheKey, $data, $this->cacheTtl);
            Log::info("Cached message ID {$messageId} for channel: {$channelUsername}");
        }

        return $messageId;
    }

    public function getCacheInfo(string $channelUsername): array
    {
        $channelUsername = $this->normalizeUsername($channelUsername);
        $cacheKey = 'telegram_channel:' . $channelUsername;

        $cachedData = Cache::get($cacheKey);

        if ($cachedData === null) {
            return [
                'from_cache' => false,
                'cache_age' => null,
            ];
        }

        $lastChecked = \Carbon\Carbon::parse($cachedData['last_checked_at']);
        $ageSeconds = $lastChecked->diffInSeconds(now());

        return [
            'from_cache' => $ageSeconds < 5, // Consider "from cache" if less than 5 seconds old
            'cache_age' => $ageSeconds,
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
            Log::error("Error fetching channel {$username}: " . $e->getMessage());

            return null;
        }
    }

    private function normalizeUsername(string $username): string
    {
        return ltrim(strtolower($username), '@');
    }
}
