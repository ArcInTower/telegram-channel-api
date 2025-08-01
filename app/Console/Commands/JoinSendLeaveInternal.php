<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Telegram\MessageService;
use App\Services\Telegram\ChannelService;

class JoinSendLeaveInternal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:join-send-leave-internal 
                            {channel : The channel username or ID}
                            {message : The message to send}
                            {--no-leave : Don\'t leave the channel after sending the message}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Internal command to join, send, and leave';

    /**
     * Hide this command from the list
     *
     * @var bool
     */
    protected $hidden = true;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        private MessageService $messageService,
        private ChannelService $channelService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Configure MadelineProto to suppress logs before any initialization
        putenv('MADELINE_SUPPRESS_LOGS=true');
        
        $channel = $this->argument('channel');
        $message = $this->argument('message');
        $noLeave = $this->option('no-leave');

        // Suppress any remaining output
        ob_start();
        
        try {
            // Step 1: Join the channel
            echo "Joining channel...\n";
            ob_end_clean();
            
            $joined = $this->channelService->joinChannel($channel);
            if (!$joined) {
                echo "Error: Failed to join channel\n";
                return Command::FAILURE;
            }
            
            ob_start();
            echo "Joined successfully\n";
            
            // Step 2: Send the message
            echo "Sending message...\n";
            ob_end_clean();
            
            $result = $this->messageService->sendMessage($channel, $message);
            
            ob_start();
            if ($result === null) {
                echo "Error: Failed to send message\n";
                return Command::FAILURE;
            }
            
            $messageId = is_array($result) && isset($result['id']) ? $result['id'] : 'unknown';
            echo "Message sent successfully (ID: {$messageId})\n";
            
            // Step 3: Leave the channel (if requested)
            if (!$noLeave) {
                echo "Leaving channel...\n";
                ob_end_clean();
                
                $left = $this->channelService->leaveChannel($channel);
                
                ob_start();
                if ($left) {
                    echo "Left channel successfully\n";
                } else {
                    echo "Warning: Could not leave channel\n";
                }
            }
            
            ob_end_clean();
            echo "Operation completed successfully";
            
            return Command::SUCCESS;

        } catch (\Exception $e) {
            ob_end_clean();
            echo "Error: " . $e->getMessage() . "\n";
            return Command::FAILURE;
        }
    }
}