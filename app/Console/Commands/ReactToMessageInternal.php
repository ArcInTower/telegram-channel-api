<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Telegram\MessageService;

class ReactToMessageInternal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:react-internal 
                            {channel : The channel username or ID}
                            {messageId : The ID of the message to react to}
                            {reaction : The reaction emoji}
                            {--big : Send as big reaction}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Internal command to add reaction';

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
        $reaction = $this->argument('reaction');
        $big = $this->option('big');

        // Suppress any remaining output
        ob_start();
        
        try {
            $result = $this->messageService->sendReaction(
                $channel,
                (int)$messageId,
                $reaction,
                $big
            );

            // Clean output buffer to remove MadelineProto logs
            ob_end_clean();

            if ($result === true) {
                if ($reaction === 'remove') {
                    echo "Reaction removed successfully";
                } else {
                    echo "Reaction {$reaction} added successfully";
                }
            } else {
                echo "Error: Failed to add reaction\n";
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