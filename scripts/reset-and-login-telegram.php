#!/usr/bin/env php
<?php

/**
 * Reset and Login Telegram Session Script
 * 
 * This script resets the current session and initiates a new login
 * Usage: php scripts/reset-and-login-telegram.php
 */

require __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel application
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use danog\MadelineProto\API;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\AppInfo;
use Illuminate\Support\Facades\Log;

echo "Telegram Session Reset and Login Script\n";
echo "======================================\n\n";

// First, reset the session
echo "STEP 1: Resetting current session...\n";
echo "-----------------------------------\n";

try {
    $sessionFile = storage_path('app/telegram.madeline');
    $sessionFiles = [
        $sessionFile,
        $sessionFile . '.lock',
        $sessionFile . '.temp.madeline',
        $sessionFile . '.lightState.php',
        $sessionFile . '.lightState.php.lock',
        $sessionFile . '.safe.php',
        $sessionFile . '.safe.php.lock',
        $sessionFile . '.ipcState.php',
        $sessionFile . '.ipcState.php.lock',
    ];
    
    $deletedFiles = [];
    
    // First check if the main session is a directory
    if (is_dir($sessionFile)) {
        echo "Found session directory: " . basename($sessionFile) . " ... ";
        if (deleteDirectory($sessionFile)) {
            echo "DELETED\n";
            $deletedFiles[] = basename($sessionFile) . ' (directory)';
        } else {
            echo "FAILED TO DELETE\n";
        }
    }
    
    // Then check for individual files
    foreach ($sessionFiles as $file) {
        if (file_exists($file) && !is_dir($file)) {
            echo "Found: " . basename($file) . " ... ";
            if (unlink($file)) {
                echo "DELETED\n";
                $deletedFiles[] = basename($file);
            } else {
                echo "FAILED TO DELETE\n";
            }
        }
    }
    
    // Clear cache
    echo "\nClearing Telegram cache from database... ";
    try {
        $count = \App\Models\TelegramCache::count();
        \App\Models\TelegramCache::truncate();
        echo "CLEARED ($count entries)\n";
    } catch (\Exception $e) {
        echo "FAILED: " . $e->getMessage() . "\n";
    }
    
    echo "\nSession reset complete. Deleted " . count($deletedFiles) . " files.\n\n";
    
} catch (\Exception $e) {
    echo "\nERROR during reset: " . $e->getMessage() . "\n";
    exit(1);
}

// Now start new login
echo "STEP 2: Starting new Telegram login...\n";
echo "-------------------------------------\n\n";

try {
    // Configure MadelineProto
    $settings = new Settings;
    
    $appInfo = new AppInfo;
    $appInfo->setApiId((int) config('telegram.api_id'));
    $appInfo->setApiHash(config('telegram.api_hash'));
    
    $settings->setAppInfo($appInfo);
    
    // Check if we have open_basedir restrictions
    if (ini_get('open_basedir')) {
        echo "Note: Running with open_basedir restrictions. Some features may be limited.\n\n";
    }
    
    echo "Initializing MadelineProto...\n";
    $madelineProto = new API($sessionFile, $settings);
    
    echo "\n";
    echo "===========================================\n";
    echo "  TELEGRAM AUTHENTICATION REQUIRED\n";
    echo "===========================================\n\n";
    
    // Start the authentication process
    $madelineProto->start();
    
    echo "\n✅ Successfully logged in to Telegram!\n\n";
    
    // Get self info to confirm login
    $self = $madelineProto->getSelf();
    
    echo "Logged in as:\n";
    echo "- Name: " . ($self['first_name'] ?? 'Unknown') . " " . ($self['last_name'] ?? '') . "\n";
    echo "- Username: " . (isset($self['username']) ? '@' . $self['username'] : 'Not set') . "\n";
    echo "- Phone: " . ($self['phone'] ?? 'Hidden') . "\n";
    echo "- User ID: " . ($self['id'] ?? 'Unknown') . "\n";
    
    echo "\n✅ Session saved successfully!\n";
    echo "You can now use the API endpoints.\n";
    
} catch (\Exception $e) {
    echo "\n❌ ERROR during login: " . $e->getMessage() . "\n";
    echo "\nIf you see IPC server errors, this is expected in restricted environments.\n";
    echo "The session should still work for basic operations.\n";
    exit(1);
}

function deleteDirectory($dir) {
    if (!is_dir($dir)) {
        return false;
    }
    
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        if (is_dir($path)) {
            deleteDirectory($path);
        } else {
            unlink($path);
        }
    }
    
    return rmdir($dir);
}

echo "\n";
exit(0);