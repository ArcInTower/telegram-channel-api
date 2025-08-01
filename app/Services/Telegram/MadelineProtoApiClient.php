<?php

namespace App\Services\Telegram;

use App\Contracts\TelegramApiInterface;
use danog\MadelineProto\API;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\AppInfo;
use Illuminate\Support\Facades\Log;

class MadelineProtoApiClient implements TelegramApiInterface
{
    private ?API $madelineProto = null;

    public function getApiInstance(): API
    {
        if ($this->madelineProto === null) {
            $this->initializeApi();
        }

        if ($this->madelineProto === null) {
            throw new \RuntimeException('Failed to initialize MadelineProto - instance is null');
        }

        return $this->madelineProto;
    }

    /**
     * Logout and clear the session
     */
    public function logout(): void
    {
        try {
            $sessionFile = storage_path('app/' . config('telegram.session_file'));

            // Try to logout properly if API is available
            try {
                if ($this->madelineProto !== null) {
                    $this->madelineProto->logout();
                }
            } catch (\Exception $e) {
                Log::info('Could not logout from API: ' . $e->getMessage());
            }

            // Clear the session files
            if (file_exists($sessionFile)) {
                // If it's a directory (MadelineProto 8.x)
                if (is_dir($sessionFile)) {
                    $this->deleteDirectory($sessionFile);
                } else {
                    // If it's a file (older versions)
                    unlink($sessionFile);
                }
            }

            // Clear any related files
            $sessionFiles = glob($sessionFile . '*');
            foreach ($sessionFiles as $file) {
                if (is_file($file)) {
                    unlink($file);
                } elseif (is_dir($file)) {
                    $this->deleteDirectory($file);
                }
            }

            $this->madelineProto = null;
            Log::info('Telegram session cleared successfully');
        } catch (\Exception $e) {
            Log::error('Error clearing Telegram session: ' . $e->getMessage());

            throw new \RuntimeException('Failed to clear Telegram session: ' . $e->getMessage());
        }
    }

    /**
     * Recursively delete a directory
     */
    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    private function initializeApi(): void
    {
        $isRestricted = $this->isRestrictedEnvironment();

        if ($isRestricted) {
            Log::info('Running MadelineProto in restricted environment mode');
            putenv('MADELINE_IPC_SINGLE_THREAD=1');

            if (!defined('MADELINE_WORKER_TYPE')) {
                define('MADELINE_WORKER_TYPE', 'madeline-ipc-server');
            }
        }

        $settings = $this->createSettings($isRestricted);
        $sessionFile = storage_path('app/' . config('telegram.session_file'));

        try {
            // Set environment variable to disable browser opening
            putenv('MADELINE_BROWSER=none');

            $this->madelineProto = new API($sessionFile, $settings);

            if (!$isRestricted) {
                try {
                    // Try to get self info to check if we're authorized
                    $this->madelineProto->getSelf();
                } catch (\Throwable $e) {
                    if (str_contains($e->getMessage(), 'LOGIN_REQUIRED') ||
                        str_contains($e->getMessage(), 'SESSION_REVOKED') ||
                        str_contains($e->getMessage(), 'AUTH_KEY_UNREGISTERED')) {
                        // Clear the invalid session
                        $this->logout();

                        throw new \RuntimeException('Telegram authentication required. The bot session has expired or been revoked.');
                    }

                    throw $e;
                }
            }
        } catch (\Exception $e) {
            $this->handleInitializationError($e, $isRestricted);
        }
    }

    private function createSettings(bool $isRestricted): Settings
    {
        $settings = new Settings;

        $appInfo = new AppInfo;
        $appInfo->setApiId((int) config('telegram.api_id'));
        $appInfo->setApiHash(config('telegram.api_hash'));

        $settings->setAppInfo($appInfo);

        if ($isRestricted) {
            $this->configureRestrictedSettings($settings);
        }
        
        // Configure logger to suppress output when requested
        if (getenv('MADELINE_SUPPRESS_LOGS') === 'true') {
            $logger = new \danog\MadelineProto\Settings\Logger;
            $logger->setType(Logger::LOGGER_FILE);
            $logger->setExtra(storage_path('logs/madeline_silent.log'));
            $logger->setLevel(Logger::ULTRA_VERBOSE);
            $settings->setLogger($logger);
        }

        return $settings;
    }

