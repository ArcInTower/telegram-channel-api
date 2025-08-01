<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Telegram\MessageService;
use Carbon\Carbon;

class DebugMessagesByDate extends Command
{
    protected $signature = 'telegram:debug-messages 
                            {channel : The channel username or ID}
                            {from : Start date (YYYY-MM-DD)}
                            {to : End date (YYYY-MM-DD)}';

    protected $description = 'Debug messages date range issue';

    protected $hidden = true;

    public function __construct(
        private MessageService $messageService
    ) {
        parent::__construct();
    }

    public function handle()
    {
        putenv('MADELINE_SUPPRESS_LOGS=true');
        
        $channel = $this->argument('channel');
        $fromDate = $this->argument('from');
        $toDate = $this->argument('to');

        try {
            $from = Carbon::parse($fromDate)->startOfDay();
            $to = Carbon::parse($toDate)->endOfDay();
            
            $this->info("Debug info:");
            $this->info("From: {$from->toDateTimeString()} (timestamp: {$from->timestamp})");
            $this->info("To: {$to->toDateTimeString()} (timestamp: {$to->timestamp})");
            
            // Get last few messages to check their dates
            ob_start();
            $result = $this->messageService->getLastMessageId($channel);
            ob_end_clean();
            
            if ($result) {
                $this->info("Channel found: {$channel}");
                $this->info("Last message ID: " . ($result['last_message_id'] ?? 'unknown'));
            }
            
            // Try to get messages with debug info
            $this->info("\nFetching messages...");
            
            ob_start();
            $messages = $this->messageService->getMessagesByDateRange($channel, $from, $to, 10);
            ob_end_clean();
            
            if ($messages === null) {
                $this->error("Messages is null");
            } elseif (empty($messages)) {
                $this->error("Messages array is empty");
                
                // Try to get recent messages to see their dates
                $this->info("\nTrying to get recent messages to check dates...");
                ob_start();
                $api = app(\App\Contracts\TelegramApiInterface::class);
                $channelName = '@' . ltrim($channel, '@');
                $recent = $api->getMessagesHistory($channelName, ['limit' => 5]);
                ob_end_clean();
                
                if (!empty($recent['messages'])) {
                    $this->info("Found " . count($recent['messages']) . " recent messages:");
                    foreach ($recent['messages'] as $msg) {
                        if (isset($msg['date'])) {
                            $msgDate = Carbon::createFromTimestamp($msg['date']);
                            $this->info("Message ID: {$msg['id']}, Date: {$msgDate->toDateTimeString()}, In range: " . 
                                ($msg['date'] >= $from->timestamp && $msg['date'] <= $to->timestamp ? 'YES' : 'NO'));
                        }
                    }
                } else {
                    $this->error("No recent messages found");
                }
            } else {
                $this->info("Found " . count($messages) . " messages");
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            ob_end_clean();
            $this->error("Error: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}