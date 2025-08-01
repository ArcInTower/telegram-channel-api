<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CreateAccount extends Command
{
    protected $signature = 'telegram:create-account {name : Name for the new account}';
    protected $description = 'Create a new Telegram account session';

    public function handle()
    {
        $name = $this->argument('name');
        $sessionFile = $name . '.madeline';
        
        // Update the config to use this new session
        $envFile = base_path('.env');
        $envContent = file_get_contents($envFile);
        
        if (str_contains($envContent, 'TELEGRAM_SESSION_FILE=')) {
            $envContent = preg_replace(
                '/TELEGRAM_SESSION_FILE=.*/',
                "TELEGRAM_SESSION_FILE={$sessionFile}",
                $envContent
            );
        } else {
            $envContent .= "\nTELEGRAM_SESSION_FILE={$sessionFile}";
        }
        
        file_put_contents($envFile, $envContent);
        
        $this->info("Created new account: {$name}");
        $this->info("Session file: {$sessionFile}");
        $this->info("\nNow you need to login with a different phone number:");
        $this->info("php artisan telegram:login");
        
        return Command::SUCCESS;
    }
}