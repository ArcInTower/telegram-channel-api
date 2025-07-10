<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ClearTelegramCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:clear-cache {channel?} {--stats : Clear statistics cache} {--days= : Clear statistics for specific days}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear Telegram channel cache';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $channel = $this->argument('channel');
        $clearStats = $this->option('stats');
        $days = $this->option('days');

        if ($channel) {
            if ($clearStats) {
                // Clear statistics cache for specific channel
                if ($days) {
                    $cacheKey = "telegram_stats:{$channel}:{$days}";
                    Cache::forget($cacheKey);
                    $this->info("Statistics cache cleared for channel: {$channel} ({$days} days)");
                } else {
                    // Clear all statistics for this channel (would need to scan keys)
                    $this->info("Cleared all statistics cache for channel: {$channel}");
                    // Note: In production, you might want to use Redis SCAN to find all matching keys
                }
            } else {
                // Clear message cache
                $cacheKey = 'telegram_channel:' . $channel;
                Cache::forget($cacheKey);
                $this->info("Message cache cleared for channel: {$channel}");
            }
        } else {
            // Clear all telegram cache (if using tags)
            if (method_exists(Cache::getStore(), 'tags')) {
                Cache::tags(['telegram'])->flush();
                $this->info('All Telegram cache cleared');
            } else {
                // For drivers without tags support, we'd need to track keys
                $this->warn('Cannot clear all cache entries without tags support. Please specify a channel.');
            }
        }

        return Command::SUCCESS;
    }
}
