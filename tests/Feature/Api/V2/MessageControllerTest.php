<?php

namespace Tests\Feature\Api\V2;

use App\Services\Telegram\MessageService;
use App\Services\TelegramChannelService;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class MessageControllerTest extends TestCase
{
    private $telegramService;
    private $messageService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->telegramService = Mockery::mock(TelegramChannelService::class);
        $this->app->instance(TelegramChannelService::class, $this->telegramService);

        $this->messageService = Mockery::mock(MessageService::class);
        $this->app->instance(MessageService::class, $this->messageService);

        // Clear cache before each test
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_get_last_message_returns_success_response()
    {
        $channel = 'laravel';
        $messageId = 12345;

        $this->messageService
            ->shouldReceive('getLastMessageId')
            ->with($channel)
            ->once()
            ->andReturn([
                'channel' => $channel,
                'last_message_id' => $messageId,
            ]);

        $this->messageService
            ->shouldReceive('getCacheMetadataForChannel')
            ->with($channel)
            ->once()
            ->andReturn([
                'from_cache' => false,
                'cached_at' => null,
                'cache_ttl_seconds' => 300,
            ]);

        $response = $this->getJson("/api/v2/telegram/channels/{$channel}/messages/last-id");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'type',
                    'id',
                    'attributes' => [
                        'last_message_id',
                    ],
                ],
                'meta' => [
                    'timestamp',
                    'api_version',
                ],
                'jsonapi' => [
                    'version',
                ],
            ])
            ->assertJson([
                'data' => [
                    'type' => 'channel-message',
                    'id' => $channel,
                    'attributes' => [
                        'last_message_id' => $messageId,
                    ],
                ],
            ]);
    }

    public function test_get_last_message_returns_404_when_channel_not_found()
    {
        $channel = 'thisChannelDefinitelyDoesNotExist123456789';

        $this->messageService
            ->shouldReceive('getLastMessageId')
            ->with($channel)
            ->once()
            ->andReturn(null);

        $response = $this->getJson("/api/v2/telegram/channels/{$channel}/messages/last-id");

        $response->assertStatus(404)
            ->assertJsonStructure([
                'errors' => [
                    '*' => [
                        'status',
                        'title',
                        'detail',
                    ],
                ],
                'meta' => [
                    'timestamp',
                    'api_version',
                ],
                'jsonapi' => [
                    'version',
                ],
            ])
            ->assertJson([
                'errors' => [
                    [
                        'status' => '404',
                        'title' => 'Not Found',
                        'detail' => 'Channel not found or no messages available',
                    ],
                ],
            ]);
    }

    public function test_get_last_message_validates_channel_format()
    {
        $invalidChannel = 'invalid channel!';

        $response = $this->getJson("/api/v2/telegram/channels/{$invalidChannel}/messages/last-id");

        $response->assertStatus(400)
            ->assertJson([
                'errors' => [
                    [
                        'status' => '400',
                        'title' => 'Bad Request',
                        'detail' => 'Invalid channel format. Use only letters, numbers, underscore and @.',
                    ],
                ],
            ]);
    }
}
