<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Telegram\UserService;

class UpdateProfileClone extends Command
{
    protected $signature = 'telegram:clone-user {username : Username to clone (without @)}';
    protected $description = 'Clone another user\'s profile with username variations';

    private array $variations = [
        'a' => ['а', 'ａ', '@'],  // Cyrillic 'a', full-width 'a'
        'e' => ['е', 'ｅ', '3'],  // Cyrillic 'e', full-width 'e'
        'i' => ['і', 'ｉ', '1'],  // Cyrillic 'i', full-width 'i'
        'o' => ['о', 'ｏ', '0'],  // Cyrillic 'o', full-width 'o'
        'n' => ['п', 'ｎ'],       // Similar looking
        'r' => ['г', 'ｒ'],       // Similar looking
        's' => ['ѕ', 'ｓ', '5'],  // Cyrillic 's', full-width 's'
        'c' => ['с', 'ｃ'],       // Cyrillic 'c', full-width 'c'
        'B' => ['В', 'Ｂ', '8'],  // Cyrillic 'B', full-width 'B'
        'F' => ['Ｆ'],            // Full-width 'F'
    ];

    public function __construct(
        private UserService $userService
    ) {
        parent::__construct();
    }

    public function handle()
    {
        // Configure MadelineProto to suppress logs
        putenv('MADELINE_SUPPRESS_LOGS=true');
        
        $targetUsername = $this->argument('username');
        $targetUsername = ltrim($targetUsername, '@');
        
        $this->info("Fetching profile info for @{$targetUsername}...");
        
        // Get target user info
        try {
            ob_start();
            $api = app(\App\Contracts\TelegramApiInterface::class);
            $targetInfo = $api->getInfo('@' . $targetUsername);
            ob_end_clean();
            
            if (!$targetInfo || !isset($targetInfo['User'])) {
                $this->error("User @{$targetUsername} not found");
                return Command::FAILURE;
            }
            
            $targetUser = $targetInfo['User'];
            $firstName = $targetUser['first_name'] ?? '';
            $lastName = $targetUser['last_name'] ?? '';
            $targetBio = $targetUser['about'] ?? '';
            
            $this->info("Found user: {$firstName} {$lastName}");
            
        } catch (\Exception $e) {
            ob_end_clean();
            $this->error("Failed to get user info: " . $e->getMessage());
            return Command::FAILURE;
        }
        
        // Update name and bio
        try {
            ob_start();
            if ($firstName) {
                $this->userService->updateName($firstName, $lastName);
                $this->info("✓ Name updated to: {$firstName} {$lastName}");
            }
            
            // Create bio
            $bio = "Clon No Oficial de {$firstName}";
            if ($lastName) {
                $bio .= " {$lastName}";
            }
            // Truncate to 70 chars if needed
            if (strlen($bio) > 70) {
                $bio = substr($bio, 0, 67) . '...';
            }
            
            $this->userService->updateBio($bio);
            ob_end_clean();
            $this->info("✓ Bio updated to: {$bio}");
        } catch (\Exception $e) {
            ob_end_clean();
            $this->error('Failed to update name/bio: ' . $e->getMessage());
        }
        
        // Generate username variations
        $baseUsername = $targetUsername . 'Clone';
        $variations = $this->generateVariations($baseUsername);
        
        $success = false;
        foreach ($variations as $username) {
            try {
                ob_start();
                $result = $this->userService->updateUsername($username);
                ob_end_clean();
                
                if ($result) {
                    $this->info("✓ Username updated to: @{$username}");
                    $success = true;
                    break;
                }
            } catch (\Exception $e) {
                ob_end_clean();
                if (str_contains($e->getMessage(), 'USERNAME_OCCUPIED')) {
                    $this->warn("✗ @{$username} is taken, trying next variation...");
                    continue;
                } elseif (str_contains($e->getMessage(), 'FLOOD_WAIT')) {
                    // Extract wait time
                    preg_match('/FLOOD_WAIT_(\d+)/', $e->getMessage(), $matches);
                    $waitTime = $matches[1] ?? 300;
                    $this->error("⏱️ Rate limited by Telegram. Please wait {$waitTime} seconds (about " . round($waitTime/60) . " minutes)");
                    $this->info("Your current username is: @" . ($this->userService->getCurrentProfile()['username'] ?? 'none'));
                    return Command::FAILURE;
                } else {
                    $this->error("Error with @{$username}: " . $e->getMessage());
                }
            }
        }
        
        if (!$success) {
            $this->error('Could not find an available username variation');
            return Command::FAILURE;
        }
        
        return Command::SUCCESS;
    }
    
    private function generateVariations(string $base): array
    {
        $variations = [$base]; // Try original first
        
        // Character replacements for common letters
        $replacements = [
            'a' => ['4', '@'],
            'e' => ['3'],
            'i' => ['1', 'l'],
            'I' => ['1', 'l', 'i'],
            'o' => ['0'],
            'O' => ['0'],
            's' => ['5', 'z'],
            'S' => ['5', 'Z'],
            'g' => ['9'],
            'l' => ['1', 'i'],
            'z' => ['2'],
        ];
        
        // Generate variations by replacing single characters
        $chars = str_split($base);
        $length = strlen($base);
        
        // Single character replacements
        for ($i = 0; $i < $length; $i++) {
            $char = $chars[$i];
            if (isset($replacements[$char])) {
                foreach ($replacements[$char] as $replacement) {
                    $variant = $chars;
                    $variant[$i] = $replacement;
                    $variations[] = implode('', $variant);
                }
            }
        }
        
        // Add variations with underscores
        $variations[] = str_replace('Clone', '_Clone', $base);
        $variations[] = $base . '_';
        $variations[] = '_' . $base;
        
        // Add variations with case changes
        $variations[] = strtolower($base);
        $variations[] = ucfirst(strtolower($base));
        
        // Add common suffixes
        $suffixes = ['_bot', '_real', '_official', '_2', '_v2', '_new', '_alt'];
        foreach ($suffixes as $suffix) {
            if (strlen($base . $suffix) <= 32) {
                $variations[] = $base . $suffix;
            }
        }
        
        // Only add numbers as last resort
        for ($i = 2; $i <= 5; $i++) {
            $variations[] = $base . $i;
        }
        
        // Remove duplicates and filter valid usernames (ONLY ASCII)
        $variations = array_unique($variations);
        $variations = array_filter($variations, function($username) {
            return strlen($username) >= 5 && strlen($username) <= 32 && 
                   preg_match('/^[a-zA-Z0-9_]+$/', $username);
        });
        
        return array_values($variations);
    }
}