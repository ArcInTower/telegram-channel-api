<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Telegram\ChannelService;

class JoinChannel extends Command
{
    protected $signature = 'telegram:join-channel {channel : The channel username or ID}';
    protected $description = 'Join a Telegram channel/group';

    public function __construct(
        private ChannelService $channelService
    ) {
        parent::__construct();
    }

    public function handle()
    {
        $channel = $this->argument('channel');
        
        try {
            putenv('MADELINE_SUPPRESS_LOGS=true');
            ob_start();
            
            $result = $this->channelService->joinChannel($channel);
            
            ob_end_clean();
            
            if ($result) {
                $this->info("Joined channel successfully");
                return Command::SUCCESS;
            } else {
                $this->error("Failed to join channel");
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            ob_end_clean();
            $this->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}