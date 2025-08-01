<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Telegram\UserService;

class UpdateProfile extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:update-profile 
                            {--first-name= : New first name}
                            {--last-name= : New last name (use "remove" to delete)}
                            {--username= : New username without @ (use "remove" to delete)}
                            {--bio= : New bio/about text}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update your Telegram profile (name, username, bio)';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        private UserService $userService
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
        $firstName = $this->option('first-name');
        $lastName = $this->option('last-name');
        $username = $this->option('username');
        $bio = $this->option('bio');

        if (!$firstName && !$lastName && !$username && !$bio) {
            $this->error('Please provide at least one option to update');
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

        $options = '';
        if ($firstName) $options .= ' --first-name=' . escapeshellarg($firstName);
        if ($lastName) $options .= ' --last-name=' . escapeshellarg($lastName);
        if ($username) $options .= ' --username=' . escapeshellarg($username);
        if ($bio) $options .= ' --bio=' . escapeshellarg($bio);

        $cmd = sprintf(
            'php %s telegram:update-profile-internal%s 2>/dev/null',
            base_path('artisan'),
            $options
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
                    $this->error('Failed to update profile');
                }
                return Command::FAILURE;
            }
        }

        $this->error('Failed to execute command');
        return Command::FAILURE;
    }
}