<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ManageChannelSecurity extends Command
{
    protected $signature = 'channel:security 
                            {action : The action to perform (block|unblock|list|whitelist)}
                            {channel? : The channel name}';

    protected $description = 'Manage channel security settings';

    public function handle()
    {
        $action = $this->argument('action');
        $channel = $this->argument('channel');

        switch ($action) {
            case 'block':
                $this->blockChannel($channel);
                break;
            case 'unblock':
                $this->unblockChannel($channel);
                break;
            case 'list':
                $this->listBlocked();
                break;
            case 'whitelist':
                $this->showWhitelist();
                break;
            default:
                $this->error('Invalid action. Use: block, unblock, list, or whitelist');
        }
    }

    private function blockChannel(?string $channel): void
    {
        if (!$channel) {
            $this->error('Please provide a channel name to block');
            return;
        }

        $channel = strtolower(ltrim($channel, '@'));
        $blocklist = Cache::get('channel_security_blocklist', []);
        
        if (!in_array($channel, $blocklist)) {
            $blocklist[] = $channel;
            Cache::put('channel_security_blocklist', $blocklist);
            $this->info("Channel '@{$channel}' has been blocked");
        } else {
            $this->warn("Channel '@{$channel}' is already blocked");
        }
    }

    private function unblockChannel(?string $channel): void
    {
        if (!$channel) {
            $this->error('Please provide a channel name to unblock');
            return;
        }

        $channel = strtolower(ltrim($channel, '@'));
        $blocklist = Cache::get('channel_security_blocklist', []);
        
        if (in_array($channel, $blocklist)) {
            $blocklist = array_values(array_diff($blocklist, [$channel]));
            Cache::put('channel_security_blocklist', $blocklist);
            $this->info("Channel '@{$channel}' has been unblocked");
        } else {
            $this->warn("Channel '@{$channel}' was not blocked");
        }
    }

    private function listBlocked(): void
    {
        $this->info('=== Blocked Channels ===');
        
        // Static blocklist from config
        $this->line("\nStatic Blocklist (from config):");
        $staticList = config('security.channels.blocklist', []);
        foreach ($staticList as $channel) {
            $this->line("  - @{$channel}");
        }
        
        // Dynamic blocklist from cache
        $this->line("\nDynamic Blocklist (from cache):");
        $dynamicList = Cache::get('channel_security_blocklist', []);
        if (empty($dynamicList)) {
            $this->line("  (none)");
        } else {
            foreach ($dynamicList as $channel) {
                $this->line("  - @{$channel}");
            }
        }
    }

    private function showWhitelist(): void
    {
        $enabled = config('security.channels.whitelist_enabled');
        
        $this->info('=== Whitelist Status ===');
        $this->line('Whitelist Mode: ' . ($enabled ? 'ENABLED' : 'DISABLED'));
        
        if ($enabled) {
            $this->line("\nAllowed Channels:");
            $whitelist = config('security.channels.whitelist', []);
            if (empty($whitelist)) {
                $this->warn("  (none) - No channels are accessible!");
            } else {
                foreach ($whitelist as $channel) {
                    $this->line("  - {$channel}");
                }
            }
            
            $this->line("\nTo add channels to whitelist, update CHANNEL_WHITELIST in .env");
            $this->line("Example: CHANNEL_WHITELIST=@channel1,@channel2,@channel3");
        } else {
            $this->line("\nTo enable whitelist mode, set CHANNEL_WHITELIST_ENABLED=true in .env");
        }
    }
}