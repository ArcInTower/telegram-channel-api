<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Telegram\MessageService;

class SendMessageInternal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:send-message-internal 
                            {channel : The channel username or ID}
                            {message : The message to send}
                            {--reply-to= : ID of message to reply to}
                            {--silent : Send message silently (no notification)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Internal command to send message';

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
        private MessageService $messageService
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
        $replyTo = $this->option('reply-to');
        $silent = $this->option('silent');

        // Suppress any remaining output
        ob_start();
        
        try {
            $result = $this->messageService->sendMessage(
                $channel,
                $message,
                $replyTo ? (int)$replyTo : null,
                $silent
            );

            // Clean output buffer to remove MadelineProto logs
            ob_end_clean();

            if ($result === null) {
                echo "Error: Failed to send message\n";
                return Command::FAILURE;
            }

            // Output the sent message ID
            if (is_array($result) && isset($result['id'])) {
                echo "Message sent successfully. ID: " . $result['id'];
            } elseif (is_numeric($result)) {
                echo "Message sent successfully. ID: " . $result;
            } else {
                echo "Message sent successfully";
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            ob_end_clean();
            echo "Error: " . $e->getMessage() . "\n";
            return Command::FAILURE;
        }
    }
}