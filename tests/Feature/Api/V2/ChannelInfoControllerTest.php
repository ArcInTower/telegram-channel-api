<?php

namespace Tests\Feature\Api\V2;

use App\Services\TelegramChannelService;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class ChannelInfoControllerTest extends TestCase
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

    public function test_get_channel_info_returns_success_response()
    {
        $channel = 'testchannel';
        $info = [
            'id' => 123456,
            'title' => 'Test Channel',
            'username' => 'testchannel',
            'type' => 'channel',
            'participants_count' => 1000,
            'about' => 'Test channel description',
            'last_message_id' => 99999,
        ];

        $this->telegramService
            ->shouldReceive('getChannelInfo')
            ->with($channel)
            ->once()
            ->andReturn($info);

        $response = $this->getJson("/api/v2/telegram/channels/{$channel}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'type',
                    'id',
                    'attributes',
                ],
                'meta' => [
                    'timestamp',
                    'api_version',
                ],
                'jsonapi' => [
                    'version',
                ],
            ]);
    }
}
