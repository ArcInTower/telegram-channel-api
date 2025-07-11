<?php

namespace App\Services\Telegram\Statistics;

use Carbon\Carbon;

class StatisticsCalculator
{
    public function calculate(array $messages, Carbon $startDate, Carbon $endDate, array $userInfoCache = []): array
    {
        $stats = new StatisticsData;

        foreach ($messages as $message) {
            if ($this->isServiceMessage($message)) {
                continue;
            }

            $this->processMessage($message, $stats, $userInfoCache);
        }

        return $this->formatStatistics($stats, $startDate, $endDate);
    }

    private function isServiceMessage(array $message): bool
    {
        return isset($message['_']) && $message['_'] === 'messageService';
    }

    private function processMessage(array $message, StatisticsData $stats, array $userInfoCache): void
    {
        $stats->incrementTotalMessages();

        $userId = $this->extractUserId($message);
        $userName = $this->extractUserName($message, $userId, $userInfoCache);

        $stats->addUserName($userId, $userName);
        $stats->incrementUserMessageCount($userId);

        if (isset($message['message'])) {
            $textLength = mb_strlen($message['message']);
            $stats->addUserTextLength($userId, $textLength);
            $stats->addTotalTextLength($textLength);
        }

        if (isset($message['reply_to'])) {
            $stats->incrementUserReplyCount($userId);
            $stats->incrementTotalReplies();
        }

        $this->processTimeStatistics($message, $stats, $userId);
    }

    private function extractUserId(array $message): string
    {
        if (isset($message['from_id'])) {
            if (is_array($message['from_id'])) {
                return $message['from_id']['user_id'] ?? $message['from_id']['channel_id'] ?? 'unknown';
            }

            return $message['from_id'];
        } elseif (isset($message['peer_id']['channel_id'])) {
            return 'channel_' . $message['peer_id']['channel_id'];
        }

        return 'unknown';
    }

    private function extractUserName(array $message, string $userId, array $userInfoCache): string
    {
        if (isset($message['from_name'])) {
            return $message['from_name'];
        } elseif (isset($message['post_author'])) {
            return $message['post_author'];
        } elseif (isset($userInfoCache[$userId])) {
            $userInfo = $userInfoCache[$userId];
            if (!empty($userInfo['username'])) {
                return '@' . $userInfo['username'];
            }

            return trim($userInfo['first_name'] . ' ' . $userInfo['last_name']);
        } elseif (strpos($userId, 'channel_') === 0) {
            return 'Channel Post';
        }

        return 'Unknown';
    }

    private function processTimeStatistics(array $message, StatisticsData $stats, string $userId): void
    {
        if (!isset($message['date'])) {
            return;
        }

        $messageTime = Carbon::createFromTimestamp($message['date']);
        $hour = $messageTime->hour;
        $weekday = $messageTime->dayOfWeek;
        $dateKey = $messageTime->format('Y-m-d');

        $stats->incrementHourlyStats($hour);
        $stats->incrementWeekdayStats($weekday);
        $stats->incrementDailyStats($dateKey);
        $stats->incrementUserHourlyStats($userId, $hour);
        $stats->incrementUserWeekdayStats($userId, $weekday);
    }

    private function formatStatistics(StatisticsData $stats, Carbon $startDate, Carbon $endDate): array
    {
        $userStats = $stats->getUserStats();
        $topUsers = $this->getTopUsers($userStats, $stats->getUserNames());

        return [
            'period' => [
                'start' => $startDate->toISOString(),
                'end' => $endDate->toISOString(),
                'days' => (int) round($startDate->diffInDays($endDate)),
            ],
            'summary' => [
                'total_messages' => $stats->getTotalMessages(),
                'active_users' => count($userStats),
                'total_replies' => $stats->getTotalReplies(),
                'reply_rate' => $stats->getReplyRate(),
                'average_messages_per_user' => $stats->getAverageMessagesPerUser(),
                'average_message_length' => $stats->getAverageMessageLength(),
            ],
            'top_users' => $topUsers,
            'activity_patterns' => [
                'by_hour' => $this->formatHourlyStats($stats->getHourlyStats()),
                'by_weekday' => $this->formatWeekdayStats($stats->getWeekdayStats()),
                'by_date' => $stats->getDailyStats(),
            ],
            'peak_activity' => [
                'hour' => $stats->getPeakHour() . ':00',
                'weekday' => $this->getWeekdayName($stats->getPeakWeekday()),
            ],
        ];
    }

    private function getTopUsers(array $userStats, array $userNames): array
    {
        uasort($userStats, function ($a, $b) {
            return $b['message_count'] - $a['message_count'];
        });

        $topUsers = array_slice($userStats, 0, 10, true);
        $formattedUsers = [];

        foreach ($topUsers as $userId => $stats) {
            $formattedUsers[] = [
                'user_name' => $userNames[$userId] ?? 'Unknown',
                'message_count' => $stats['message_count'],
                'average_message_length' => $stats['message_count'] > 0
                    ? round($stats['total_text_length'] / $stats['message_count'], 2)
                    : 0,
                'reply_count' => $stats['reply_count'],
                'most_active_hour' => array_search(max($stats['messages_by_hour']), $stats['messages_by_hour']),
                'most_active_weekday' => $this->getWeekdayName(array_search(max($stats['messages_by_weekday']), $stats['messages_by_weekday'])),
            ];
        }

        return $formattedUsers;
    }

    private function formatHourlyStats(array $hourlyStats): array
    {
        $formatted = [];
        foreach ($hourlyStats as $hour => $count) {
            $formatted[sprintf('%02d:00', $hour)] = $count;
        }

        return $formatted;
    }

    private function formatWeekdayStats(array $weekdayStats): array
    {
        $formatted = [];
        foreach ($weekdayStats as $day => $count) {
            $formatted[$this->getWeekdayName($day)] = $count;
        }

        return $formatted;
    }

    private function getWeekdayName(int $day): string
    {
        $days = [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ];

        return $days[$day] ?? 'Unknown';
    }
}
