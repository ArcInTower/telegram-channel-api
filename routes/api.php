<?php

use App\Http\Controllers\Api\TelegramController;
use App\Http\Controllers\Api\V2\ChannelInfoController;
use App\Http\Controllers\Api\V2\CompareController;
use App\Http\Controllers\Api\V2\MessageController;
use App\Http\Controllers\Api\V2\StatisticsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Telegram API Routes (v1 - Deprecated)
Route::prefix('telegram')->group(function () {
    // Apply rate limiting only in production
    $lastMessageMiddleware = app()->environment('production') ? ['throttle:60,1'] : [];

    Route::get('/last-message', [TelegramController::class, 'getLastMessageId'])
        ->middleware($lastMessageMiddleware)
        ->name('telegram.last-message');
});

// Telegram API Routes (v2)
Route::prefix('v2/telegram')->group(function () {
    // Apply rate limiting only in production
    $channelMiddleware = app()->environment('production') ? ['throttle:60,1'] : [];
    $statsMiddleware = app()->environment('production') ? ['throttle:10,60'] : [];

    Route::prefix('channels/{channel}')->group(function () use ($channelMiddleware, $statsMiddleware) {
        Route::get('/', [ChannelInfoController::class, 'getChannelInfo'])
            ->middleware($channelMiddleware)
            ->name('v2.telegram.channel.info');

        Route::get('/messages/last', [MessageController::class, 'getLastMessageId'])
            ->middleware($channelMiddleware)
            ->name('v2.telegram.channel.last-message');

        Route::get('/messages/last-id', [MessageController::class, 'getLastMessageId'])
            ->middleware($channelMiddleware)
            ->name('v2.telegram.channel.last-message-id');

        Route::get('/statistics', [StatisticsController::class, 'getStatistics'])
            ->middleware($statsMiddleware)
            ->name('v2.telegram.channel.statistics')
            ->defaults('days', 7);

        Route::get('/statistics/{days}', [StatisticsController::class, 'getStatistics'])
            ->middleware($statsMiddleware)
            ->where('days', '[0-9]+')
            ->name('v2.telegram.channel.statistics.days');
    });

    // Compare multiple channels
    Route::post('/channels/compare', [CompareController::class, 'compareChannels'])
        ->middleware($statsMiddleware)
        ->name('v2.telegram.channels.compare');
});
