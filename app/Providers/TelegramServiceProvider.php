<?php

namespace App\Providers;

use App\Contracts\TelegramApiInterface;
use App\Services\Telegram\MadelineProtoApiClient;
use App\Services\Telegram\MessageService;
use App\Services\Telegram\Statistics\StatisticsCalculator;
use App\Services\Telegram\StatisticsService;
use App\Services\TelegramChannelService;
use Illuminate\Support\ServiceProvider;

class TelegramServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind interface to implementation
        $this->app->bind(TelegramApiInterface::class, MadelineProtoApiClient::class);

        // Register as singleton to maintain state
        $this->app->singleton(MadelineProtoApiClient::class);

        // Register services
        $this->app->bind(MessageService::class);
        $this->app->bind(StatisticsService::class);
        $this->app->bind(StatisticsCalculator::class);

        // Register the main service
        $this->app->bind(TelegramChannelService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
