<?php

namespace Tests\Feature\Commands;

use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ClearTelegramCacheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_clear_cache_for_specific_channel()
    {
        // Arrange - Add data to cache
        $channel = 'testchannel';
        Cache::put('telegram_channel:' . $channel, [
            'last_message_id' => 12345,
            'last_checked_at' => now()->toISOString(),
        ], 300);

        // Assert cache exists
        $this->assertNotNull(Cache::get('telegram_channel:' . $channel));

        // Act - Run command
        $this->artisan('telegram:clear-cache', ['channel' => $channel])
            ->expectsOutput("Message cache cleared for channel: {$channel}")
            ->assertExitCode(0);

        // Assert cache was cleared
        $this->assertNull(Cache::get('telegram_channel:' . $channel));
    }

    public function test_clear_statistics_cache_for_specific_channel()
    {
        // Arrange
        $channel = 'testchannel';
        $days = 7;
        $cacheKey = "telegram_stats:{$channel}:{$days}";

        Cache::put($cacheKey, ['some' => 'stats'], 3600);
        $this->assertNotNull(Cache::get($cacheKey));

        // Act
        $this->artisan('telegram:clear-cache', [
            'channel' => $channel,
            '--stats' => true,
            '--days' => $days,
        ])
            ->expectsOutput("Statistics cache cleared for channel: {$channel} ({$days} days)")
            ->assertExitCode(0);

        // Assert
        $this->assertNull(Cache::get($cacheKey));
    }

    public function test_clear_all_cache_runs_without_error()
    {
        // Test clearing all cache without specifying a channel
        $this->artisan('telegram:clear-cache')
            ->assertExitCode(0);
    }
}
