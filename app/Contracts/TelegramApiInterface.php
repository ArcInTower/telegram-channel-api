<?php

namespace App\Contracts;

use danog\MadelineProto\API;

interface TelegramApiInterface
{
    /**
     * Get MadelineProto API instance
     */
    public function getApiInstance(): API;

    /**
     * Get channel information
     */
    public function getChannelInfo(string $channelUsername): ?array;

    /**
     * Get channel messages history
     */
    public function getMessagesHistory(string $channelUsername, array $params = []): ?array;

    /**
     * Check if environment is restricted
     */
    public function isRestrictedEnvironment(): bool;

    /**
     * Get a single message by ID
     */
    public function getMessage(string $channelUsername, int $messageId): ?array;
    
    /**
     * Get information about a channel or user by ID
     *
     * @param string|int $peer Channel ID, user ID, or username
     * @return array|null
     */
    public function getInfo($peer): ?array;
}
