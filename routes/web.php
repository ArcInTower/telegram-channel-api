<?php

use App\Http\Controllers\ArchitectureController;
use App\Http\Controllers\ChangelogController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\TelegramAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
})->name('home');

Route::get('/changelog', [ChangelogController::class, 'index'])->name('changelog');
Route::get('/architecture', [ArchitectureController::class, 'index'])->name('architecture');
Route::get('/statistics/{channel}/{days?}', [StatisticsController::class, 'show'])->name('statistics');
Route::get('/compare', function () {
    return view('compare');
})->name('compare')->middleware('check.blocked.channel');

Route::get('/exclusion-request', function () {
    return view('exclusion-request');
})->name('exclusion.request');

// Telegram Authentication Routes
Route::prefix('telegram-auth')->group(function () {
    Route::get('/', [TelegramAuthController::class, 'showLoginForm'])->name('telegram.login');
    Route::post('/start', [TelegramAuthController::class, 'startAuth'])->name('telegram.auth.start');
    Route::get('/verify', [TelegramAuthController::class, 'showVerifyForm'])->name('telegram.verify');
    Route::post('/verify', [TelegramAuthController::class, 'verifyCode'])->name('telegram.auth.verify');
    Route::post('/logout', [TelegramAuthController::class, 'logout'])->name('telegram.logout');
});
