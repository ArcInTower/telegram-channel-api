#!/usr/bin/env php
<?php

/**
 * Reset Telegram Session Script
 * 
 * This script can be run from the command line to reset the Telegram session
 * Usage: php scripts/reset-telegram-session.php
 */

require __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel application
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Log;

echo "Telegram Session Reset Script\n";
echo "=============================\n\n";

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
    
    echo "Looking for session files in: " . storage_path('app/') . "\n\n";
    
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
    
    echo "\n=============================\n";
    echo "Session Reset Summary:\n";
    echo "- Files deleted: " . count($deletedFiles) . "\n";
    if (count($deletedFiles) > 0) {
        echo "- Deleted files:\n";
        foreach ($deletedFiles as $file) {
            echo "  * $file\n";
        }
    }
    echo "\nThe Telegram session has been reset.\n";
    echo "You will need to authenticate again on next use.\n";
    
} catch (\Exception $e) {
    echo "\nERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
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

exit(0);