    private function configureRestrictedSettings(Settings $settings): void
    {
        $logger = new \danog\MadelineProto\Settings\Logger;
        $logger->setType(Logger::LOGGER_FILE);
        $logger->setExtra(storage_path('logs/madeline.log'));
        $logger->setLevel(Logger::WARNING);
        $settings->setLogger($logger);

        $settings->setDb(
            (new \danog\MadelineProto\Settings\Database\Memory),
        );

        $settings->getSerialization()->setInterval(3600);
    }

    private function handleInitializationError(\Exception $e, bool $isRestricted): void
    {
        $errorMessage = $e->getMessage();
        Log::error('Failed to initialize MadelineProto: ' . $errorMessage);

        if ($isRestricted && (strpos($errorMessage, 'IPC') !== false || strpos($errorMessage, 'open_basedir') !== false)) {
            Log::info('IPC server cannot start in restricted environment - this is expected');
        } else {
            throw $e;
        }
    }

    public function getChannelInfo(string $channelUsername): ?array
    {
        try {
            // Security validation first
            $this->validateChannelSecurity($channelUsername);
            
            $channelUsername = '@' . ltrim($channelUsername, '@');
            $api = $this->getApiInstance();

            $info = $api->getInfo($channelUsername);

            // Security restriction: Only allow public channels and supergroups
            $type = $info['type'] ?? '';
            if (!in_array($type, ['channel', 'supergroup'])) {
                throw new \Exception('This API only supports public channels, not private chats or groups');
            }

            // Check if it's a public channel (has username)
            if (!isset($info['Chat']['username']) || empty($info['Chat']['username'])) {
                throw new \Exception('This API only supports public channels with usernames');
            }

            $fullInfo = $api->getFullInfo($channelUsername);

            // Log the full info for debugging
            Log::info('Channel full info:', [
                'channel' => $channelUsername,
                'full_info_keys' => array_keys($fullInfo),
                'full_keys' => isset($fullInfo['full']) ? array_keys($fullInfo['full']) : [],
                'chat_keys' => isset($fullInfo['Chat']) ? array_keys($fullInfo['Chat']) : [],
            ]);

            // Try to get approximate total messages from last message ID
            $approxTotalMessages = null;

            try {
                $lastMessages = $api->messages->getHistory([
                    'peer' => $channelUsername,
                    'limit' => 1,
                    'offset_id' => 0,
                ]);

                if (!empty($lastMessages['messages'])) {
                    $approxTotalMessages = $lastMessages['messages'][0]['id'] ?? null;
                }
            } catch (\Exception $e) {
                Log::info('Could not get approximate message count: ' . $e->getMessage());
            }

            return [
                'id' => $info['bot_api_id'] ?? null,
                'title' => $info['Chat']['title'] ?? null,
                'username' => $info['Chat']['username'] ?? null,
                'type' => $info['type'] ?? null,
                'participants_count' => $fullInfo['full']['participants_count'] ?? null,
                'about' => $fullInfo['full']['about'] ?? null,
                'created_date' => $fullInfo['Chat']['date'] ?? null,
                'approx_total_messages' => $approxTotalMessages,
            ];

        } catch (\Exception $e) {
            $message = $e->getMessage();
            Log::error('Error getting channel info: ' . $message);

            // Clear session and re-throw authentication errors
            if (str_contains($message, 'AUTH_KEY_UNREGISTERED') ||
                str_contains($message, 'SESSION_REVOKED') ||
                str_contains($message, 'LOGIN_REQUIRED')) {
                $this->logout();

                throw $e;
            }

            return null;
        }
    }

