<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\AnonymizeUsers;
use App\Services\UserAnonymizationService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class AnonymizeUsersTest extends TestCase
{
    private AnonymizeUsers $middleware;
    private UserAnonymizationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new UserAnonymizationService;
        $this->middleware = new AnonymizeUsers($this->service);
    }

    public function test_middleware_anonymizes_json_api_responses()
    {
        Config::set('telegram.anonymized_users', ['testuser']);

        $request = Request::create('/api/test', 'GET');

        $response = new Response(json_encode([
            'data' => [
                'username' => 'testuser',
                'message' => 'Hello from testuser',
            ],
        ]), 200, ['Content-Type' => 'application/vnd.api+json']);

        $next = function ($request) use ($response) {
            return $response;
        };

        $result = $this->middleware->handle($request, $next);
        $content = json_decode($result->getContent(), true);

        $this->assertEquals('t******r', $content['data']['username']);
        $this->assertEquals('Hello from testuser', $content['data']['message']);
    }

    public function test_middleware_anonymizes_regular_json_responses()
    {
        Config::set('telegram.anonymized_users', ['john']);

        $request = Request::create('/api/test', 'GET');

        $response = new Response(json_encode([
            'user' => 'john',
            'from' => '@john',
        ]), 200, ['Content-Type' => 'application/json']);

        $next = function ($request) use ($response) {
            return $response;
        };

        $result = $this->middleware->handle($request, $next);
        $content = json_decode($result->getContent(), true);

        $this->assertEquals('j**n', $content['user']);
        $this->assertEquals('@j**n', $content['from']);
    }

    public function test_middleware_ignores_non_json_responses()
    {
        Config::set('telegram.anonymized_users', ['testuser']);

        $request = Request::create('/api/test', 'GET');

        $htmlContent = '<html><body>Hello testuser</body></html>';
        $response = new Response($htmlContent, 200, ['Content-Type' => 'text/html']);

        $next = function ($request) use ($response) {
            return $response;
        };

        $result = $this->middleware->handle($request, $next);

        $this->assertEquals($htmlContent, $result->getContent());
        $this->assertStringContainsString('testuser', $result->getContent());
    }

    public function test_middleware_handles_nested_data_structures()
    {
        Config::set('telegram.anonymized_users', ['alice', 'bob']);

        $request = Request::create('/api/test', 'GET');

        $response = new Response(json_encode([
            'data' => [
                'messages' => [
                    ['from' => 'alice', 'text' => 'Hi'],
                    ['from' => '@bob', 'text' => 'Hello'],
                ],
                'users' => [
                    ['username' => 'alice', 'id' => 1],
                    ['username' => 'bob', 'id' => 2],
                ],
            ],
        ]), 200, ['Content-Type' => 'application/json']);

        $next = function ($request) use ($response) {
            return $response;
        };

        $result = $this->middleware->handle($request, $next);
        $content = json_decode($result->getContent(), true);

        $this->assertEquals('a***e', $content['data']['messages'][0]['from']);
        $this->assertEquals('@b*b', $content['data']['messages'][1]['from']);
        $this->assertEquals('a***e', $content['data']['users'][0]['username']);
        $this->assertEquals('b*b', $content['data']['users'][1]['username']);
    }

    public function test_middleware_handles_null_json_content()
    {
        $request = Request::create('/api/test', 'GET');

        $response = new Response('invalid json', 200, ['Content-Type' => 'application/json']);

        $next = function ($request) use ($response) {
            return $response;
        };

        $result = $this->middleware->handle($request, $next);

        $this->assertEquals('invalid json', $result->getContent());
    }
}
