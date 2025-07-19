<?php

namespace App\Services\Telegram\Statistics;

use Carbon\Carbon;

class TopContributorsCalculator
{
    private array $config;
    private array $userInfo = [];

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'weights' => [
                'message_frequency' => 0.15,     // How often user posts
                'engagement_received' => 0.25,   // Replies/reactions received
                'content_quality' => 0.20,       // Quality of content
                'response_speed' => 0.10,        // How quickly they respond
                'conversation_starter' => 0.15,  // Initiates conversations
                'consistency' => 0.10,           // Regular participation
                'helpfulness' => 0.05            // Helpful responses
            ],
            'thresholds' => [
                'optimal_message_length' => [30, 500],  // Optimal length range
                'spam_length_threshold' => 2000,        // Too long = possible spam
                'quick_response_minutes' => 10,         // Quick response threshold
                'max_daily_messages' => 100,            // Max daily without penalty
                'min_message_gap_minutes' => 1          // Minimum gap between messages
            ]
        ], $config);
    }

    /**
     * Calculate user values for the specified time period
     */
    public function calculateUserValues(array $messages, int $days = 7, array $userInfoCache = []): array
    {
        $endDate = now();
        $startDate = $endDate->copy()->subDays($days);

        // Filter messages within the period
        $periodMessages = $this->filterMessagesByPeriod($messages, $startDate, $endDate);

        // Group by user with user info cache
        $userMessages = $this->groupMessagesByUser($periodMessages, $userInfoCache);

        // Calculate metrics for each user
        $userMetrics = [];
        foreach ($userMessages as $userId => $messages) {
            $userMetrics[$userId] = $this->calculateUserMetrics($userId, $messages, $periodMessages, $days);
        }

        // Calculate final scores
        $userScores = $this->calculateFinalScores($userMetrics);

        // Sort by score descending
        arsort($userScores);

        return [
            'period' => [
                'start' => $startDate->toISOString(),
                'end' => $endDate->toISOString(),
                'days' => $days
            ],
            'user_rankings' => $this->formatUserRankings($userScores, $userMetrics),
            'summary' => $this->calculateSummary($userMetrics, $userScores)
        ];
    }

    /**
     * Calculate all metrics for a specific user
     */
    private function calculateUserMetrics(string $userId, array $userMessages, array $allMessages, int $days): array
    {
        $metrics = [
            'user_id' => $userId,
            'total_messages' => count($userMessages),
            'avg_message_length' => $this->calculateAverageLength($userMessages),
            'message_frequency_score' => $this->calculateFrequencyScore($userMessages, $days),
            'engagement_received_score' => $this->calculateEngagementReceived($userId, $allMessages),
            'content_quality_score' => $this->calculateContentQuality($userMessages),
            'response_speed_score' => $this->calculateResponseSpeed($userMessages, $allMessages),
            'conversation_starter_score' => $this->calculateConversationStarter($userMessages, $allMessages),
            'consistency_score' => $this->calculateConsistency($userMessages, $days),
            'helpfulness_score' => $this->calculateHelpfulness($userMessages, $allMessages),
            'unique_interactions_score' => $this->calculateUniqueInteractions($userMessages, $allMessages),
            'peak_hours_activity' => $this->calculatePeakHoursActivity($userMessages)
        ];

        return $metrics;
    }

    /**
     * Calculate message frequency score
     * Optimal: 3-15 messages per day
     */
    private function calculateFrequencyScore(array $messages, int $days): float
    {
        $messageCount = count($messages);
        $dailyAverage = $messageCount / $days;

        if ($dailyAverage < 0.5) return $dailyAverage * 2; // Very inactive (more generous)
        if ($dailyAverage <= 2) return 0.5 + ($dailyAverage - 0.5) * 0.33; // 0.5-1.0
        if ($dailyAverage <= 25) return 1.0; // Optimal range (extended)
        if ($dailyAverage <= 50) return 1.0 - (($dailyAverage - 25) * 0.01); // Very slight penalty

        return 0.75; // Potential spam penalty (more lenient)
    }

    /**
     * Calculate engagement received (replies and reactions)
     */
    private function calculateEngagementReceived(string $userId, array $allMessages): float
    {
        $userMessages = array_filter($allMessages, fn($msg) => $this->getUserId($msg) === $userId);
        $totalReplies = 0;
        $totalReactions = 0;
        $uniqueEngagers = [];

        foreach ($userMessages as $message) {
            $messageId = $message['id'] ?? null;
            if ($messageId) {
                // Count replies to this message
                $replies = array_filter($allMessages, function($msg) use ($messageId) {
                    return isset($msg['reply_to']) &&
                           ($msg['reply_to']['reply_to_msg_id'] ?? null) === $messageId;
                });

                foreach ($replies as $reply) {
                    $totalReplies++;
                    $replyUserId = $this->getUserId($reply);
                    if (!in_array($replyUserId, $uniqueEngagers)) {
                        $uniqueEngagers[] = $replyUserId;
                    }
                }
            }

            // Count reactions
            if (isset($message['reactions']['results'])) {
                foreach ($message['reactions']['results'] as $reaction) {
                    $totalReactions += $reaction['count'] ?? 0;
                }
            }
        }

        $messageCount = count($userMessages);
        if ($messageCount === 0) return 0;

        $replyRate = $totalReplies / $messageCount;
        $reactionRate = $totalReactions / $messageCount;
        $diversityBonus = min(count($uniqueEngagers) / 10, 1.0); // Bonus for diverse engagement

        // Normalize scores
        $replyScore = min($replyRate / 0.5, 1.0);
        $reactionScore = min($reactionRate / 2.0, 1.0);

        return ($replyScore * 0.5) + ($reactionScore * 0.3) + ($diversityBonus * 0.2);
    }

    /**
     * Calculate content quality based on message characteristics
     */
    private function calculateContentQuality(array $messages): float
    {
        if (empty($messages)) return 0;

        $qualityScores = [];
        [$optimalMin, $optimalMax] = $this->config['thresholds']['optimal_message_length'];
        $spamThreshold = $this->config['thresholds']['spam_length_threshold'];

        foreach ($messages as $message) {
            $text = $message['message'] ?? '';
            $length = mb_strlen($text);

            // Base score from length (more permissive)
            if ($length < 5) {
                $score = 0.4; // Very short messages (still valuable)
            } elseif ($length >= $optimalMin && $length <= $optimalMax) {
                $score = 1.0; // Optimal length
            } elseif ($length < $optimalMin) {
                $score = 0.6 + (($length - 5) / ($optimalMin - 5)) * 0.4;
            } elseif ($length <= $spamThreshold) {
                $score = 1.0 - (($length - $optimalMax) / ($spamThreshold - $optimalMax)) * 0.3;
            } else {
                $score = 0.4; // Long messages still have some value
            }

            // Additional quality factors
            $hasLinks = preg_match('/https?:\/\//', $text);
            $hasMedia = isset($message['media']);
            $isForwarded = isset($message['fwd_from']);

            // Bonus for original content with media/links
            if (!$isForwarded && ($hasLinks || $hasMedia)) {
                $score *= 1.2;
            }

            // Penalty for excessive forwarding
            if ($isForwarded) {
                $score *= 0.7;
            }

            $qualityScores[] = min($score, 1.0);
        }

        return array_sum($qualityScores) / count($qualityScores);
    }

    /**
     * Calculate response speed to other messages
     */
    private function calculateResponseSpeed(array $userMessages, array $allMessages): float
    {
        $responseTimes = [];
        $quickResponseThreshold = $this->config['thresholds']['quick_response_minutes'] * 60;

        foreach ($userMessages as $message) {
            if (!isset($message['reply_to'])) continue;

            $replyToId = $message['reply_to']['reply_to_msg_id'] ?? null;
            if (!$replyToId) continue;

            // Find the original message
            $originalMessage = null;
            foreach ($allMessages as $msg) {
                if (($msg['id'] ?? null) === $replyToId) {
                    $originalMessage = $msg;
                    break;
                }
            }

            if (!$originalMessage) continue;

            $responseTime = ($message['date'] ?? 0) - ($originalMessage['date'] ?? 0);
            if ($responseTime > 0 && $responseTime < 86400) { // Within 24 hours
                $responseTimes[] = $responseTime;
            }
        }

        if (empty($responseTimes)) return 0.5; // Neutral if no responses

        $avgResponseTime = array_sum($responseTimes) / count($responseTimes);

        // Score calculation
        if ($avgResponseTime <= $quickResponseThreshold) {
            return 1.0;
        } elseif ($avgResponseTime <= $quickResponseThreshold * 4) {
            return 1.0 - (($avgResponseTime - $quickResponseThreshold) / ($quickResponseThreshold * 3)) * 0.5;
        } else {
            return 0.5;
        }
    }

    /**
     * Calculate conversation starter score
     */
    private function calculateConversationStarter(array $userMessages, array $allMessages): float
    {
        $starterCount = 0;
        $totalMessages = count($userMessages);

        if ($totalMessages === 0) return 0;

        foreach ($userMessages as $message) {
            if (!isset($message['reply_to'])) {
                $messageTime = $message['date'] ?? 0;
                $recentActivity = false;

                // Check for recent activity before this message
                foreach ($allMessages as $otherMsg) {
                    $otherTime = $otherMsg['date'] ?? 0;
                    $timeDiff = $messageTime - $otherTime;

                    // If activity within 60 minutes, not a conversation starter (more permissive)
                    if ($timeDiff > 0 && $timeDiff <= 3600) {
                        $recentActivity = true;
                        break;
                    }
                }

                if (!$recentActivity) {
                    $starterCount++;
                }
            }
        }

        $starterRatio = $starterCount / max($totalMessages * 0.3, 1);
        return min($starterRatio, 1.0);
    }

    /**
     * Calculate consistency of participation
     */
    private function calculateConsistency(array $messages, int $days): float
    {
        if (empty($messages)) return 0;

        // Group messages by day
        $dailyMessages = [];
        foreach ($messages as $message) {
            $date = Carbon::createFromTimestamp($message['date'] ?? 0)->format('Y-m-d');
            $dailyMessages[$date] = ($dailyMessages[$date] ?? 0) + 1;
        }

        $activeDays = count($dailyMessages);
        $consistencyRatio = $activeDays / $days;

        // Bonus for uniform distribution
        if ($activeDays > 1) {
            $messageValues = array_values($dailyMessages);
            $mean = array_sum($messageValues) / count($messageValues);
            $variance = array_sum(array_map(fn($x) => pow($x - $mean, 2), $messageValues)) / count($messageValues);
            $cv = $variance > 0 ? sqrt($variance) / $mean : 0;

            // Lower coefficient of variation = more consistent
            $uniformityBonus = max(0, 1 - $cv);
        } else {
            $uniformityBonus = 0;
        }

        return ($consistencyRatio * 0.7) + ($uniformityBonus * 0.3);
    }

    /**
     * Calculate helpfulness of responses
     */
    private function calculateHelpfulness(array $userMessages, array $allMessages): float
    {
        $helpfulResponses = 0;
        $totalResponses = 0;

        foreach ($userMessages as $message) {
            if (!isset($message['reply_to'])) continue;

            $totalResponses++;

            // Heuristics for helpful responses
            $messageText = strtolower($message['message'] ?? '');
            $length = mb_strlen($messageText);

            // Helpful indicators
            $helpfulPatterns = [
                'thanks', 'thank you', 'yes', 'no', 'here', 'link', 'http', '@',
                'think', 'recommend', 'suggest', 'try', 'solution', 'help',
                '?', '!', 'because', 'reason'
            ];

            $hasHelpfulContent = false;
            foreach ($helpfulPatterns as $pattern) {
                if (strpos($messageText, $pattern) !== false) {
                    $hasHelpfulContent = true;
                    break;
                }
            }

            // Score based on length and content
            if ($length >= 30 && $length <= 500 && $hasHelpfulContent) {
                $helpfulResponses++;
            } elseif ($length >= 20 && $hasHelpfulContent) {
                $helpfulResponses += 0.5;
            }
        }

        return $totalResponses > 0 ? min($helpfulResponses / $totalResponses, 1.0) : 0.5;
    }

    /**
     * Calculate unique interactions (diversity of people interacted with)
     */
    private function calculateUniqueInteractions(array $userMessages, array $allMessages): float
    {
        $interactedUsers = [];

        foreach ($userMessages as $message) {
            // Check replies from this user
            if (isset($message['reply_to'])) {
                $replyToId = $message['reply_to']['reply_to_msg_id'] ?? null;
                if ($replyToId) {
                    foreach ($allMessages as $msg) {
                        if (($msg['id'] ?? null) === $replyToId) {
                            $otherUserId = $this->getUserId($msg);
                            if (!in_array($otherUserId, $interactedUsers)) {
                                $interactedUsers[] = $otherUserId;
                            }
                            break;
                        }
                    }
                }
            }
        }

        // Normalize: 10+ unique interactions = perfect score
        return min(count($interactedUsers) / 10, 1.0);
    }

    /**
     * Calculate peak hours activity (engagement during active hours)
     */
    private function calculatePeakHoursActivity(array $messages): float
    {
        if (empty($messages)) return 0;

        $hourlyDistribution = array_fill(0, 24, 0);

        foreach ($messages as $message) {
            $hour = Carbon::createFromTimestamp($message['date'] ?? 0)->hour;
            $hourlyDistribution[$hour]++;
        }

        // Define peak hours (9 AM - 10 PM)
        $peakHours = range(9, 22);
        $peakMessages = 0;
        $totalMessages = count($messages);

        foreach ($peakHours as $hour) {
            $peakMessages += $hourlyDistribution[$hour];
        }

        return $totalMessages > 0 ? $peakMessages / $totalMessages : 0;
    }

    /**
     * Calculate final weighted scores
     */
    private function calculateFinalScores(array $userMetrics): array
    {
        $scores = [];
        $weights = $this->config['weights'];

        foreach ($userMetrics as $userId => $metrics) {
            $score = 0;
            $score += $metrics['message_frequency_score'] * $weights['message_frequency'];
            $score += $metrics['engagement_received_score'] * $weights['engagement_received'];
            $score += $metrics['content_quality_score'] * $weights['content_quality'];
            $score += $metrics['response_speed_score'] * $weights['response_speed'];
            $score += $metrics['conversation_starter_score'] * $weights['conversation_starter'];
            $score += $metrics['consistency_score'] * $weights['consistency'];
            $score += $metrics['helpfulness_score'] * $weights['helpfulness'];

            // Additional metrics with implicit weights
            $score += $metrics['unique_interactions_score'] * 0.05;
            $score += $metrics['peak_hours_activity'] * 0.03;

            $scores[$userId] = round($score * 100, 2); // Score 0-100
        }

        return $scores;
    }

    /**
     * Format user rankings for output
     */
    private function formatUserRankings(array $userScores, array $userMetrics): array
    {
        $rankings = [];
        $rank = 1;

        foreach ($userScores as $userId => $score) {
            $metrics = $userMetrics[$userId];
            $userDisplay = $this->userInfo[$userId] ?? [
                'user_id' => $userId,
                'username' => null,
                'display_name' => 'User ' . substr($userId, -6)
            ];
            
            $rankings[] = [
                'rank' => $rank++,
                'user_id' => $userId,
                'username' => $userDisplay['username'] ?? null,
                'display_name' => $userDisplay['display_name'] ?? null,
                'user_name' => $userDisplay['username'] ? '@' . $userDisplay['username'] : $userDisplay['display_name'],
                'total_score' => $score,
                'metrics' => [
                    'total_messages' => $metrics['total_messages'],
                    'avg_message_length' => $metrics['avg_message_length'],
                    'message_frequency' => round($metrics['message_frequency_score'] * 100, 1),
                    'engagement_received' => round($metrics['engagement_received_score'] * 100, 1),
                    'content_quality' => round($metrics['content_quality_score'] * 100, 1),
                    'response_speed' => round($metrics['response_speed_score'] * 100, 1),
                    'conversation_starter' => round($metrics['conversation_starter_score'] * 100, 1),
                    'consistency' => round($metrics['consistency_score'] * 100, 1),
                    'helpfulness' => round($metrics['helpfulness_score'] * 100, 1),
                    'unique_interactions' => round($metrics['unique_interactions_score'] * 100, 1),
                    'peak_hours_activity' => round($metrics['peak_hours_activity'] * 100, 1)
                ],
                'category' => $this->categorizeUser($score, $metrics),
                'badges' => $this->assignBadges($metrics)
            ];
        }

        return $rankings;
    }

    /**
     * Categorize user based on their score and behavior
     */
    private function categorizeUser(float $score, array $metrics): string
    {
        if ($score >= 70) return 'Community Leader';      // Lowered from 80
        if ($score >= 55) return 'Active Contributor';    // Lowered from 65
        if ($score >= 40) return 'Regular Member';        // Lowered from 50
        if ($score >= 25) return 'Casual Participant';    // Lowered from 30
        return 'Observer';
    }

    /**
     * Assign special badges based on outstanding metrics
     */
    private function assignBadges(array $metrics): array
    {
        $badges = [];

        if ($metrics['response_speed_score'] >= 0.9) {
            $badges[] = 'Lightning Responder';
        }
        if ($metrics['conversation_starter_score'] >= 0.8) {
            $badges[] = 'Conversation Catalyst';
        }
        if ($metrics['helpfulness_score'] >= 0.8) {
            $badges[] = 'Helpful Hero';
        }
        if ($metrics['consistency_score'] >= 0.9) {
            $badges[] = 'Daily Devotee';
        }
        if ($metrics['engagement_received_score'] >= 0.85) {
            $badges[] = 'Engagement Magnet';
        }
        if ($metrics['unique_interactions_score'] >= 0.8) {
            $badges[] = 'Social Butterfly';
        }

        return $badges;
    }

    /**
     * Calculate summary statistics
     */
    private function calculateSummary(array $userMetrics, array $userScores): array
    {
        $totalUsers = count($userScores);
        $avgScore = $totalUsers > 0 ? array_sum($userScores) / $totalUsers : 0;

        $categories = [
            'Community Leader' => 0,
            'Active Contributor' => 0,
            'Regular Member' => 0,
            'Casual Participant' => 0,
            'Observer' => 0
        ];

        foreach ($userScores as $userId => $score) {
            $category = $this->categorizeUser($score, $userMetrics[$userId]);
            $categories[$category]++;
        }

        return [
            'total_users_analyzed' => $totalUsers,
            'average_score' => round($avgScore, 2),
            'score_distribution' => $categories,
            'top_performer' => $totalUsers > 0 ? [
                'user_id' => array_key_first($userScores),
                'score' => array_values($userScores)[0] ?? 0
            ] : null,
            'engagement_health' => $this->calculateEngagementHealth($avgScore, $categories)
        ];
    }

    /**
     * Calculate overall engagement health of the group
     */
    private function calculateEngagementHealth(float $avgScore, array $categories): string
    {
        $activeRatio = ($categories['Community Leader'] + $categories['Active Contributor']) /
                       max(array_sum($categories), 1);

        if ($avgScore >= 60 && $activeRatio >= 0.3) return 'Excellent';
        if ($avgScore >= 50 && $activeRatio >= 0.2) return 'Good';
        if ($avgScore >= 40 && $activeRatio >= 0.1) return 'Fair';
        if ($avgScore >= 30) return 'Needs Improvement';
        return 'Poor';
    }

    /**
     * Filter messages by time period
     */
    private function filterMessagesByPeriod(array $messages, Carbon $start, Carbon $end): array
    {
        return array_filter($messages, function($message) use ($start, $end) {
            $messageTime = Carbon::createFromTimestamp($message['date'] ?? 0);
            return $messageTime->between($start, $end);
        });
    }

    /**
     * Group messages by user ID
     */
    private function groupMessagesByUser(array $messages, array $userInfoCache = []): array
    {
        $userMessages = [];
        $userInfo = [];
        
        foreach ($messages as $message) {
            $userId = $this->getUserId($message);
            if ($userId !== 'unknown') {
                $userMessages[$userId][] = $message;
                
                // Store user display information
                if (!isset($userInfo[$userId])) {
                    // First check if we have info in the cache
                    if (isset($userInfoCache[$userId])) {
                        $cachedInfo = $userInfoCache[$userId];
                        $userInfo[$userId] = [
                            'user_id' => $userId,
                            'username' => $cachedInfo['username'] ?? null,
                            'display_name' => $cachedInfo['username'] ? '@' . $cachedInfo['username'] : 
                                            trim(($cachedInfo['first_name'] ?? '') . ' ' . ($cachedInfo['last_name'] ?? ''))
                        ];
                    } else {
                        // Fallback to extracting from message
                        $userInfo[$userId] = $this->getUserDisplay($message);
                    }
                }
            }
        }
        
        // Store user info for later use
        $this->userInfo = $userInfo;
        
        return $userMessages;
    }

    /**
     * Extract user ID from message
     */
    private function getUserId(array $message): string
    {
        if (isset($message['from_id'])) {
            if (is_array($message['from_id'])) {
                return $message['from_id']['user_id'] ??
                       $message['from_id']['channel_id'] ??
                       'unknown';
            }
            return (string) $message['from_id'];
        }
        return 'unknown';
    }
    
    /**
     * Extract username or display name from message
     */
    private function getUserDisplay(array $message): array
    {
        $userId = $this->getUserId($message);
        $username = null;
        $firstName = null;
        $lastName = null;
        
        // Debug: Log message structure to understand data format
        \Log::debug('Message structure for user display', [
            'has_from' => isset($message['from']),
            'has_from_' => isset($message['from_']),
            'from_id' => $message['from_id'] ?? null,
            'message_sample' => array_slice($message, 0, 5)
        ]);
        
        // Try multiple possible locations for user info
        // 1. Standard 'from' field
        if (isset($message['from'])) {
            $username = $message['from']['username'] ?? null;
            $firstName = $message['from']['first_name'] ?? null;
            $lastName = $message['from']['last_name'] ?? null;
        }
        // 2. Alternative 'from_' field
        elseif (isset($message['from_'])) {
            $username = $message['from_']['username'] ?? null;
            $firstName = $message['from_']['first_name'] ?? null;
            $lastName = $message['from_']['last_name'] ?? null;
        }
        // 3. Check if user info is at message root level
        else {
            $username = $message['username'] ?? null;
            $firstName = $message['first_name'] ?? null;
            $lastName = $message['last_name'] ?? null;
        }
        
        // Build display name
        $displayName = null;
        if ($username) {
            $displayName = '@' . $username;
        } elseif ($firstName || $lastName) {
            $displayName = trim(($firstName ?? '') . ' ' . ($lastName ?? ''));
        }
        
        return [
            'user_id' => $userId,
            'username' => $username,
            'display_name' => $displayName ?: 'User ' . substr($userId, -6)
        ];
    }

    /**
     * Calculate average message length
     */
    private function calculateAverageLength(array $messages): float
    {
        if (empty($messages)) return 0;

        $totalLength = array_sum(array_map(function($msg) {
            return mb_strlen($msg['message'] ?? '');
        }, $messages));

        return round($totalLength / count($messages), 1);
    }
}
