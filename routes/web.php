<?php

use App\Http\Controllers\ArchitectureController;
use App\Http\Controllers\ChangelogController;
use App\Http\Controllers\StatisticsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
});

Route::get('/changelog', [ChangelogController::class, 'index'])->name('changelog');
Route::get('/architecture', [ArchitectureController::class, 'index'])->name('architecture');
Route::get('/statistics/{channel}/{days?}', [StatisticsController::class, 'show'])->name('statistics');
