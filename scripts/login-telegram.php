#!/usr/bin/env php
<?php

/**
 * Login to Telegram Script
 * 
 * This script initiates a new Telegram login
 * Usage: php scripts/login-telegram.php
 */

require __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel application
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use danog\MadelineProto\API;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\AppInfo;
use danog\MadelineProto\Logger;

echo "Telegram Login Script\n";
echo "====================\n\n";

try {
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
    
    $sessionFile = storage_path('app/telegram.madeline');
    
    // Check if session already exists
    if (file_exists($sessionFile) || is_dir($sessionFile)) {
        echo "⚠️  Warning: A session already exists.\n";
        echo "Run 'php scripts/reset-telegram-session.php' first if you want to start fresh.\n\n";
        
        $response = readline("Continue anyway? (y/N): ");
        if (strtolower($response) !== 'y') {
            echo "Aborted.\n";
            exit(0);
        }
        echo "\n";
    }
    
    echo "Initializing MadelineProto...\n\n";
    
    $madelineProto = new API($sessionFile, $settings);
    
    // Check if already logged in
    if ($madelineProto->getSelf()) {
        echo "✅ Already logged in!\n\n";
        $self = $madelineProto->getSelf();
        echo "Current session:\n";
        echo "- Name: " . ($self['first_name'] ?? 'Unknown') . " " . ($self['last_name'] ?? '') . "\n";
        echo "- Username: " . (isset($self['username']) ? '@' . $self['username'] : 'Not set') . "\n";
        echo "- User ID: " . ($self['id'] ?? 'Unknown') . "\n";
        exit(0);
    }
    
    echo "===========================================\n";
    echo "  TELEGRAM AUTHENTICATION REQUIRED\n";
    echo "===========================================\n\n";
    echo "You will be prompted for:\n";
    echo "1. Your phone number (with country code)\n";
    echo "2. The verification code sent to your Telegram app\n";
    echo "3. Your 2FA password (if enabled)\n\n";
    
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
    
    echo "\n✅ Session saved to: " . $sessionFile . "\n";
    echo "You can now use the API endpoints.\n";
    
} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n\n";
    
    if (strpos($e->getMessage(), 'IPC') !== false) {
        echo "Note: IPC server errors are expected in restricted environments.\n";
        echo "The session should still work for basic operations.\n";
    }
    
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n";
exit(0);