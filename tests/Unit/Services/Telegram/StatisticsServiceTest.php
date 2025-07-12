<?php

namespace Tests\Unit\Services\Telegram;

use App\Contracts\TelegramApiInterface;
use App\Services\Telegram\Statistics\StatisticsCalculator;
use App\Services\Telegram\StatisticsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class StatisticsServiceTest extends TestCase
{
    private StatisticsService $service;
    private $apiClient;
    private $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->apiClient = Mockery::mock(TelegramApiInterface::class);
        $this->calculator = Mockery::mock(StatisticsCalculator::class);

        $this->service = new StatisticsService(
            $this->apiClient,
            $this->calculator,
        );

        Log::shouldReceive('info')->andReturn(null);
        Log::shouldReceive('warning')->andReturn(null);
        Log::shouldReceive('error')->andReturn(null);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_get_channel_statistics_returns_null_for_invalid_channel()
    {
        $channel = 'invalidchannel';

        $this->apiClient
            ->shouldReceive('getChannelInfo')
            ->once()
            ->andReturn(['type' => 'user']); // Not a channel

        $result = $this->service->getChannelStatistics($channel);

        $this->assertNull($result);
    }

    public function test_get_channel_statistics_returns_empty_stats_when_no_messages()
    {
        $channel = 'emptychannel';
        $days = 7;

        $this->apiClient
            ->shouldReceive('getChannelInfo')
            ->once()
            ->andReturn(['type' => 'channel']);

        $this->apiClient
            ->shouldReceive('getMessagesHistory')
            ->once()
            ->andReturn(['messages' => []]);

        $result = $this->service->getChannelStatistics($channel, $days);

        $this->assertIsArray($result);
        $this->assertEquals(0, $result['summary']['total_messages']);
        $this->assertEquals(0, $result['summary']['active_users']);
        $this->assertEquals('N/A', $result['peak_activity']['hour']);
    }

    public function test_get_channel_statistics_processes_messages_correctly()
    {
        $channel = 'testchannel';
        $days = 7;

        $this->apiClient
            ->shouldReceive('getChannelInfo')
            ->once()
            ->andReturn(['type' => 'channel']);

        $messages = [
            'messages' => [
                [
                    'id' => 1,
                    'date' => Carbon::now()->subDays(2)->timestamp,
                    'message' => 'Test message',
                ],
            ],
            'users' => [],
        ];

        $this->apiClient
            ->shouldReceive('getMessagesHistory')
            ->once()
            ->andReturn($messages);

        $expectedStats = [
            'summary' => ['total_messages' => 1],
            'period' => ['days' => $days],
        ];

        $this->calculator
            ->shouldReceive('calculate')
            ->once()
            ->andReturn($expectedStats);

        $result = $this->service->getChannelStatistics($channel, $days);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('period', $result);
        $this->assertArrayHasKey('channel_info', $result);
        $this->assertEquals($expectedStats['summary'], $result['summary']);
        $this->assertEquals($expectedStats['period'], $result['period']);
    }

    public function test_normalizes_channel_username()
    {
        $channel = '@TestChannel';

        $this->apiClient
            ->shouldReceive('getChannelInfo')
            ->with('@testchannel')
            ->once()
            ->andReturn(['type' => 'channel']);

        $this->apiClient
            ->shouldReceive('getMessagesHistory')
            ->once()
            ->andReturn(['messages' => []]);

        $result = $this->service->getChannelStatistics($channel);

        $this->assertIsArray($result);
    }
}
