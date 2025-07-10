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
            $this->madelineProto = new API($sessionFile, $settings);

            if (!$isRestricted) {
                $this->madelineProto->start();
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
            $channelUsername = '@' . ltrim($channelUsername, '@');
            $api = $this->getApiInstance();

            $info = $api->getInfo($channelUsername);
            $fullInfo = $api->getFullInfo($channelUsername);

            return [
                'id' => $info['bot_api_id'] ?? null,
                'title' => $info['Chat']['title'] ?? null,
                'username' => $info['Chat']['username'] ?? null,
                'type' => $info['type'] ?? null,
                'participants_count' => $fullInfo['full']['participants_count'] ?? null,
                'about' => $fullInfo['full']['about'] ?? null,
            ];

        } catch (\Exception $e) {
            Log::error('Error getting channel info: ' . $e->getMessage());

            return null;
        }
    }

    public function getMessagesHistory(string $channelUsername, array $params = []): ?array
    {
        try {
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
            Log::error('Error getting messages history: ' . $e->getMessage());
            $this->handleApiError($e);

            return null;
        }
    }

    private function handleApiError(\Exception $e): void
    {
        $errorMessage = $e->getMessage();

        if (strpos($errorMessage, 'CHANNEL_PRIVATE') !== false) {
            Log::warning('Channel is private');
        } elseif (strpos($errorMessage, 'SESSION_REVOKED') !== false) {
            Log::error('Session revoked - need to re-authenticate');
            $this->madelineProto = null;
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
}
