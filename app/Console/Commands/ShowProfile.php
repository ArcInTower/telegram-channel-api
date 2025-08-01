<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Telegram\UserService;

class ShowProfile extends Command
{
    protected $signature = 'telegram:show-profile';
    protected $description = 'Show current Telegram profile info';

    public function __construct(
        private UserService $userService
    ) {
        parent::__construct();
    }

    public function handle()
    {
        try {
            putenv('MADELINE_SUPPRESS_LOGS=true');
            ob_start();
            
            $profile = $this->userService->getCurrentProfile();
            
            ob_end_clean();
            
            $this->info('Current Profile:');
            $this->info('───────────────');
            $this->info('ID: ' . ($profile['id'] ?? 'N/A'));
            $this->info('Name: ' . $profile['first_name'] . ' ' . $profile['last_name']);
            $this->info('Username: @' . ($profile['username'] ?: 'none'));
            $this->info('Bio: ' . ($profile['bio'] ?: 'none'));
            $this->info('Phone: ' . ($profile['phone'] ?? 'N/A'));
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            ob_end_clean();
            $this->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}