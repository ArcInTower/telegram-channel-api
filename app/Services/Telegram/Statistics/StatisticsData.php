<?php

namespace App\Services\Telegram\Statistics;

class StatisticsData
{
    private array $userStats = [];
    private array $userNames = [];
    private array $dailyStats = [];
    private array $hourlyStats;
    private array $weekdayStats;
    private int $totalMessages = 0;
    private int $totalReplies = 0;
    private int $totalTextLength = 0;

    public function __construct()
    {
        $this->hourlyStats = array_fill(0, 24, 0);
        $this->weekdayStats = array_fill(0, 7, 0);
    }

    public function incrementTotalMessages(): void
    {
        $this->totalMessages++;
    }

    public function incrementTotalReplies(): void
    {
        $this->totalReplies++;
    }

    public function addTotalTextLength(int $length): void
    {
        $this->totalTextLength += $length;
    }

    public function addUserName(string $userId, string $userName): void
    {
        if (!isset($this->userNames[$userId]) || $this->userNames[$userId] === 'Unknown') {
            $this->userNames[$userId] = $userName;
        }
    }

    public function incrementUserMessageCount(string $userId): void
    {
        $this->initializeUserStats($userId);
        $this->userStats[$userId]['message_count']++;
    }

    public function addUserTextLength(string $userId, int $length): void
    {
        $this->initializeUserStats($userId);
        $this->userStats[$userId]['total_text_length'] += $length;
    }

    public function incrementUserReplyCount(string $userId): void
    {
        $this->initializeUserStats($userId);
        $this->userStats[$userId]['reply_count']++;
    }

    public function incrementHourlyStats(int $hour): void
    {
        $this->hourlyStats[$hour]++;
    }

    public function incrementWeekdayStats(int $weekday): void
    {
        $this->weekdayStats[$weekday]++;
    }

    public function incrementDailyStats(string $date): void
    {
        if (!isset($this->dailyStats[$date])) {
            $this->dailyStats[$date] = 0;
        }
        $this->dailyStats[$date]++;
    }

    public function incrementUserHourlyStats(string $userId, int $hour): void
    {
        $this->initializeUserStats($userId);
        $this->userStats[$userId]['messages_by_hour'][$hour]++;
    }

    public function incrementUserWeekdayStats(string $userId, int $weekday): void
    {
        $this->initializeUserStats($userId);
        $this->userStats[$userId]['messages_by_weekday'][$weekday]++;
    }

    private function initializeUserStats(string $userId): void
    {
        if (!isset($this->userStats[$userId])) {
            $this->userStats[$userId] = [
                'message_count' => 0,
                'total_text_length' => 0,
                'reply_count' => 0,
                'messages_by_hour' => array_fill(0, 24, 0),
                'messages_by_weekday' => array_fill(0, 7, 0),
            ];
        }
    }

    public function getTotalMessages(): int
    {
        return $this->totalMessages;
    }

    public function getTotalReplies(): int
    {
        return $this->totalReplies;
    }

    public function getReplyRate(): float
    {
        return $this->totalMessages > 0
            ? round(($this->totalReplies / $this->totalMessages) * 100, 2)
            : 0;
    }

    public function getAverageMessagesPerUser(): float
    {
        $uniqueUsers = count($this->userStats);

        return $uniqueUsers > 0
            ? round($this->totalMessages / $uniqueUsers, 2)
            : 0;
    }

    public function getAverageMessageLength(): float
    {
        return $this->totalMessages > 0
            ? round($this->totalTextLength / $this->totalMessages, 2)
            : 0;
    }

    public function getUserStats(): array
    {
        return $this->userStats;
    }

    public function getUserNames(): array
    {
        return $this->userNames;
    }

    public function getHourlyStats(): array
    {
        return $this->hourlyStats;
    }

    public function getWeekdayStats(): array
    {
        return $this->weekdayStats;
    }

    public function getDailyStats(): array
    {
        return $this->dailyStats;
    }

    public function getPeakHour(): int
    {
        return array_search(max($this->hourlyStats), $this->hourlyStats);
    }

    public function getPeakWeekday(): int
    {
        return array_search(max($this->weekdayStats), $this->weekdayStats);
    }
}
