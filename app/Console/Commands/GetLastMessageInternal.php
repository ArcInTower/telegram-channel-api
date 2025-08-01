<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Telegram\MessageService;

class GetLastMessageInternal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:get-last-message-internal {channel : The channel username or ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Internal command to get last message ID';

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
        $channel = $this->argument('channel');

        try {
            $result = $this->messageService->getLastMessageId($channel);

            if ($result === null) {
                echo "Error: Channel not found or no messages available\n";
                return Command::FAILURE;
            }

            // Extract just the ID from the result
            if (is_array($result) && isset($result['last_message_id'])) {
                echo $result['last_message_id'];
            } elseif (is_numeric($result)) {
                echo $result;
            } else {
                echo "Error: Unable to extract message ID from response\n";
                return Command::FAILURE;
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
            return Command::FAILURE;
        }
    }
}