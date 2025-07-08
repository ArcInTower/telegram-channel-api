<?php

namespace App\Console\Commands;

use danog\MadelineProto\API;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\AppInfo;
use danog\MadelineProto\Logger;
use Illuminate\Console\Command;

class TelegramStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check Telegram session status and display current login information';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Telegram Session Status');
        $this->info('======================');
        $this->newLine();
        
        $sessionFile = storage_path('app/' . config('telegram.session_file'));
        
        // Check if session exists
        if (!file_exists($sessionFile) && !is_dir($sessionFile)) {
            $this->error('❌ No session found');
            $this->info('Run "php artisan telegram:login" to create a new session.');
            return Command::FAILURE;
        }
        
        $this->info('📁 Session found at: ' . $sessionFile);
        
        try {
            // Configure MadelineProto with quiet logger
            $settings = new Settings;
            
            $appInfo = new AppInfo;
            $appInfo->setApiId((int) config('telegram.api_id'));
            $appInfo->setApiHash(config('telegram.api_hash'));
            
            $settings->setAppInfo($appInfo);
            
            // Quiet logger
            $logger = new \danog\MadelineProto\Settings\Logger;
            $logger->setType(Logger::LOGGER_FILE);
            $logger->setExtra(storage_path('logs/madeline.log'));
            $logger->setLevel(Logger::ERROR);
            $settings->setLogger($logger);
            
            $this->info('🔄 Checking authentication status...');
            
            $madelineProto = new API($sessionFile, $settings);
            
            // Try to get self info
            try {
                $self = $madelineProto->getSelf();
                
                if ($self) {
                    $this->newLine();
                    $this->info('✅ Session is ACTIVE and authenticated!');
                    $this->newLine();
                    
                    $this->info('👤 Logged in as:');
                    $firstName = $self['first_name'] ?? 'Unknown';
                    $this->info('   Name: ' . substr($firstName, 0, 1) . str_repeat('*', min(strlen($firstName) - 1, 4)));
                    
                    if (isset($self['username'])) {
                        $username = $self['username'];
                        $this->info('   Username: @' . substr($username, 0, 3) . str_repeat('*', min(strlen($username) - 3, 5)));
                    } else {
                        $this->info('   Username: Not set');
                    }
                    
                    if (isset($self['phone'])) {
                        $phone = $self['phone'];
                        $this->info('   Phone: ' . substr($phone, 0, 2) . str_repeat('*', max(strlen($phone) - 4, 4)) . substr($phone, -2));
                    } else {
                        $this->info('   Phone: Hidden');
                    }
                    
                    if (isset($self['id'])) {
                        $userId = (string) $self['id'];
                        $this->info('   User ID: ' . substr($userId, 0, 3) . str_repeat('*', min(strlen($userId) - 3, 5)));
                    } else {
                        $this->info('   User ID: Unknown');
                    }
                    
                    $this->info('   Status: ' . ($self['status']['_'] ?? 'Unknown'));
                    
                    // Check cache stats
                    $this->newLine();
                    $this->info('📊 Cache Statistics:');
                    try {
                        $cacheCount = \App\Models\TelegramCache::count();
                        $activeCacheCount = \App\Models\TelegramCache::active()->count();
                        $this->info('   Total cached channels: ' . $cacheCount);
                        $this->info('   Active cache entries: ' . $activeCacheCount);
                    } catch (\Exception $e) {
                        $this->info('   Unable to retrieve cache stats');
                    }
                    
                    $this->newLine();
                    $this->info('✅ Everything is working correctly!');
                    
                    return Command::SUCCESS;
                } else {
                    $this->error('❌ Session exists but NOT authenticated');
                    $this->info('Run "php artisan telegram:login" to authenticate.');
                    return Command::FAILURE;
                }
                
            } catch (\Exception $e) {
                if (strpos($e->getMessage(), 'AUTH_KEY_UNREGISTERED') !== false) {
                    $this->error('❌ Session is INVALID (AUTH_KEY_UNREGISTERED)');
                    $this->info('The session has been revoked or is corrupted.');
                    $this->info('Run "php artisan telegram:login --reset" to create a new session.');
                } else {
                    $this->error('❌ Cannot verify authentication: ' . $e->getMessage());
                }
                return Command::FAILURE;
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Failed to check session: ' . $e->getMessage());
            
            if (strpos($e->getMessage(), 'IPC') !== false) {
                $this->warn('Note: IPC server errors are expected in restricted environments.');
                $this->info('Try running "php artisan telegram:login --check" for a basic check.');
            }
            
            return Command::FAILURE;
        }
    }
}