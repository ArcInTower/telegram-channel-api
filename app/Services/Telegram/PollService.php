<?php

namespace App\Services\Telegram;

use App\Contracts\TelegramApiInterface;
use App\Services\CacheableService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PollService extends CacheableService
{
    private TelegramApiInterface $telegramClient;
    private int $cacheTtl;

    public function __construct(TelegramApiInterface $telegramClient)
    {
        $this->telegramClient = $telegramClient;
        $this->cacheTtl = config('telegram.cache_ttl', 300);
    }

    /**
     * Get polls from a channel within a time period
     */
    public function getChannelPolls(string $channelUsername, string $period = '7days', int $limit = 50, ?int $offsetId = null): array
    {
        $channelUsername = $this->normalizeUsername($channelUsername);
        
        // If offset is provided, don't use cache for the main result (it's a continuation)
        if ($offsetId !== null) {
            try {
                $polls = $this->fetchPolls($channelUsername, $period, $limit, $offsetId);
                
                return [
                    'channel' => $channelUsername,
                    'period' => $period,
                    'polls' => $polls['polls'],
                    'total_polls' => $polls['total'],
                    'messages_scanned' => $polls['messages_scanned'],
                    'scan_completed' => $polls['scan_completed'],
                    'has_more' => $polls['has_more'] ?? false,
                    'next_offset' => $polls['has_more'] ? $polls['last_message_id'] : null,
                    'continued_from_offset' => $offsetId,
                    'processing_info' => [
                        'max_messages_per_request' => 5000,
                        'period_cutoff' => date('Y-m-d H:i:s', $polls['cutoff_timestamp'] ?? 0)
                    ]
                ];
            } catch (\Exception $e) {
                Log::error('Error fetching channel polls with offset', [
                    'channel' => $channelUsername,
                    'offset' => $offsetId,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        }
        
        // Normal cached request
        $cacheKey = "polls_{$channelUsername}_{$period}_{$limit}";

        return $this->getWithCache($cacheKey, $this->cacheTtl, function () use ($channelUsername, $period, $limit) {
            try {
                $polls = $this->fetchPolls($channelUsername, $period, $limit);
                
                return [
                    'channel' => $channelUsername,
                    'period' => $period,
                    'polls' => $polls['polls'],
                    'total_polls' => $polls['total'],
                    'messages_scanned' => $polls['messages_scanned'],
                    'scan_completed' => $polls['scan_completed'],
                    'has_more' => $polls['has_more'] ?? false,
                    'next_offset' => $polls['has_more'] ? $polls['last_message_id'] : null
                ];
            } catch (\Exception $e) {
                Log::error('Error fetching channel polls', [
                    'channel' => $channelUsername,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        });
    }

    /**
     * Get a single poll by message ID
     */
    public function getPollByMessageId(string $channelUsername, int $messageId): ?array
    {
        $channelUsername = $this->normalizeUsername($channelUsername);
        $cacheKey = "poll_{$channelUsername}_{$messageId}";

        return $this->getWithCache($cacheKey, $this->cacheTtl * 2, function () use ($channelUsername, $messageId) {
            try {
                $message = $this->telegramClient->getMessage($channelUsername, $messageId);
                
                if (!$message || !isset($message['media']['_']) || $message['media']['_'] !== 'messageMediaPoll') {
                    return null;
                }

                return $this->formatPoll($message);
            } catch (\Exception $e) {
                Log::error('Error fetching poll by ID', [
                    'channel' => $channelUsername,
                    'message_id' => $messageId,
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        });
    }

    private function fetchPolls(string $channelUsername, string $period, int $limit, ?int $startOffsetId = null): array
    {
        $polls = [];
        $messagesScanned = 0;
        $offsetId = $startOffsetId ?? 0;
        $batchSize = 100;
        $scanCompleted = false;
        
        // Calculate the cutoff date based on period
        $cutoffDate = $this->getCutoffDate($period);
        
        // If we're continuing from an offset, log it
        if ($startOffsetId !== null) {
            Log::info('Continuing poll scan from offset', [
                'channel' => $channelUsername,
                'offset_id' => $startOffsetId,
                'period' => $period
            ]);
        }
        
        // Use cached poll index if available
        $indexCacheKey = "poll_index_{$channelUsername}";
        $cachedIndex = Cache::get($indexCacheKey, []);
        
        // Note: The cached index only contains message IDs and dates for reference
        // We don't use it to populate the polls array as it doesn't contain full poll data
        
        // Fetch new messages to find more polls
        $iterations = 0;
        $maxIterationsPerRequest = 50; // Process max 5000 messages per request (50 * 100)
        $maxMessagesPerRequest = 5000;
        
        while (count($polls) < $limit && $iterations < $maxIterationsPerRequest && !$scanCompleted && $messagesScanned < $maxMessagesPerRequest) {
            $iterations++;
            
            $result = $this->telegramClient->getMessagesHistory($channelUsername, [
                'limit' => $batchSize,
                'offset_id' => $offsetId
            ]);
            
            if (!$result || empty($result['messages'])) {
                $scanCompleted = true;
                break;
            }
            
            foreach ($result['messages'] as $message) {
                $messagesScanned++;
                
                // Check if we've hit our per-request limit
                if ($messagesScanned >= $maxMessagesPerRequest) {
                    Log::info('Hit per-request message limit', [
                        'channel' => $channelUsername,
                        'messages_scanned' => $messagesScanned,
                        'polls_found' => count($polls)
                    ]);
                    break 2; // Break both loops
                }
                
                // Check if message is older than cutoff date
                if ($message['date'] < $cutoffDate->timestamp) {
                    $scanCompleted = true;
                    break 2; // Break both loops
                }
                
                // Check if message contains a poll
                if (isset($message['media']['_']) && $message['media']['_'] === 'messageMediaPoll') {
                    $pollData = $this->formatPoll($message);
                    if ($pollData) {
                        $polls[] = $pollData;
                        
                        // Add to index cache
                        $cachedIndex[$message['id']] = [
                            'id' => $message['id'],
                            'date' => $message['date']
                        ];
                        
                        if (count($polls) >= $limit) {
                            break 2; // Break both loops
                        }
                    }
                }
            }
            
            // Get the ID of the oldest message for next iteration
            $lastMessage = end($result['messages']);
            $offsetId = $lastMessage['id'];
            
            // If we got fewer messages than requested, we've reached the end
            if (count($result['messages']) < $batchSize) {
                $scanCompleted = true;
                break;
            }
            
            // Small delay to avoid rate limiting
            if ($messagesScanned % 500 === 0 && $messagesScanned > 0) {
                sleep(1);
            }
            
            // For large scans, add progress tracking
            if ($messagesScanned % 1000 === 0 && $messagesScanned > 0) {
                Log::info('Poll scan progress', [
                    'channel' => $channelUsername,
                    'messages_scanned' => $messagesScanned,
                    'polls_found' => count($polls),
                    'cutoff_date' => $cutoffDate->toDateTimeString()
                ]);
            }
        }
        
        // Update the poll index cache
        Cache::put($indexCacheKey, $cachedIndex, now()->addDays(7));
        
        // Determine if there are more messages to process
        $hasMore = false;
        if (!$scanCompleted) {
            // We haven't reached the time cutoff yet
            if ($messagesScanned >= $maxMessagesPerRequest || $iterations >= $maxIterationsPerRequest) {
                // We hit our processing limit, more messages may exist
                $hasMore = true;
            }
        }
        
        return [
            'polls' => array_slice($polls, 0, $limit),
            'total' => count($polls),
            'messages_scanned' => $messagesScanned,
            'scan_completed' => $scanCompleted,
            'has_more' => $hasMore,
            'last_message_id' => $offsetId,
            'cutoff_timestamp' => $cutoffDate->timestamp // Include cutoff for reference
        ];
    }

    private function formatPoll(array $message): ?array
    {
        if (!isset($message['media']['poll'])) {
            return null;
        }
        
        $pollData = $message['media']['poll'];
        $results = $message['media']['results'] ?? null;
        
        // Extract question text
        $question = 'N/A';
        if (isset($pollData['question'])) {
            if (is_array($pollData['question']) && isset($pollData['question']['text'])) {
                $question = $pollData['question']['text'];
            } elseif (is_string($pollData['question'])) {
                $question = $pollData['question'];
            }
        }
        
        // Extract answers
        $answers = [];
        if (isset($pollData['answers']) && is_array($pollData['answers'])) {
            foreach ($pollData['answers'] as $index => $answer) {
                $text = 'N/A';
                if (isset($answer['text'])) {
                    if (is_array($answer['text']) && isset($answer['text']['text'])) {
                        $text = $answer['text']['text'];
                    } elseif (is_string($answer['text'])) {
                        $text = $answer['text'];
                    }
                }
                
                $answerData = [
                    'text' => $text,
                    'index' => $index
                ];
                
                // Add vote data if available
                if ($results && isset($results['results'][$index])) {
                    $result = $results['results'][$index];
                    $answerData['voters'] = $result['voters'] ?? 0;
                    // Don't expose what the user voted for privacy
                    $answerData['chosen'] = false;
                    
                    if (isset($results['total_voters']) && $results['total_voters'] > 0) {
                        $answerData['percentage'] = round(
                            ($answerData['voters'] / $results['total_voters']) * 100, 
                            1
                        );
                    } else {
                        $answerData['percentage'] = 0;
                    }
                }
                
                $answers[] = $answerData;
            }
        }
        
        // Check if results are visible
        $resultsVisible = true;
        if (!$results || !isset($results['results'])) {
            $resultsVisible = false;
        }
        
        // Check if message is forwarded
        $forwardedInfo = null;
        if (isset($message['fwd_from'])) {
            $forwardedInfo = [
                'is_forwarded' => true,
                'original_date' => null,
                'from_name' => null,
                'from_id' => null,
                'from_type' => null,
                'from_username' => null
            ];
            
            // Get original date
            if (isset($message['fwd_from']['date'])) {
                $forwardedInfo['original_date'] = Carbon::createFromTimestamp($message['fwd_from']['date'])->toIso8601String();
            }
            
            // Get source information
            if (isset($message['fwd_from']['from_id'])) {
                $fromId = $message['fwd_from']['from_id'];
                
                // Determine if it's a channel or user ID
                // In MadelineProto, channel IDs are typically larger numbers
                if (is_numeric($fromId)) {
                    // Channel IDs in Telegram are typically > 1000000000
                    if ($fromId > 1000000000) {
                        $forwardedInfo['from_id'] = $fromId;
                        $forwardedInfo['from_type'] = 'channel';
                    } else {
                        $forwardedInfo['from_id'] = $fromId;
                        $forwardedInfo['from_type'] = 'user';
                    }
                }
            }
            
            // Get channel/user name if available
            if (isset($message['fwd_from']['from_name'])) {
                $forwardedInfo['from_name'] = $message['fwd_from']['from_name'];
            }
            
            // Post author (for channels with signatures)
            if (isset($message['fwd_from']['post_author'])) {
                $forwardedInfo['post_author'] = $message['fwd_from']['post_author'];
            }
            
            // Try to get channel username for creating links
            if ($forwardedInfo['from_type'] === 'channel' && $forwardedInfo['from_id']) {
                try {
                    // Get channel info to retrieve username
                    $channelInfo = $this->telegramClient->getInfo($forwardedInfo['from_id']);
                    
                    if ($channelInfo) {
                        // Check for username in different possible locations
                        $username = null;
                        if (isset($channelInfo['Chat']['username'])) {
                            $username = $channelInfo['Chat']['username'];
                        } elseif (isset($channelInfo['User']['username'])) {
                            $username = $channelInfo['User']['username'];
                        } elseif (isset($channelInfo['username'])) {
                            $username = $channelInfo['username'];
                        }
                        
                        if ($username) {
                            $forwardedInfo['from_username'] = $username;
                        }
                    }
                } catch (\Exception $e) {
                    // If we can't get the channel info, just log and continue
                    Log::debug('Could not fetch channel info for forwarded poll', [
                        'channel_id' => $forwardedInfo['from_id'],
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
        
        return [
            'message_id' => $message['id'],
            'date' => Carbon::createFromTimestamp($message['date'])->toIso8601String(),
            'question' => $question,
            'closed' => $pollData['closed'] ?? false,
            'public_voters' => $pollData['public_voters'] ?? false,
            'multiple_choice' => $pollData['multiple_choice'] ?? false,
            'quiz' => $pollData['quiz'] ?? false,
            'answers' => $answers,
            'total_voters' => $results['total_voters'] ?? 0,
            'message_text' => mb_substr($message['message'] ?? '', 0, 200),
            'results_visible' => $resultsVisible,
            'forwarded_info' => $forwardedInfo
        ];
    }

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

    private function normalizeUsername(string $username): string
    {
        return ltrim(strtolower($username), '@');
    }

    /**
     * Get cache metadata for polls request
     */
    public function getCacheMetadataForPolls(string $channelUsername, string $period, int $limit): array
    {
        $channelUsername = $this->normalizeUsername($channelUsername);
        $cacheKey = "polls_{$channelUsername}_{$period}_{$limit}";
        return $this->getCacheMetadata($cacheKey, $this->cacheTtl);
    }

    /**
     * Get cache metadata for single poll request
     */
    public function getCacheMetadataForPoll(string $channelUsername, int $messageId): array
    {
        $channelUsername = $this->normalizeUsername($channelUsername);
        $cacheKey = "poll_{$channelUsername}_{$messageId}";
        return $this->getCacheMetadata($cacheKey, $this->cacheTtl * 2);
    }
}