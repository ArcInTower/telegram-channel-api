<?php

namespace App\Services\Telegram;

use App\Contracts\TelegramApiInterface;
use App\Services\CacheableService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ReactionService extends CacheableService
{
    private TelegramApiInterface $telegramClient;
    private int $cacheTtl;

    public function __construct(TelegramApiInterface $telegramClient)
    {
        $this->telegramClient = $telegramClient;
        $this->cacheTtl = config('telegram.cache_ttl', 300);
    }

    /**
     * Get reactions for a specific message
     */
    public function getMessageReactions(string $channelUsername, int $messageId): ?array
    {
        $channelUsername = $this->normalizeUsername($channelUsername);
        $cacheKey = "reactions_{$channelUsername}_{$messageId}";

        return $this->getWithCache($cacheKey, $this->cacheTtl * 2, function () use ($channelUsername, $messageId) {
            try {
                $message = $this->telegramClient->getMessage($channelUsername, $messageId);
                
                if (!$message) {
                    return null;
                }

                $messageText = mb_substr($message['message'] ?? '', 0, 200);
                $reactions = isset($message['reactions']) ? $this->formatReactions($message['reactions']) : [];
                $totalReactions = array_sum(array_column($reactions, 'count'));

                return [
                    'channel' => $channelUsername,
                    'message_id' => $messageId,
                    'message_preview' => $messageText,
                    'total_reactions' => $totalReactions,
                    'reactions' => $reactions,
                    'message_date' => isset($message['date']) ? 
                        Carbon::createFromTimestamp($message['date'])->toIso8601String() : null
                ];
            } catch (\Exception $e) {
                Log::error('Error fetching message reactions', [
                    'channel' => $channelUsername,
                    'message_id' => $messageId,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        });
    }

    /**
     * Get channel reactions analysis
     */
    public function getChannelReactions(string $channelUsername, string $period = '7days', int $limit = 100): array
    {
        $channelUsername = $this->normalizeUsername($channelUsername);
        $cacheKey = "reaction_analysis_{$channelUsername}_{$period}_{$limit}";

        return $this->getWithCache($cacheKey, $this->getCacheTTL('channel', $period), function () use ($channelUsername, $period, $limit) {
            try {
                $cutoffDate = $this->getCutoffDate($period);
                // Limit the number of messages to fetch based on period
                $maxMessages = match($period) {
                    '1hour' => 100,
                    '1day' => 500,
                    '7days' => 1000,
                    '30days' => 2000,
                    default => 3000
                };
                $messages = $this->fetchMessagesForPeriod($channelUsername, $cutoffDate, min($limit * 2, $maxMessages));
                
                $totalReactions = 0;
                $reactionTypes = [];
                $messagesWithReactions = 0;
                $topMessages = [];

                foreach ($messages as $message) {
                    if (!isset($message['reactions']) || empty($message['reactions']['results'])) {
                        continue;
                    }

                    $messagesWithReactions++;
                    $messageReactionCount = 0;

                    foreach ($message['reactions']['results'] as $reaction) {
                        $emoji = $reaction['reaction']['emoticon'] ?? $reaction['reaction']['document_id'] ?? 'custom';
                        $count = $reaction['count'] ?? 0;
                        
                        $totalReactions += $count;
                        $messageReactionCount += $count;

                        if (!isset($reactionTypes[$emoji])) {
                            $reactionTypes[$emoji] = [
                                'emoji' => $emoji,
                                'count' => 0,
                                'is_premium' => $reaction['reaction']['_'] === 'reactionCustomEmoji'
                            ];
                        }
                        $reactionTypes[$emoji]['count'] += $count;
                    }

                    // Track top messages by reaction count
                    if ($messageReactionCount > 0) {
                        $topMessages[] = [
                            'message_id' => $message['id'],
                            'text' => mb_substr($message['message'] ?? '', 0, 100),
                            'date' => $message['date'],
                            'reaction_count' => $messageReactionCount,
                            'reactions' => $this->formatReactions($message['reactions'])
                        ];
                    }
                }

                // Sort top messages by reaction count
                usort($topMessages, fn($a, $b) => $b['reaction_count'] <=> $a['reaction_count']);
                $topMessages = array_slice($topMessages, 0, 10);

                // Sort reaction types by count
                usort($reactionTypes, fn($a, $b) => $b['count'] <=> $a['count']);

                return [
                    'channel' => $channelUsername,
                    'analyzed_messages' => count($messages),
                    'messages_with_reactions' => $messagesWithReactions,
                    'total_reactions' => $totalReactions,
                    'average_reactions_per_message' => $messagesWithReactions > 0 ? 
                        round($totalReactions / $messagesWithReactions, 2) : 0,
                    'reaction_types' => array_values($reactionTypes),
                    'top_messages' => $topMessages,
                    'engagement_rate' => count($messages) > 0 ? 
                        round(($messagesWithReactions / count($messages)) * 100, 2) : 0,
                    'cached_at' => now()->toIso8601String()
                ];
            } catch (\Exception $e) {
                Log::error('Error analyzing channel reactions', [
                    'channel' => $channelUsername,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        });
    }

    private function formatReactions(array $reactions): array
    {
        if (!isset($reactions['results'])) {
            return [];
        }

        $formatted = [];
        foreach ($reactions['results'] as $reaction) {
            $emoji = $reaction['reaction']['emoticon'] ?? $reaction['reaction']['document_id'] ?? 'custom';
            $formatted[] = [
                'emoji' => $emoji,
                'count' => $reaction['count'] ?? 0,
                'is_premium' => $reaction['reaction']['_'] === 'reactionCustomEmoji',
                'chosen' => $reaction['chosen'] ?? false
            ];
        }

        return $formatted;
    }


    /**
     * Fetch messages for a given period
     */
    private function fetchMessagesForPeriod(string $channelUsername, ?Carbon $cutoffDate, int $maxMessages = 1000): array
    {
        $messages = [];
        $offsetId = 0;
        $batchSize = 100;
        
        while (count($messages) < $maxMessages) {
            $result = $this->telegramClient->getMessagesHistory($channelUsername, [
                'limit' => $batchSize,
                'offset_id' => $offsetId
            ]);
            
            if (!$result || empty($result['messages'])) {
                break;
            }
            
            foreach ($result['messages'] as $message) {
                // If we have a cutoff date and message is older, stop
                if ($cutoffDate && $message['date'] < $cutoffDate->timestamp) {
                    return $messages;
                }
                
                $messages[] = $message;
                
                if (count($messages) >= $maxMessages) {
                    return $messages;
                }
            }
            
            // Get the ID of the oldest message for next iteration
            $lastMessage = end($result['messages']);
            $offsetId = $lastMessage['id'];
            
            // If we got fewer messages than requested, we've reached the end
            if (count($result['messages']) < $batchSize) {
                break;
            }
        }
        
        return $messages;
    }

    /**
     * Get cutoff date based on period
     */
    private function getCutoffDate(string $period): Carbon
    {
        return match($period) {
            '1hour' => now()->subHour(),
            '1day' => now()->subDay(),
            '7days' => now()->subDays(7),
            '30days' => now()->subDays(30),
            '3months' => now()->subMonths(3),
            '6months' => now()->subMonths(6),
            '1year' => now()->subYear(),
            default => now()->subDays(7)
        };
    }

    /**
     * Get cache TTL based on type and period
     */
    private function getCacheTTL(string $type, ?string $period = null): int
    {
        // For channel reactions, use dynamic TTL based on period
        if ($type === 'channel' && $period) {
            return match($period) {
                '1hour' => 300,      // 5 minutes
                '1day' => 1800,      // 30 minutes
                '7days' => 3600,     // 1 hour
                '30days' => 7200,    // 2 hours
                '3months' => 21600,  // 6 hours
                '6months' => 43200,  // 12 hours
                '1year' => 86400,    // 24 hours
                default => 1800      // 30 minutes default
            };
        }
        
        return match($type) {
            'message' => 600, // 10 minutes
            default => 300
        };
    }

    /**
     * Normalize channel username
     */
    private function normalizeUsername(string $username): string
    {
        return ltrim(strtolower($username), '@');
    }

    /**
     * Get cache metadata for channel reactions
     */
    public function getCacheMetadataForChannel(string $channelUsername, string $period, int $limit): array
    {
        $channelUsername = $this->normalizeUsername($channelUsername);
        $cacheKey = "reaction_analysis_{$channelUsername}_{$period}_{$limit}";
        return $this->getCacheMetadata($cacheKey, $this->getCacheTTL('channel', $period));
    }

    /**
     * Get cache metadata for message reactions
     */
    public function getCacheMetadataForMessage(string $channelUsername, int $messageId): array
    {
        $channelUsername = $this->normalizeUsername($channelUsername);
        $cacheKey = "reactions_{$channelUsername}_{$messageId}";
        return $this->getCacheMetadata($cacheKey, $this->cacheTtl * 2);
    }

}