<?php

namespace App\Services\Telegram;

use App\Contracts\TelegramApiInterface;
use Illuminate\Support\Facades\Log;

class ChannelService
{
    public function __construct(
        private TelegramApiInterface $apiClient,
    ) {}

    /**
     * Join a channel or group
     */
    public function joinChannel(string $channelUsername): bool
    {
        $channelUsername = $this->normalizeUsername($channelUsername);
        $channelUsername = '@' . ltrim($channelUsername, '@');

        try {
            Log::info("Joining channel: {$channelUsername}");

            $api = $this->apiClient->getApiInstance();
            
            // Try to join using channels.joinChannel
            try {
                $result = $api->channels->joinChannel([
                    'channel' => $channelUsername
                ]);
                
                if ($result) {
                    Log::info("Successfully joined channel: {$channelUsername}");
                    return true;
                }
            } catch (\Exception $e) {
                // If it's not a channel, try as a chat
                if (str_contains($e->getMessage(), 'CHANNEL_INVALID')) {
                    // Try to join using messages.importChatInvite if it's an invite link
                    if (str_contains($channelUsername, 'joinchat/') || str_contains($channelUsername, '+')) {
                        $hash = $this->extractInviteHash($channelUsername);
                        $result = $api->messages->importChatInvite([
                            'hash' => $hash
                        ]);
                        
                        if ($result) {
                            Log::info("Successfully joined via invite link");
                            return true;
                        }
                    }
                }
                throw $e;
            }

            return false;

        } catch (\Exception $e) {
            $message = $e->getMessage();

            // Already a participant
            if (str_contains($message, 'ALREADY_PARTICIPANT') || 
                str_contains($message, 'USER_ALREADY_PARTICIPANT')) {
                Log::info("Already a member of channel: {$channelUsername}");
                return true;
            }

            // Channel is private
            if (str_contains($message, 'CHANNEL_PRIVATE') || 
                str_contains($message, 'INVITE_HASH_INVALID')) {
                throw new \RuntimeException('Cannot join private channel without valid invite link');
            }

            Log::error("Error joining channel {$channelUsername}: " . $message);
            throw $e;
        }
    }

    /**
     * Leave a channel or group
     */
    public function leaveChannel(string $channelUsername): bool
    {
        $channelUsername = $this->normalizeUsername($channelUsername);
        $channelUsername = '@' . ltrim($channelUsername, '@');

        try {
            Log::info("Leaving channel: {$channelUsername}");

            $api = $this->apiClient->getApiInstance();
            
            // Get channel info first to get the proper ID
            $info = $this->apiClient->getChannelInfo($channelUsername);
            
            if (!$info) {
                throw new \RuntimeException('Channel not found');
            }

            // Try to leave using channels.leaveChannel
            try {
                $result = $api->channels->leaveChannel([
                    'channel' => $channelUsername
                ]);
                
                if ($result) {
                    Log::info("Successfully left channel: {$channelUsername}");
                    return true;
                }
            } catch (\Exception $e) {
                // If it's a chat/group, try messages.deleteChatUser
                if (str_contains($e->getMessage(), 'CHANNEL_INVALID')) {
                    // For groups, we need to use deleteChatUser
                    $self = $api->getSelf();
                    $result = $api->messages->deleteChatUser([
                        'chat_id' => $info['Chat']['id'] ?? $info['id'],
                        'user_id' => $self['id']
                    ]);
                    
                    if ($result) {
                        Log::info("Successfully left group: {$channelUsername}");
                        return true;
                    }
                }
                throw $e;
            }

            return false;

        } catch (\Exception $e) {
            $message = $e->getMessage();

            // Not a participant
            if (str_contains($message, 'USER_NOT_PARTICIPANT')) {
                Log::info("Not a member of channel: {$channelUsername}");
                return true; // Consider it a success if we're not in the channel
            }

            Log::error("Error leaving channel {$channelUsername}: " . $message);
            throw $e;
        }
    }

    /**
     * Extract invite hash from invite link
     */
    private function extractInviteHash(string $link): string
    {
        if (preg_match('/joinchat\/(.+)/', $link, $matches)) {
            return $matches[1];
        }
        if (preg_match('/\+(.+)/', $link, $matches)) {
            return $matches[1];
        }
        return $link;
    }

    /**
     * Normalize channel username
     */
    private function normalizeUsername(string $username): string
    {
        return ltrim(strtolower($username), '@');
    }
}