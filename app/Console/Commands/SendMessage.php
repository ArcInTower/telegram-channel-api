<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Telegram\MessageService;

class SendMessage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:send-message 
                            {channel : The channel username or ID}
                            {message : The message to send}
                            {--reply-to= : ID of message to reply to}
                            {--silent : Send message silently (no notification)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a message to a Telegram channel';

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
        $message = $this->argument('message');
        $replyTo = $this->option('reply-to');
        $silent = $this->option('silent');

        // Create a separate process to avoid MadelineProto logs
        $descriptorspec = [
            0 => ["pipe", "r"],
            1 => ["pipe", "w"],
            2 => ["pipe", "w"]
        ];

        // Set environment to suppress MadelineProto logs
        $env = array_merge($_ENV, [
            'MADELINE_SUPPRESS_LOGS' => 'true',
        ]);

        $cmd = sprintf(
            'php %s telegram:send-message-internal %s %s%s%s 2>/dev/null',
            base_path('artisan'),
            escapeshellarg($channel),
            escapeshellarg($message),
            $replyTo ? ' --reply-to=' . escapeshellarg($replyTo) : '',
            $silent ? ' --silent' : ''
        );

        $process = proc_open($cmd, $descriptorspec, $pipes, base_path(), $env);

        if (is_resource($process)) {
            fclose($pipes[0]);
            
            $stdout = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[2]);
            
            $return_value = proc_close($process);

            if ($return_value === 0) {
                $this->line($stdout);
                return Command::SUCCESS;
            } else {
                // Look for error in output
                if (str_contains($stdout, 'Error:')) {
                    $this->error(trim($stdout));
                } else {
                    $this->error('Failed to send message');
                }
                return Command::FAILURE;
            }
        }

        $this->error('Failed to execute command');
        return Command::FAILURE;
    }
}