<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Telegram\MessageService;
use Carbon\Carbon;

class GetMessagesByDateRange extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:messages-by-date 
                            {channel : The channel username or ID}
                            {from : Start date (YYYY-MM-DD)}
                            {to : End date (YYYY-MM-DD)}
                            {--format=json : Output format (json, table, count)}
                            {--limit=100 : Maximum number of messages to retrieve}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get messages from a Telegram channel within a date range';

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
        $fromDate = $this->argument('from');
        $toDate = $this->argument('to');
        $format = $this->option('format');
        $limit = (int) $this->option('limit');

        // Validate dates
        try {
            $from = Carbon::parse($fromDate)->startOfDay();
            $to = Carbon::parse($toDate)->endOfDay();
        } catch (\Exception $e) {
            $this->error('Invalid date format. Please use YYYY-MM-DD format.');
            return Command::FAILURE;
        }

        if ($from->isAfter($to)) {
            $this->error('Start date must be before end date.');
            return Command::FAILURE;
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
            'php %s telegram:messages-by-date-internal %s %s %s --format=%s --limit=%d 2>/dev/null',
            base_path('artisan'),
            escapeshellarg($channel),
            escapeshellarg($fromDate),
            escapeshellarg($toDate),
            escapeshellarg($format),
            $limit
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
                    $this->error('Failed to retrieve messages');
                }
                return Command::FAILURE;
            }
        }

        $this->error('Failed to execute command');
        return Command::FAILURE;
    }
}