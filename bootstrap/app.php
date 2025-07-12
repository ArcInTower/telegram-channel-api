<?php

use App\Http\Middleware\AnonymizeUsers;
use App\Http\Middleware\CheckBlockedChannel;
use App\Http\Middleware\EnsureJsonResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(append: [
            EnsureJsonResponse::class,
            CheckBlockedChannel::class,
            AnonymizeUsers::class,
        ]);

        $middleware->alias([
            'check.blocked.channel' => CheckBlockedChannel::class,
            'anonymize.users' => AnonymizeUsers::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e, $request) {
            // Handle MadelineProto authentication errors
            if ($request->is('api/*') &&
                ($e instanceof \RuntimeException || $e instanceof \Exception) &&
                (str_contains($e->getMessage(), 'MadelineProto') ||
                 str_contains($e->getMessage(), 'SESSION_REVOKED') ||
                 str_contains($e->getMessage(), 'AUTH_KEY_UNREGISTERED'))) {

                return response()->json([
                    'jsonapi' => ['version' => '1.1'],
                    'errors' => [[
                        'status' => '401',
                        'code' => 'TELEGRAM_AUTH_REQUIRED',
                        'title' => 'Telegram Authentication Required',
                        'detail' => 'The Telegram session has expired or been revoked. Please contact the administrator to re-authenticate the bot.',
                    ]],
                ], 401);
            }
        });
    })->create();
