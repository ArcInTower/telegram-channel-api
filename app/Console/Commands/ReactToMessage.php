<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Telegram\MessageService;

class ReactToMessage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:react 
                            {channel : The channel username or ID}
                            {messageId : The ID of the message to react to}
                            {reaction : The reaction emoji (or "remove" to remove reaction)}
                            {--big : Send as big reaction}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add a reaction to a message';

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
        $messageId = $this->argument('messageId');
        $reaction = $this->argument('reaction');
        $big = $this->option('big');

        // Show available reactions if requested
        if ($reaction === 'list') {
            $this->showAvailableReactions();
            return Command::SUCCESS;
        }

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
            'php %s telegram:react-internal %s %s %s%s 2>/dev/null',
            base_path('artisan'),
            escapeshellarg($channel),
            escapeshellarg($messageId),
            escapeshellarg($reaction),
            $big ? ' --big' : ''
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
                if (str_contains($stdout, 'Error:')) {
                    $this->error(trim($stdout));
                } else {
                    $this->error('Failed to add reaction');
                }
                return Command::FAILURE;
            }
        }

        $this->error('Failed to execute command');
        return Command::FAILURE;
    }

    private function showAvailableReactions(): void
    {
        $this->info('Common reactions you can use:');
        $this->line('❤️ 👍 👎 🔥 🥰 👏 😁 🤔 🤯 😱 🤬 😢 🎉 🤩 🤮 💩');
        $this->line('😍 😂 😎 😡 😭 🙏 😘 😊 🥳 🥺 😇 🤝 🤗 🫡 🤭 🤫');
        $this->line('🤨 😐 😑 😶 😏 😈 👻 👽 🤖 😺 😹 😻 😼 😽 🙀 😿');
        $this->info("\nUsage: php artisan telegram:react @channel messageId 👍");
        $this->info("To remove: php artisan telegram:react @channel messageId remove");
    }
}