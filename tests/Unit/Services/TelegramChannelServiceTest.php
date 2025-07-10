<?php

namespace Tests\Unit\Services;

use App\Contracts\TelegramApiInterface;
use App\Services\Telegram\MessageService;
use App\Services\Telegram\StatisticsService;
use App\Services\TelegramChannelService;
use Mockery;
use Tests\TestCase;

class TelegramChannelServiceTest extends TestCase
{
    private TelegramChannelService $service;
    private $messageService;
    private $statisticsService;
    private $apiClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->messageService = Mockery::mock(MessageService::class);
        $this->statisticsService = Mockery::mock(StatisticsService::class);
        $this->apiClient = Mockery::mock(TelegramApiInterface::class);

        $this->service = new TelegramChannelService(
            $this->messageService,
            $this->statisticsService,
            $this->apiClient,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_get_last_message_id_delegates_to_message_service()
    {
        $channel = 'testchannel';
        $expectedId = 12345;

        $this->messageService
            ->shouldReceive('getLastMessageId')
            ->with($channel)
            ->once()
            ->andReturn($expectedId);

        $result = $this->service->getLastMessageId($channel);

        $this->assertEquals($expectedId, $result);
    }

    public function test_get_channel_statistics_delegates_to_statistics_service()
    {
        $channel = 'testchannel';
        $days = 7;
        $expectedStats = ['summary' => ['total_messages' => 100]];

        $this->statisticsService
            ->shouldReceive('getChannelStatistics')
            ->with($channel, $days)
            ->once()
            ->andReturn($expectedStats);

        $result = $this->service->getChannelStatistics($channel, $days);

        $this->assertEquals($expectedStats, $result);
    }

    public function test_get_channel_info_includes_last_message_id()
    {
        $channel = 'testchannel';
        $channelInfo = [
            'id' => 123,
            'title' => 'Test Channel',
            'type' => 'channel',
        ];
        $lastMessageId = 99999;

        $this->apiClient
            ->shouldReceive('getChannelInfo')
            ->with($channel)
            ->once()
            ->andReturn($channelInfo);

        $this->messageService
            ->shouldReceive('getLastMessageId')
            ->with($channel)
            ->once()
            ->andReturn($lastMessageId);

        $result = $this->service->getChannelInfo($channel);

        $this->assertIsArray($result);
        $this->assertEquals($lastMessageId, $result['last_message_id']);
        $this->assertEquals($channelInfo['id'], $result['id']);
    }
}
