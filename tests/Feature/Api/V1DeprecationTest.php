<?php

namespace Tests\Feature\Api;

use App\Services\TelegramChannelService;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class V1DeprecationTest extends TestCase
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

    public function test_v1_endpoint_returns_deprecation_header()
    {
        $channel = 'testchannel';
        $messageId = 12345;

        $this->telegramService
            ->shouldReceive('getLastMessageId')
            ->with($channel)
            ->once()
            ->andReturn($messageId);

        $response = $this->getJson("/api/telegram/last-message?channel={$channel}");

        $response->assertStatus(200)
            ->assertHeader('X-API-Deprecation-Warning')
            ->assertHeader(
                'X-API-Deprecation-Warning',
                'This endpoint is deprecated. Please use /api/v2/telegram/channels/{channel}/messages/last-id instead',
            );
    }

    public function test_v1_response_structure_remains_compatible()
    {
        $channel = 'testchannel';
        $messageId = 12345;

        $this->telegramService
            ->shouldReceive('getLastMessageId')
            ->with($channel)
            ->once()
            ->andReturn($messageId);

        $response = $this->getJson("/api/telegram/last-message?channel={$channel}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'channel',
                'last_message_id',
                'from_cache',
                'cache_age_seconds',
                'timestamp',
            ])
            ->assertJson([
                'success' => true,
                'channel' => $channel,
                'last_message_id' => $messageId,
            ]);
    }
}
