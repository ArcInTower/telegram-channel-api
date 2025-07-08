<?php

namespace App\Services;

use App\Models\TelegramCache;
use danog\MadelineProto\API;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\AppInfo;
use danog\MadelineProto\Logger;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TelegramChannelService
{
    private ?API $madelineProto = null;
    private int $cacheTtl;
    
    public function __construct()
    {
        $this->cacheTtl = config('telegram.cache_ttl', 300);
    }
    
    private function getMadelineProto(): API
    {
        if ($this->madelineProto === null) {
            // Check if we're in a restricted environment (production)
            $isRestricted = $this->isRestrictedEnvironment();
            
            if ($isRestricted) {
                Log::info('Running MadelineProto in restricted environment mode');
                
                // Set environment variable to disable IPC
                putenv('MADELINE_IPC_SINGLE_THREAD=1');
                
                // Force single-threaded mode
                if (!defined('MADELINE_WORKER_TYPE')) {
                    define('MADELINE_WORKER_TYPE', 'madeline-ipc-server');
                }
            }
            
            $settings = new Settings;
            
            $appInfo = new AppInfo;
            $appInfo->setApiId((int) config('telegram.api_id'));
            $appInfo->setApiHash(config('telegram.api_hash'));
            
            $settings->setAppInfo($appInfo);
            
            if ($isRestricted) {
                // Configure logger to use Laravel logs instead of stdout
                $logger = new \danog\MadelineProto\Settings\Logger;
                $logger->setType(Logger::LOGGER_FILE);
                $logger->setExtra(storage_path('logs/madeline.log'));
                $logger->setLevel(Logger::WARNING);
                $settings->setLogger($logger);
                
                // Use memory database to avoid filesystem issues
                $settings->setDb(
                    (new \danog\MadelineProto\Settings\Database\Memory)
                );
                
                // Set serialization interval to maximum (1 hour) to minimize writes
                $settings->getSerialization()->setInterval(3600);
            }
            
            $sessionFile = storage_path('app/' . config('telegram.session_file'));
            
            try {
                $this->madelineProto = new API($sessionFile, $settings);
                
                if (!$isRestricted) {
                    $this->madelineProto->start();
                }
            } catch (\Exception $e) {
                $errorMessage = $e->getMessage();
                Log::error('Failed to initialize MadelineProto: ' . $errorMessage);
                
                // If it fails due to IPC issues in restricted environment
                if ($isRestricted && (strpos($errorMessage, 'IPC') !== false || strpos($errorMessage, 'open_basedir') !== false)) {
                    Log::info('IPC server cannot start in restricted environment - this is expected');
                    // Continue using the already created instance without IPC
                    // The instance should still be usable for basic operations
                } else {
                    // Re-throw if it's not an IPC-related error
                    throw $e;
                }
            }
        }
        
        // Ensure we always return a valid instance
        if ($this->madelineProto === null) {
            throw new \RuntimeException('Failed to initialize MadelineProto - instance is null');
        }
        
        return $this->madelineProto;
    }
    
    public function getLastMessageId(string $channelUsername): ?int
    {
        $channelUsername = $this->normalizeUsername($channelUsername);
        
        $cache = $this->getFromCache($channelUsername);
        
        if ($cache && !$cache->isExpired()) {
            Log::info("Cache hit for channel: {$channelUsername}");
            return $cache->last_message_id;
        }
        
        $messageId = $this->fetchLastMessageId($channelUsername);
        
        if ($messageId !== null) {
            $this->saveToCache($channelUsername, $messageId);
        }
        
        return $messageId;
    }
    
    private function fetchLastMessageId(string $username): ?int
    {
        try {
            Log::info("Fetching last message for channel: {$username}");
            
            $madelineProto = $this->getMadelineProto();
            
            $channelUsername = '@' . ltrim($username, '@');
            
            $info = $madelineProto->getInfo($channelUsername);
            
            if ($info['type'] !== 'channel' && $info['type'] !== 'supergroup') {
                Log::warning("Not a public channel: {$username}");
                return null;
            }
            
            $messages = $madelineProto->messages->getHistory([
                'peer' => $channelUsername,
                'offset_id' => 0,
                'offset_date' => 0,
                'add_offset' => 0,
                'limit' => 1,
                'max_id' => 0,
                'min_id' => 0,
                'hash' => 0,
            ]);
            
            if (!empty($messages['messages'])) {
                $lastMessage = $messages['messages'][0];
                $messageId = $lastMessage['id'];
                
                Log::info("Found last message ID {$messageId} for channel: {$username}");
                return $messageId;
            }
            
            Log::warning("No messages found for channel: {$username}");
            return null;
            
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            Log::error("Error fetching channel {$username}: " . $errorMessage);
            
            // Handle specific error cases
            if (strpos($errorMessage, 'CHANNEL_PRIVATE') !== false) {
                Log::warning("Channel is private: {$username}");
            } elseif (strpos($errorMessage, 'IPC') !== false || strpos($errorMessage, 'start the IPC server') !== false) {
                Log::error("IPC server error - running in restricted mode");
            } elseif (strpos($errorMessage, 'open_basedir') !== false) {
                Log::error("Open basedir restriction detected");
            } elseif (strpos($errorMessage, 'SESSION_REVOKED') !== false) {
                Log::error("Session revoked - need to re-authenticate");
                $this->madelineProto = null; // Reset the instance
            }
            
            return null;
        }
    }
    
    public function getChannelInfo(string $channelUsername): ?array
    {
        try {
            $channelUsername = '@' . ltrim($channelUsername, '@');
            $madelineProto = $this->getMadelineProto();
            
            $info = $madelineProto->getInfo($channelUsername);
            $fullInfo = $madelineProto->getFullInfo($channelUsername);
            
            return [
                'id' => $info['bot_api_id'] ?? null,
                'title' => $info['Chat']['title'] ?? null,
                'username' => $info['Chat']['username'] ?? null,
                'type' => $info['type'] ?? null,
                'participants_count' => $fullInfo['full']['participants_count'] ?? null,
                'about' => $fullInfo['full']['about'] ?? null,
                'last_message_id' => $this->getLastMessageId($channelUsername)
            ];
            
        } catch (\Exception $e) {
            Log::error("Error getting channel info: " . $e->getMessage());
            return null;
        }
    }
    
    private function normalizeUsername(string $username): string
    {
        return ltrim(strtolower($username), '@');
    }
    
    private function isRestrictedEnvironment(): bool
    {
        // Check if open_basedir is set (common in shared hosting)
        if (ini_get('open_basedir')) {
            return true;
        }
        
        // Check if we can access /proc (usually restricted in production)
        if (!@file_exists('/proc/self/maps')) {
            return true;
        }
        
        // Check environment variable
        if (env('MADELINE_RESTRICTED_MODE', false)) {
            return true;
        }
        
        // Check if running in production
        if (app()->environment('production')) {
            return true;
        }
        
        return false;
    }
    
    private function getFromCache(string $channelUsername): ?TelegramCache
    {
        return TelegramCache::where('channel_username', $channelUsername)->first();
    }
    
    private function saveToCache(string $channelUsername, int $messageId): void
    {
        TelegramCache::updateOrCreate(
            ['channel_username' => $channelUsername],
            [
                'last_message_id' => $messageId,
                'last_checked_at' => now(),
                'expires_at' => now()->addSeconds($this->cacheTtl),
                'metadata' => [
                    'fetched_at' => now()->toISOString(),
                    'method' => 'mtproto'
                ]
            ]
        );
    }
    
    public function clearExpiredCache(): int
    {
        return TelegramCache::expired()->delete();
    }
    
    public function __destruct()
    {
        // MadelineProto handles disconnection automatically
    }
}