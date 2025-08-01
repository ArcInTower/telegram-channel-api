<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SwitchAccount extends Command
{
    protected $signature = 'telegram:switch-account {account? : Account name to switch to}';
    protected $description = 'Switch between different Telegram accounts';

    public function handle()
    {
        $account = $this->argument('account');
        $baseSessionPath = storage_path('app/');
        
        if (!$account) {
            // List available accounts
            $this->info('Available accounts:');
            $files = glob($baseSessionPath . '*.madeline');
            
            if (empty($files)) {
                $this->warn('No accounts found.');
                $this->info('Create a new account with: php artisan telegram:create-account <name>');
                return Command::SUCCESS;
            }
            
            foreach ($files as $file) {
                $name = str_replace('.madeline', '', basename($file));
                $current = (config('telegram.session_file') === basename($file)) ? ' (current)' : '';
                $this->line("- {$name}{$current}");
            }
            
            $this->info("\nTo switch: php artisan telegram:switch-account <name>");
            return Command::SUCCESS;
        }
        
        // Switch to specified account
        $sessionFile = $account . '.madeline';
        $fullPath = $baseSessionPath . $sessionFile;
        
        if (!file_exists($fullPath) && !is_dir($fullPath)) {
            $this->error("Account '{$account}' not found.");
            $this->info("Create it with: php artisan telegram:create-account {$account}");
            return Command::FAILURE;
        }
        
        // Update the config
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
        
        $this->info("Switched to account: {$account}");
        $this->info("Session file: {$sessionFile}");
        
        return Command::SUCCESS;
    }
}