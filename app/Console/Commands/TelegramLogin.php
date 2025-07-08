<?php

namespace App\Console\Commands;

use danog\MadelineProto\API;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\AppInfo;
use danog\MadelineProto\Logger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TelegramLogin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:login 
                            {--reset : Reset session before logging in}
                            {--check : Only check current login status}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Login to Telegram or check current session status';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Telegram Login Manager');
        $this->info('=====================');
        
        $sessionFile = storage_path('app/' . config('telegram.session_file'));
        
        // If --check option, just check status
        if ($this->option('check')) {
            return $this->checkLoginStatus($sessionFile);
        }
        
        // If --reset option, reset first
        if ($this->option('reset')) {
            $this->info('Resetting session files...');
            
            // Delete session files
            $patterns = [
                $sessionFile,
                $sessionFile . '.*',
                $sessionFile . '/**',
            ];
            
            foreach ($patterns as $pattern) {
                foreach (glob($pattern) as $file) {
                    if (is_dir($file)) {
                        $this->deleteDirectory($file);
                        $this->info('Deleted directory: ' . basename($file));
                    } elseif (is_file($file)) {
                        unlink($file);
                        $this->info('Deleted file: ' . basename($file));
                    }
                }
            }
            
            // Also check if main session is a directory
            if (is_dir($sessionFile)) {
                $this->deleteDirectory($sessionFile);
                $this->info('Deleted session directory');
            }
            
            $this->info('Session reset complete.');
            $this->newLine();
        }
        
        // Check if session exists
        if ((file_exists($sessionFile) || is_dir($sessionFile)) && !$this->option('reset')) {
            $this->warn('A session already exists.');
            
            // In non-interactive mode, show info and exit
            if ($this->option('no-interaction') || !$this->input->isInteractive()) {
                $this->info('Use --reset option to reset and login, or --check to see current status.');
                $this->info('Example: php artisan telegram:login --reset --no-interaction');
                return Command::SUCCESS;
            }
            
            if (!$this->confirm('Continue without resetting?')) {
                $this->info('Use --reset option to reset and login.');
                return Command::SUCCESS;
            }
        }
        
        try {
            $this->info('Initializing MadelineProto...');
            
            // Configure MadelineProto
            $settings = new Settings;
            
            $appInfo = new AppInfo;
            $appInfo->setApiId((int) config('telegram.api_id'));
            $appInfo->setApiHash(config('telegram.api_hash'));
            
            $settings->setAppInfo($appInfo);
            
            // Configure logger for CLI
            $logger = new \danog\MadelineProto\Settings\Logger;
            $logger->setType(Logger::LOGGER_FILE);
            $logger->setExtra(storage_path('logs/madeline.log'));
            $logger->setLevel(Logger::NOTICE);
            $settings->setLogger($logger);
            
            $madelineProto = new API($sessionFile, $settings);
            
            // Check if already logged in
            try {
                $self = $madelineProto->getSelf();
                if ($self) {
                    $this->info('✅ Already logged in!');
                    $this->displayUserInfo($self);
                    return Command::SUCCESS;
                }
            } catch (\Exception $e) {
                // Not logged in, continue
            }
            
            $this->newLine();
            $this->info('=========================================');
            $this->info('  TELEGRAM AUTHENTICATION REQUIRED');
            $this->info('=========================================');
            $this->newLine();
            
            // Check if running in non-interactive mode
            if ($this->option('no-interaction') || !$this->input->isInteractive()) {
                $this->error('Cannot perform login in non-interactive mode.');
                $this->info('Telegram login requires interactive input for:');
                $this->info('- Phone number');
                $this->info('- Verification code');
                $this->info('- 2FA password (if enabled)');
                $this->newLine();
                $this->info('Please run this command in an interactive terminal (SSH) to complete login.');
                return Command::FAILURE;
            }
            
            $this->info('You will be prompted for:');
            $this->info('1. Your phone number (with country code)');
            $this->info('2. The verification code sent to your Telegram app');
            $this->info('3. Your 2FA password (if enabled)');
            $this->newLine();
            
            // Start authentication
            $madelineProto->start();
            
            $this->newLine();
            $this->info('✅ Successfully logged in to Telegram!');
            
            // Get self info
            $self = $madelineProto->getSelf();
            $this->displayUserInfo($self);
            
            $this->newLine();
            $this->info('Session saved to: ' . $sessionFile);
            $this->info('You can now use the API endpoints.');
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Login failed: ' . $e->getMessage());
            
            if (strpos($e->getMessage(), 'IPC') !== false) {
                $this->warn('Note: IPC server errors are expected in restricted environments.');
                $this->warn('The session should still work for basic operations.');
            }
            
            if ($this->option('verbose')) {
                $this->error($e->getTraceAsString());
            }
            
            return Command::FAILURE;
        }
    }
    
    private function checkLoginStatus(string $sessionFile): int
    {
        if (!file_exists($sessionFile) && !is_dir($sessionFile)) {
            $this->error('No session found. Run without --check to login.');
            return Command::FAILURE;
        }
        
        try {
            $this->info('Checking current session...');
            
            $settings = new Settings;
            
            $appInfo = new AppInfo;
            $appInfo->setApiId((int) config('telegram.api_id'));
            $appInfo->setApiHash(config('telegram.api_hash'));
            
            $settings->setAppInfo($appInfo);
            
            // Quiet logger for check
            $logger = new \danog\MadelineProto\Settings\Logger;
            $logger->setType(Logger::LOGGER_FILE);
            $logger->setExtra(storage_path('logs/madeline.log'));
            $logger->setLevel(Logger::ERROR);
            $settings->setLogger($logger);
            
            $madelineProto = new API($sessionFile, $settings);
            
            $self = $madelineProto->getSelf();
            
            if ($self) {
                $this->info('✅ Session is active!');
                $this->displayUserInfo($self);
                return Command::SUCCESS;
            } else {
                $this->error('Session exists but not authenticated.');
                return Command::FAILURE;
            }
            
        } catch (\Exception $e) {
            $this->error('Session check failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    private function displayUserInfo(array $user): void
    {
        $this->newLine();
        $this->info('Logged in as:');
        
        $firstName = $user['first_name'] ?? 'Unknown';
        $this->info('- Name: ' . substr($firstName, 0, 1) . str_repeat('*', min(strlen($firstName) - 1, 4)));
        
        if (isset($user['username'])) {
            $username = $user['username'];
            $this->info('- Username: @' . substr($username, 0, 3) . str_repeat('*', min(strlen($username) - 3, 5)));
        } else {
            $this->info('- Username: Not set');
        }
        
        if (isset($user['phone'])) {
            $phone = $user['phone'];
            $this->info('- Phone: ' . substr($phone, 0, 2) . str_repeat('*', max(strlen($phone) - 4, 4)) . substr($phone, -2));
        } else {
            $this->info('- Phone: Hidden');
        }
        
        if (isset($user['id'])) {
            $userId = (string) $user['id'];
            $this->info('- User ID: ' . substr($userId, 0, 3) . str_repeat('*', min(strlen($userId) - 3, 5)));
        } else {
            $this->info('- User ID: Unknown');
        }
    }
    
    private function deleteDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }
        
        return rmdir($dir);
    }
}