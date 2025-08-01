<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Telegram\MessageService;

class DeleteMessageInternal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:delete-message-internal 
                            {channel : The channel username or ID}
                            {messageId : The ID of the message to delete}
                            {--revoke : Delete for all users}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Internal command to delete message';

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
        $messageId = $this->argument('messageId');
        $revoke = $this->option('revoke');

        // Suppress any remaining output
        ob_start();
        
        try {
            $result = $this->messageService->deleteMessage(
                $channel,
                (int)$messageId,
                $revoke
            );

            // Clean output buffer to remove MadelineProto logs
            ob_end_clean();

            if ($result === true) {
                echo "Message deleted successfully";
            } else {
                echo "Error: Failed to delete message\n";
                return Command::FAILURE;
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            ob_end_clean();
            echo "Error: " . $e->getMessage() . "\n";
            return Command::FAILURE;
        }
    }
}