<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Telegram\MessageService;

class GetLastMessageId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:last-message-id {channel : The channel username or ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get the last message ID from a Telegram channel';

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

        // Create a separate process to run the actual command
        $descriptorspec = [
            0 => ["pipe", "r"],  // stdin
            1 => ["pipe", "w"],  // stdout
            2 => ["pipe", "w"]   // stderr
        ];

        $process = proc_open(
            'php ' . base_path('artisan') . ' telegram:get-last-message-internal ' . escapeshellarg($channel),
            $descriptorspec,
            $pipes,
            base_path()
        );

        if (is_resource($process)) {
            // Close stdin
            fclose($pipes[0]);

            // Read stdout
            $stdout = stream_get_contents($pipes[1]);
            fclose($pipes[1]);

            // Read stderr (which we'll ignore)
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[2]);

            // Close the process
            $return_value = proc_close($process);

            // Parse the output
            $lines = explode("\n", trim($stdout));
            $lastLine = end($lines);

            if ($return_value === 0 && is_numeric($lastLine)) {
                $this->line($lastLine);
                return Command::SUCCESS;
            } else {
                // Look for error message in output
                foreach ($lines as $line) {
                    if (str_contains($line, 'Error:') || str_contains($line, 'Channel not found')) {
                        $this->error(str_replace('Error:', '', $line));
                        return Command::FAILURE;
                    }
                }
                $this->error('Telegram authentication required. The bot session has expired or been revoked.');
                return Command::FAILURE;
            }
        }

        $this->error('Failed to execute command');
        return Command::FAILURE;
    }
}