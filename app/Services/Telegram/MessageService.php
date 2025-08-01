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

    /**
     * Send a message to a channel
     */
    public function sendMessage(string $channelUsername, string $message, ?int $replyToMsgId = null, bool $silent = false): ?array
    {
        $channelUsername = $this->normalizeUsername($channelUsername);
        $channelUsername = '@' . ltrim($channelUsername, '@');

        try {
            Log::info("Sending message to channel {$channelUsername}");

            // Verify channel exists and is accessible
            $info = $this->apiClient->getChannelInfo($channelUsername);
            if ($info && !in_array($info['type'], ['channel', 'supergroup'])) {
                Log::warning("Not a public channel: {$channelUsername}");
                throw new \RuntimeException("Can only send messages to channels or supergroups");
            }

            // Prepare message parameters
            $params = [
                'peer' => $channelUsername,
                'message' => $message,
            ];

            if ($replyToMsgId !== null) {
                // Use the InputReplyToMessage format for MadelineProto
                $params['reply_to'] = [
                    '_' => 'inputReplyToMessage',
                    'reply_to_msg_id' => $replyToMsgId
                ];
            }

            if ($silent) {
                $params['silent'] = true;
            }

            // Send the message
            $api = $this->apiClient->getApiInstance();
            $result = $api->messages->sendMessage($params);

            Log::info("Message sent successfully to channel: {$channelUsername}");

            // Return the sent message info
            if (isset($result['updates']) && is_array($result['updates'])) {
                foreach ($result['updates'] as $update) {
                    if (isset($update['_']) && $update['_'] === 'updateMessageID' && isset($update['id'])) {
                        return ['id' => $update['id']];
                    }
                    if (isset($update['message']) && isset($update['message']['id'])) {
                        return ['id' => $update['message']['id']];
                    }
                }
            }

            // If we can't extract the message ID, return a success indicator
            return ['success' => true];

        } catch (\Exception $e) {
            $message = $e->getMessage();

            // Check for authentication errors
            if (str_contains($message, 'AUTH_KEY_UNREGISTERED') ||
                str_contains($message, 'SESSION_REVOKED') ||
                str_contains($message, 'LOGIN_REQUIRED')) {
                throw new \RuntimeException('Telegram authentication required. The bot session has expired or been revoked.');
            }

            // Check for permission errors
            if (str_contains($message, 'CHAT_WRITE_FORBIDDEN') ||
                str_contains($message, 'CHANNEL_PRIVATE')) {
                throw new \RuntimeException('No permission to send messages to this channel');
            }

            Log::error("Error sending message to channel {$channelUsername}: " . $message);
            throw $e;
        }
    }

    /**
     * Delete a message from a channel/group
     */
    public function deleteMessage(string $channelUsername, int $messageId, bool $revoke = false): bool
    {
        $channelUsername = $this->normalizeUsername($channelUsername);
        $channelUsername = '@' . ltrim($channelUsername, '@');

        try {
            Log::info("Deleting message {$messageId} from channel {$channelUsername}");

            // Get the API instance
            $api = $this->apiClient->getApiInstance();

            // Prepare parameters
            $params = [
                'revoke' => $revoke,
                'id' => [$messageId]
            ];

            // For channels/groups, we need to specify the peer
            $params['channel'] = $channelUsername;

            // Delete the message
            $result = $api->channels->deleteMessages($params);

            // Check if deletion was successful
            if (isset($result['pts_count']) && $result['pts_count'] > 0) {
                Log::info("Message {$messageId} deleted successfully from channel: {$channelUsername}");
                return true;
            }

            // Alternative: try with messages.deleteMessages for regular chats
            try {
                $result = $api->messages->deleteMessages([
                    'revoke' => $revoke,
                    'id' => [$messageId]
                ]);
                
                if (isset($result['pts_count']) && $result['pts_count'] > 0) {
                    Log::info("Message {$messageId} deleted successfully");
                    return true;
                }
            } catch (\Exception $e) {
                // If this also fails, the original error is more relevant
            }

            Log::warning("Message deletion returned unexpected result");
            return false;

        } catch (\Exception $e) {
            $message = $e->getMessage();

            // Check for specific errors
            if (str_contains($message, 'MESSAGE_DELETE_FORBIDDEN')) {
                throw new \RuntimeException('You can only delete your own messages');
            }

            if (str_contains($message, 'MESSAGE_ID_INVALID')) {
                throw new \RuntimeException('Invalid message ID or message not found');
            }

            if (str_contains($message, 'CHANNEL_INVALID')) {
                throw new \RuntimeException('Invalid channel or you are not a member');
            }

            if (str_contains($message, 'AUTH_KEY_UNREGISTERED') ||
                str_contains($message, 'SESSION_REVOKED') ||
                str_contains($message, 'LOGIN_REQUIRED')) {
                throw new \RuntimeException('Telegram authentication required. The bot session has expired or been revoked.');
            }

            Log::error("Error deleting message {$messageId} from channel {$channelUsername}: " . $message);
            throw $e;
        }
    }

    /**
     * Send a reaction to a message
     */
    public function sendReaction(string $channelUsername, int $messageId, string $reaction, bool $big = false): bool
    {
        $channelUsername = $this->normalizeUsername($channelUsername);
        $channelUsername = '@' . ltrim($channelUsername, '@');

        try {
            Log::info("Sending reaction to message {$messageId} in channel {$channelUsername}");

            // Get the API instance
            $api = $this->apiClient->getApiInstance();

            // Prepare reaction
            $reactionEmoticon = null;
            if ($reaction !== 'remove' && $reaction !== 'none') {
                $reactionEmoticon = [
                    '_' => 'reactionEmoji',
                    'emoticon' => $reaction
                ];
            }

            // Send the reaction
            $params = [
                'peer' => $channelUsername,
                'msg_id' => $messageId,
                'big' => $big
            ];

            if ($reactionEmoticon) {
                $params['reaction'] = [$reactionEmoticon];
            } else {
                $params['reaction'] = []; // Empty array removes reaction
            }

            $result = $api->messages->sendReaction($params);

            if ($result) {
                Log::info("Reaction sent successfully to message {$messageId}");
                return true;
            }

            return false;

        } catch (\Exception $e) {
            $message = $e->getMessage();

            // Check for specific errors
            if (str_contains($message, 'MESSAGE_NOT_MODIFIED')) {
                // Reaction is already the same
                return true;
            }

            if (str_contains($message, 'MESSAGE_ID_INVALID')) {
                throw new \RuntimeException('Invalid message ID or message not found');
            }

            if (str_contains($message, 'REACTION_INVALID')) {
                throw new \RuntimeException('Invalid reaction emoji');
            }

            if (str_contains($message, 'CHANNEL_INVALID')) {
                throw new \RuntimeException('Invalid channel or you are not a member');
            }

            if (str_contains($message, 'AUTH_KEY_UNREGISTERED') ||
                str_contains($message, 'SESSION_REVOKED') ||
                str_contains($message, 'LOGIN_REQUIRED')) {
                throw new \RuntimeException('Telegram authentication required. The bot session has expired or been revoked.');
            }

            Log::error("Error sending reaction to message {$messageId}: " . $message);
            throw $e;
        }
    }
}
