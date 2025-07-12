<?php

namespace Tests\Feature\Api\V2;

use App\Services\TelegramChannelService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class StatisticsControllerTest extends TestCase
{
    private $telegramService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->telegramService = Mockery::mock(TelegramChannelService::class);
        $this->app->instance(TelegramChannelService::class, $this->telegramService);

        // Clear cache before each test
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_get_statistics_returns_success_response()
    {
        $channel = 'php';
        $days = 7;
        $stats = [
            'period' => [
                'start' => Carbon::now()->subDays($days)->toISOString(),
                'end' => Carbon::now()->toISOString(),
                'days' => $days,
            ],
            'summary' => [
                'total_messages' => 150,
                'unique_users' => 25,
            ],
        ];

        $this->telegramService
            ->shouldReceive('getChannelStatistics')
            ->with($channel, $days)
            ->once()
            ->andReturn($stats);

        $response = $this->getJson("/api/v2/telegram/channels/{$channel}/statistics");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'type',
                    'id',
                    'attributes' => [
                        'statistics',
                    ],
                ],
                'meta' => [
                    'timestamp',
                    'api_version',
                    'period_days',
                ],
                'jsonapi' => [
                    'version',
                ],
            ])
            ->assertJson([
                'data' => [
                    'type' => 'channel-statistics',
                    'id' => $channel,
                ],
            ]);
    }

    public function test_get_statistics_with_custom_days()
    {
        $channel = 'php';
        $days = 14;
        $stats = ['summary' => ['total_messages' => 300]];

        $this->telegramService
            ->shouldReceive('getChannelStatistics')
            ->with($channel, $days)
            ->once()
            ->andReturn($stats);

        $response = $this->getJson("/api/v2/telegram/channels/{$channel}/statistics/{$days}");

        $response->assertStatus(200)
            ->assertJson([
                'meta' => [
                    'period_days' => $days,
                ],
            ]);
    }

    public function test_get_statistics_validates_days_parameter()
    {
        $channel = 'php';

        // Test with days > 15 (max allowed)
        $response = $this->getJson("/api/v2/telegram/channels/{$channel}/statistics/16");
        $response->assertStatus(400)
            ->assertJson([
                'errors' => [
                    [
                        'status' => '400',
                        'title' => 'Bad Request',
                    ],
                ],
            ]);

        // Test with days < 1
        $response = $this->getJson("/api/v2/telegram/channels/{$channel}/statistics/0");
        $response->assertStatus(400)
            ->assertJson([
                'errors' => [
                    [
                        'status' => '400',
                        'title' => 'Bad Request',
                    ],
                ],
            ]);
    }
}
