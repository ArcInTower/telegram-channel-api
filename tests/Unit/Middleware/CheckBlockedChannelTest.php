<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\CheckBlockedChannel;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class CheckBlockedChannelTest extends TestCase
{
    private CheckBlockedChannel $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new CheckBlockedChannel;
    }

    public function test_allows_non_blocked_channel()
    {
        Config::set('telegram.blocked_channels', ['blockedchannel', 'anotherblockedone']);

        $request = Request::create('/api/v2/telegram/channels/allowedchannel/messages/last-id', 'GET');
        $request->setRouteResolver(function () use ($request) {
            $route = new \Illuminate\Routing\Route('GET', '/api/v2/telegram/channels/{channel}/messages/last-id', []);
            $route->bind($request);
            $route->setParameter('channel', 'allowedchannel');

            return $route;
        });

        $next = function ($request) {
            return new Response('OK', 200);
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_blocks_configured_channel()
    {
        Config::set('telegram.blocked_channels', ['blockedchannel', 'anotherblockedone']);

        $request = Request::create('/api/v2/telegram/channels/blockedchannel/messages/last-id', 'GET');
        $request->setRouteResolver(function () use ($request) {
            $route = new \Illuminate\Routing\Route('GET', '/api/v2/telegram/channels/{channel}/messages/last-id', []);
            $route->bind($request);
            $route->setParameter('channel', 'blockedchannel');

            return $route;
        });

        $next = function ($request) {
            return new Response('OK', 200);
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertEquals(403, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertEquals('1.1', $content['jsonapi']['version']);
        $this->assertEquals('403', $content['errors'][0]['status']);
        $this->assertEquals('CHANNEL_BLOCKED', $content['errors'][0]['code']);
        $this->assertEquals('Access Denied', $content['errors'][0]['title']);
    }

    public function test_normalizes_channel_names()
    {
        Config::set('telegram.blocked_channels', ['blockedchannel']);

        // Test with @ symbol and uppercase
        $request = Request::create('/api/v2/telegram/channels/@BlockedChannel/messages/last-id', 'GET');
        $request->setRouteResolver(function () use ($request) {
            $route = new \Illuminate\Routing\Route('GET', '/api/v2/telegram/channels/{channel}/messages/last-id', []);
            $route->bind($request);
            $route->setParameter('channel', '@BlockedChannel');

            return $route;
        });

        $next = function ($request) {
            return new Response('OK', 200);
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function test_blocks_channel_from_request_input()
    {
        Config::set('telegram.blocked_channels', ['blockedchannel']);

        $request = Request::create('/api/compare', 'POST');
        $request->merge(['channel' => 'blockedchannel']);

        $next = function ($request) {
            return new Response('OK', 200);
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function test_blocks_channel_from_channels_array()
    {
        Config::set('telegram.blocked_channels', ['blockedchannel', 'anotherblockedone']);

        $request = Request::create('/api/compare', 'POST');
        $request->merge([
            'channels' => ['allowedchannel', 'blockedchannel', 'anotherallowed'],
        ]);

        $next = function ($request) {
            return new Response('OK', 200);
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertEquals(403, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertStringContainsString('@blockedchannel', $content['errors'][0]['detail']);
    }

    public function test_allows_when_no_channel_specified()
    {
        Config::set('telegram.blocked_channels', ['blockedchannel']);

        $request = Request::create('/api/other-endpoint', 'GET');

        $next = function ($request) {
            return new Response('OK', 200);
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_handles_empty_blocked_channels_config()
    {
        Config::set('telegram.blocked_channels', []);

        $request = Request::create('/api/v2/telegram/channels/anychannel/messages/last-id', 'GET');
        $request->setRouteResolver(function () use ($request) {
            $route = new \Illuminate\Routing\Route('GET', '/api/v2/telegram/channels/{channel}/messages/last-id', []);
            $route->bind($request);
            $route->setParameter('channel', 'anychannel');

            return $route;
        });

        $next = function ($request) {
            return new Response('OK', 200);
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertEquals(200, $response->getStatusCode());
    }
}
