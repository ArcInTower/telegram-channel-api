<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Telegram\ChannelService;

class LeaveChannel extends Command
{
    protected $signature = 'telegram:leave-channel {channel : The channel username or ID}';
    protected $description = 'Leave a Telegram channel/group';

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
            
            $result = $this->channelService->leaveChannel($channel);
            
            ob_end_clean();
            
            if ($result) {
                $this->info("Left channel successfully");
                return Command::SUCCESS;
            } else {
                $this->error("Failed to leave channel");
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            ob_end_clean();
            $this->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}