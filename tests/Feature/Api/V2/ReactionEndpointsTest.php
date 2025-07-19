<?php

namespace Tests\Feature\Api\V2;

use Tests\TestCase;
use App\Services\Telegram\ReactionService;
use Mockery;

class ReactionEndpointsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Skip if in safe testing mode
        if (env('SAFE_TESTING', true)) {
            $this->markTestSkipped('Skipping in safe testing mode');
        }
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_channel_reactions_endpoint_returns_correct_structure()
    {
        // Mock the ReactionService
        $mockData = [
            'channel' => 'testchannel',
            'analyzed_messages' => 100,
            'messages_with_reactions' => 25,
            'total_reactions' => 150,
            'average_reactions_per_message' => 6.0,
            'engagement_rate' => 25.0,
            'reaction_types' => [
                ['emoji' => '❤️', 'count' => 50, 'is_premium' => false],
                ['emoji' => '👍', 'count' => 30, 'is_premium' => false],
            ]
        ];

        $mockService = Mockery::mock(ReactionService::class);
        $mockService->shouldReceive('getChannelReactions')
            ->with('testchannel', '7days', 100)
            ->andReturn($mockData);
        $mockService->shouldReceive('getCacheMetadataForChannel')
            ->andReturn(['from_cache' => false, 'cached_at' => null, 'expires_at' => null]);

        $this->app->instance(ReactionService::class, $mockService);

        $response = $this->getJson('/api/v2/telegram/channels/testchannel/reactions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'channel',
                    'analyzed_messages',
                    'messages_with_reactions',
                    'total_reactions',
                    'average_reactions_per_message',
                    'engagement_rate',
                    'reaction_types' => [
                        '*' => ['emoji', 'count', 'is_premium']
                    ]
                ],
                'meta' => [
                    'api_version',
                    'cache'
                ]
            ]);
    }

    public function test_top_messages_endpoint_with_period_filter()
    {
        $mockData = [
            'channel' => 'testchannel',
            'period' => '30days',
            'top_messages' => [
                [
                    'message_id' => 123,
                    'text' => 'Test message',
                    'date' => '2024-01-01T00:00:00+00:00',
                    'reaction_count' => 50,
                    'views' => 1000,
                    'engagement_rate' => 5.0,
                    'reactions' => [
                        ['emoji' => '❤️', 'count' => 30, 'is_premium' => false, 'chosen' => false]
                    ]
                ]
            ]
        ];

        $mockService = Mockery::mock(ReactionService::class);
        $mockService->shouldReceive('getTopMessagesByReactions')
            ->with('testchannel', '30days', 10)
            ->andReturn($mockData);
        $mockService->shouldReceive('getCacheMetadataForTopMessages')
            ->andReturn(['from_cache' => true, 'cached_at' => now()->toIso8601String(), 'expires_at' => null]);

        $this->app->instance(ReactionService::class, $mockService);

        $response = $this->getJson('/api/v2/telegram/channels/testchannel/reactions/top-messages?period=30days');

        $response->assertStatus(200)
            ->assertJsonPath('data.period', '30days')
            ->assertJsonStructure([
                'data' => [
                    'channel',
                    'period',
                    'top_messages' => [
                        '*' => [
                            'message_id',
                            'text',
                            'date',
                            'reaction_count',
                            'views',
                            'engagement_rate',
                            'reactions'
                        ]
                    ]
                ],
                'meta'
            ]);
    }

    public function test_message_reactions_endpoint_returns_404_when_not_found()
    {
        $mockService = Mockery::mock(ReactionService::class);
        $mockService->shouldReceive('getMessageReactions')
            ->with('testchannel', 99999)
            ->andReturn(null);

        $this->app->instance(ReactionService::class, $mockService);

        $response = $this->getJson('/api/v2/telegram/channels/testchannel/messages/99999/reactions');

        $response->assertStatus(404)
            ->assertJsonPath('errors.0.detail', 'Message not found or no reactions available');
    }

    public function test_channel_reactions_validates_input_parameters()
    {
        $response = $this->getJson('/api/v2/telegram/channels/test-channel!/reactions?limit=2000');

        $response->assertStatus(422);
    }

    public function test_top_messages_validates_period_parameter()
    {
        $response = $this->getJson('/api/v2/telegram/channels/testchannel/reactions/top-messages?period=invalid');

        $response->assertStatus(422);
    }

    public function test_authentication_error_returns_401()
    {
        $mockService = Mockery::mock(ReactionService::class);
        $mockService->shouldReceive('getChannelReactions')
            ->andThrow(new \Exception('AUTH_KEY_UNREGISTERED'));

        $this->app->instance(ReactionService::class, $mockService);

        $response = $this->getJson('/api/v2/telegram/channels/testchannel/reactions');

        $response->assertStatus(401)
            ->assertJsonPath('errors.0.detail', 'Authentication required. Please re-authenticate with Telegram.');
    }
}