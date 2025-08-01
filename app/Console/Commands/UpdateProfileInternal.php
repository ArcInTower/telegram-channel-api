<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Telegram\UserService;

class UpdateProfileInternal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:update-profile-internal 
                            {--first-name= : New first name}
                            {--last-name= : New last name}
                            {--username= : New username without @}
                            {--bio= : New bio/about text}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Internal command to update profile';

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
        // Configure MadelineProto to suppress logs before any initialization
        putenv('MADELINE_SUPPRESS_LOGS=true');
        
        $firstName = $this->option('first-name');
        $lastName = $this->option('last-name');
        $username = $this->option('username');
        $bio = $this->option('bio');

        // Suppress any remaining output
        ob_start();
        
        try {
            $updates = [];
            
            // Update name if provided
            if ($firstName || $lastName) {
                $nameResult = $this->userService->updateName($firstName, $lastName);
                if ($nameResult) {
                    $updates[] = "Name updated";
                }
            }
            
            // Update username if provided
            if ($username) {
                $usernameResult = $this->userService->updateUsername($username);
                if ($usernameResult) {
                    $updates[] = "Username updated";
                }
            }
            
            // Update bio if provided
            if ($bio) {
                $bioResult = $this->userService->updateBio($bio);
                if ($bioResult) {
                    $updates[] = "Bio updated";
                }
            }

            // Clean output buffer to remove MadelineProto logs
            ob_end_clean();

            if (!empty($updates)) {
                echo "Profile updated successfully:\n";
                foreach ($updates as $update) {
                    echo "- " . $update . "\n";
                }
            } else {
                echo "Error: No updates were applied\n";
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