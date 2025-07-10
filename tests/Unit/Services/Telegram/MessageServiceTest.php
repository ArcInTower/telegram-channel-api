<?php

namespace Tests\Unit\Services\Telegram;

use App\Contracts\TelegramApiInterface;
use App\Services\Telegram\MessageService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class MessageServiceTest extends TestCase
{
    private MessageService $service;
    private $apiClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->apiClient = Mockery::mock(TelegramApiInterface::class);

        $this->service = new MessageService($this->apiClient);

        Log::shouldReceive('info')->andReturn(null);
        Log::shouldReceive('warning')->andReturn(null);
        Log::shouldReceive('error')->andReturn(null);

        // Clear cache before each test
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_get_last_message_id_returns_from_cache_when_not_expired()
    {
        $channelUsername = 'testchannel';
        $expectedMessageId = 12345;

        // Put data in cache
        Cache::put('telegram_channel:' . $channelUsername, [
            'last_message_id' => $expectedMessageId,
            'last_checked_at' => now()->toISOString(),
        ], 300);

        $result = $this->service->getLastMessageId($channelUsername);

        $this->assertEquals($expectedMessageId, $result);
    }

    public function test_get_last_message_id_fetches_from_api_when_cache_expired()
    {
        $channelUsername = 'testchannel';
        $expectedMessageId = 67890;

        // Ensure cache is empty
        Cache::forget('telegram_channel:' . $channelUsername);

        $this->apiClient
            ->shouldReceive('getChannelInfo')
            ->with('@' . $channelUsername)
            ->once()
            ->andReturn(['type' => 'channel']);

        $this->apiClient
            ->shouldReceive('getMessagesHistory')
            ->with('@' . $channelUsername, ['limit' => 1])
            ->once()
            ->andReturn([
                'messages' => [
                    ['id' => $expectedMessageId],
                ],
            ]);

        $result = $this->service->getLastMessageId($channelUsername);

        $this->assertEquals($expectedMessageId, $result);

        // Verify it was cached
        $cachedData = Cache::get('telegram_channel:' . $channelUsername);
        $this->assertNotNull($cachedData);
        $this->assertEquals($expectedMessageId, $cachedData['last_message_id']);
    }

    public function test_get_last_message_id_returns_null_when_channel_not_found()
    {
        $channelUsername = 'notfound';

        // Ensure cache is empty
        Cache::forget('telegram_channel:' . $channelUsername);

        $this->apiClient
            ->shouldReceive('getChannelInfo')
            ->with('@' . $channelUsername)
            ->once()
            ->andReturn(null);

        $result = $this->service->getLastMessageId($channelUsername);

        $this->assertNull($result);
    }

    public function test_get_last_message_id_returns_null_when_not_a_channel()
    {
        $channelUsername = 'privatechat';

        // Ensure cache is empty
        Cache::forget('telegram_channel:' . $channelUsername);

        $this->apiClient
            ->shouldReceive('getChannelInfo')
            ->with('@' . $channelUsername)
            ->once()
            ->andReturn(['type' => 'user']);

        $result = $this->service->getLastMessageId($channelUsername);

        $this->assertNull($result);
    }

    public function test_get_last_message_id_normalizes_username()
    {
        $channelUsername = '@TestChannel';
        $normalizedUsername = 'testchannel';

        // Ensure cache is empty
        Cache::forget('telegram_channel:' . $normalizedUsername);

        $this->apiClient
            ->shouldReceive('getChannelInfo')
            ->with('@' . $normalizedUsername)
            ->once()
            ->andReturn(['type' => 'channel']);

        $this->apiClient
            ->shouldReceive('getMessagesHistory')
            ->with('@' . $normalizedUsername, ['limit' => 1])
            ->once()
            ->andReturn(['messages' => []]);

        $result = $this->service->getLastMessageId($channelUsername);

        $this->assertNull($result);
    }
}
