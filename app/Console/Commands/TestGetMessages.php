<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestGetMessages extends Command
{
    protected $signature = 'telegram:test-messages {channel}';
    protected $description = 'Test getting messages';
    protected $hidden = true;

    public function handle()
    {
        $channel = $this->argument('channel');
        $channelName = '@' . ltrim($channel, '@');
        
        try {
            $api = app(\App\Contracts\TelegramApiInterface::class);
            
            $this->info("Getting messages from: $channelName");
            
            // Get channel info first
            $info = $api->getChannelInfo($channelName);
            if ($info) {
                $this->info("Channel type: " . ($info['type'] ?? 'unknown'));
            }
            
            // Get messages
            $messages = $api->getMessagesHistory($channelName, ['limit' => 5]);
            
            if (empty($messages)) {
                $this->error("No messages object returned");
            } elseif (empty($messages['messages'])) {
                $this->error("Messages array is empty");
                $this->info("Full response: " . json_encode($messages));
            } else {
                $this->info("Found " . count($messages['messages']) . " messages");
                foreach ($messages['messages'] as $i => $msg) {
                    $date = isset($msg['date']) ? date('Y-m-d H:i:s', $msg['date']) : 'no date';
                    $text = isset($msg['message']) ? substr($msg['message'], 0, 50) : 'no text';
                    $this->info("Message $i: ID={$msg['id']}, Date=$date, Text=$text");
                }
            }
            
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
        }
        
        return Command::SUCCESS;
    }
}