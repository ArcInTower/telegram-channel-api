<?php

namespace App\Services\Telegram;

use App\Contracts\TelegramApiInterface;
use Illuminate\Support\Facades\Log;

class UserService
{
    public function __construct(
        private TelegramApiInterface $apiClient,
    ) {}

    /**
     * Update user's first and last name
     */
    public function updateName(?string $firstName, ?string $lastName): bool
    {
        try {
            $api = $this->apiClient->getApiInstance();
            
            // Get current user info
            $self = $api->getSelf();
            
            // Prepare parameters
            $params = [
                'first_name' => $firstName ?? $self['first_name'] ?? '',
                'last_name' => $lastName ?? $self['last_name'] ?? ''
            ];
            
            // Handle "remove" option for last name
            if ($lastName === 'remove') {
                $params['last_name'] = '';
            }
            
            // First name cannot be empty
            if (empty($params['first_name'])) {
                throw new \RuntimeException('First name cannot be empty');
            }
            
            $result = $api->account->updateProfile($params);
            
            if ($result) {
                Log::info("Updated name to: {$params['first_name']} {$params['last_name']}");
                return true;
            }
            
            return false;
            
        } catch (\Exception $e) {
            Log::error("Error updating name: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update username (@username)
     */
    public function updateUsername(string $username): bool
    {
        try {
            $api = $this->apiClient->getApiInstance();
            
            // Remove @ if present
            $username = ltrim($username, '@');
            
            // Handle "remove" option
            if ($username === 'remove') {
                $username = '';
            }
            
            // Validate username (if not removing)
            if (!empty($username) && !preg_match('/^[a-zA-Z0-9_]{5,32}$/', $username)) {
                throw new \RuntimeException('Username must be 5-32 characters, only letters, numbers and underscores');
            }
            
            $result = $api->account->updateUsername([
                'username' => $username
            ]);
            
            if ($result) {
                $msg = empty($username) ? "Username removed" : "Username updated to: @{$username}";
                Log::info($msg);
                return true;
            }
            
            return false;
            
        } catch (\Exception $e) {
            $message = $e->getMessage();
            
            // Handle specific errors
            if (str_contains($message, 'USERNAME_OCCUPIED')) {
                throw new \RuntimeException('Username is already taken');
            }
            if (str_contains($message, 'USERNAME_INVALID')) {
                throw new \RuntimeException('Invalid username format');
            }
            if (str_contains($message, 'USERNAME_NOT_MODIFIED')) {
                throw new \RuntimeException('Username is the same as current');
            }
            
            Log::error("Error updating username: " . $message);
            throw $e;
        }
    }

    /**
     * Update bio/about text
     */
    public function updateBio(string $bio): bool
    {
        try {
            $api = $this->apiClient->getApiInstance();
            
            // Bio has a 70 character limit
            if (strlen($bio) > 70) {
                throw new \RuntimeException('Bio must be 70 characters or less');
            }
            
            $result = $api->account->updateProfile([
                'about' => $bio
            ]);
            
            if ($result) {
                Log::info("Bio updated");
                return true;
            }
            
            return false;
            
        } catch (\Exception $e) {
            Log::error("Error updating bio: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get current user profile info
     */
    public function getCurrentProfile(): array
    {
        try {
            $api = $this->apiClient->getApiInstance();
            $self = $api->getSelf();
            
            return [
                'id' => $self['id'] ?? null,
                'first_name' => $self['first_name'] ?? '',
                'last_name' => $self['last_name'] ?? '',
                'username' => $self['username'] ?? '',
                'phone' => $self['phone'] ?? '',
                'bio' => $self['about'] ?? ''
            ];
            
        } catch (\Exception $e) {
            Log::error("Error getting profile: " . $e->getMessage());
            throw $e;
        }
    }
}