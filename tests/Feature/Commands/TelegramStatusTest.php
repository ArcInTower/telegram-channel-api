<?php

namespace Tests\Feature\Commands;

use Tests\TestCase;

class TelegramStatusTest extends TestCase
{
    public function test_status_shows_no_session_when_not_logged_in()
    {
        // Ensure no session exists
        $sessionFile = storage_path('app/' . config('telegram.session_file'));
        if (file_exists($sessionFile) || is_dir($sessionFile)) {
            if (is_dir($sessionFile)) {
                $this->removeDirectory($sessionFile);
            } else {
                unlink($sessionFile);
            }
        }

        $this->artisan('telegram:status')
            ->expectsOutput('Telegram Session Status')
            ->expectsOutput('======================')
            ->expectsOutput('❌ No session found')
            ->expectsOutput('Run "php artisan telegram:login" to create a new session.')
            ->assertExitCode(1);
    }

    private function removeDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != '.' && $object != '..') {
                if (is_dir($dir . '/' . $object)) {
                    $this->removeDirectory($dir . '/' . $object);
                } else {
                    unlink($dir . '/' . $object);
                }
            }
        }
        rmdir($dir);
    }
}