    public function getInfo($peer): ?array
    {
        try {
            $api = $this->getApiInstance();
            return $api->getInfo($peer);
        } catch (\Exception $e) {
            Log::debug('Error getting peer info', [
                'peer' => $peer,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function getMessagesHistory(string $channelUsername, array $params = []): ?array
    {
        try {
            // Security validation first
            $this->validateChannelSecurity($channelUsername);
            
            $channelUsername = '@' . ltrim($channelUsername, '@');
            $api = $this->getApiInstance();

            $defaultParams = [
                'peer' => $channelUsername,
                'offset_id' => 0,
                'offset_date' => 0,
                'add_offset' => 0,
                'limit' => 1,
                'max_id' => 0,
                'min_id' => 0,
                'hash' => 0,
            ];

            $params = array_merge($defaultParams, $params);

            return $api->messages->getHistory($params);

        } catch (\Exception $e) {
            Log::error('Error getting messages history: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            $this->handleApiError($e);

            return null;
        }
    }

    private function handleApiError(\Exception $e): void
    {
        $errorMessage = $e->getMessage();

        if (strpos($errorMessage, 'CHANNEL_PRIVATE') !== false) {
            Log::warning('Channel is private');
        } elseif (strpos($errorMessage, 'SESSION_REVOKED') !== false ||
                  strpos($errorMessage, 'AUTH_KEY_UNREGISTERED') !== false ||
                  strpos($errorMessage, 'LOGIN_REQUIRED') !== false) {
            Log::error('Authentication error - clearing session and need to re-authenticate');
            $this->logout();

            // Re-throw authentication errors
            throw $e;
        } elseif (strpos($errorMessage, 'IPC') !== false || strpos($errorMessage, 'open_basedir') !== false) {
            Log::error('IPC server error or open basedir restriction');
        }
    }

    public function isRestrictedEnvironment(): bool
    {
        if (ini_get('open_basedir')) {
            return true;
        }

        if (!@file_exists('/proc/self/maps')) {
            return true;
        }

        if (env('MADELINE_RESTRICTED_MODE', false)) {
            return true;
        }

        if (app()->environment('production')) {
            return true;
        }

        return false;
    }

    /**
     * Validate channel security to prevent access to private chats
     */
    private function validateChannelSecurity(string $channel): void
    {
        // Remove @ if present for validation
        $cleanChannel = ltrim($channel, '@');
        
        // 1. Reject numeric IDs completely
        if (is_numeric($cleanChannel) || is_numeric(str_replace('-', '', $cleanChannel))) {
            Log::warning('Security: Attempted to access numeric channel ID', [
                'channel' => $channel,
                'ip' => request()->ip()
            ]);
            throw new \Exception('Access denied: Numeric channel IDs are not allowed');
        }
        
        // 2. Only allow proper username format (alphanumeric and underscore)
        if (!preg_match('/^[a-zA-Z0-9_]{4,32}$/', $cleanChannel)) {
            Log::warning('Security: Invalid channel format attempted', [
                'channel' => $channel,
                'ip' => request()->ip()
            ]);
            throw new \Exception('Invalid channel format. Only public channel usernames are allowed');
        }
        
        // 3. Reject dangerous keywords
        $dangerousKeywords = [
            'self', 'me', 'saved', 'private', 'bot', 'botfather', 
            'telegram', 'service', 'support', '777000', 'settings',
            'channel_bot', 'group', 'gigagroup', 'basicgroup'
        ];
        
        if (in_array(strtolower($cleanChannel), $dangerousKeywords)) {
            Log::warning('Security: Dangerous keyword in channel name', [
                'channel' => $channel,
                'ip' => request()->ip()
            ]);
            throw new \Exception('Access denied: Reserved channel names are not allowed');
        }
        
        // 4. Reject path traversal attempts
        if (preg_match('/\.\.[\/\\\\]|[\/\\\\]\.\./', $channel)) {
            Log::warning('Security: Path traversal attempt', [
                'channel' => $channel,
                'ip' => request()->ip()
            ]);
            throw new \Exception('Access denied: Invalid channel name');
        }
        
        // 5. Reject encoded or special characters
        if ($channel !== htmlspecialchars($channel, ENT_QUOTES, 'UTF-8')) {
            Log::warning('Security: Encoded characters in channel name', [
                'channel' => $channel,
                'ip' => request()->ip()
            ]);
            throw new \Exception('Access denied: Invalid characters in channel name');
        }
        
        // 6. Length validation
        if (strlen($cleanChannel) < 4 || strlen($cleanChannel) > 32) {
            throw new \Exception('Channel username must be between 4 and 32 characters');
        }
    }

    public function getMessage(string $channelUsername, int $messageId): ?array
    {
        try {
            // Security validation first
            $this->validateChannelSecurity($channelUsername);
            
            // Validate message ID
            if ($messageId <= 0 || $messageId > PHP_INT_MAX) {
                throw new \Exception('Invalid message ID');
            }
            
            $channelUsername = '@' . ltrim($channelUsername, '@');
            $api = $this->getApiInstance();

            // Get channel peer
            $channelInfo = $api->getInfo($channelUsername);
            
            // Get messages by ID
            $messages = $api->messages->getMessages([
                'id' => [[
                    '_' => 'inputMessageID',
                    'id' => $messageId
                ]]
            ]);

            if (!empty($messages['messages']) && count($messages['messages']) > 0) {
                $message = $messages['messages'][0];
                
                // Return null for empty messages
                if ($message['_'] === 'messageEmpty') {
                    return null;
                }
                
                return $message;
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Error getting message: ' . $e->getMessage(), [
                'channel' => $channelUsername,
                'message_id' => $messageId
            ]);
            
            $this->handleApiError($e);
            return null;
        }
    }
}
