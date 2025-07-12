@extends('layouts.app')

@section('title', 'Architecture - Laravel Telegram API')

@section('content')
<div class="mt-8">
        <h1 class="text-4xl font-bold mb-8 text-gray-800">Architecture & Design Decisions</h1>
        
        <div class="prose max-w-none">
            <p class="text-lg text-gray-600 mb-8">
                This project follows a clear hierarchy of principles:
            </p>
            <ol class="list-decimal list-inside text-lg mb-8 space-y-2">
                <li><strong>Laravel conventions</strong> - We follow Laravel's established patterns and best practices</li>
                <li><strong>PHP standards</strong> - We adhere to PHP-FIG standards (PSR-4, PSR-12)</li>
                <li><strong>Clean code principles</strong> - SOLID principles applied pragmatically, avoiding over-engineering</li>
            </ol>

            <!-- Laravel Conventions -->
            <section class="mb-12 bg-white rounded-lg shadow-md p-6">
                <h2 class="text-2xl font-bold mb-4 text-blue-700">🌙 Laravel Conventions We Follow</h2>
                
                <div class="mb-6">
                    <h3 class="text-xl font-semibold mb-2">Directory Structure</h3>
                    <p>We follow the standard <a href="https://laravel.com/docs/12.x/structure" target="_blank" class="text-blue-600 hover:underline">Laravel directory structure</a>:</p>
                    <ul class="list-disc list-inside ml-4 space-y-1">
                        <li><code>app/Http/Controllers/</code> - HTTP controllers</li>
                        <li><code>app/Services/</code> - Business logic services</li>
                        <li><code>routes/api.php</code> - API route definitions</li>
                        <li><code>config/</code> - Configuration files</li>
                        <li><code>tests/</code> - Unit and feature tests</li>
                    </ul>
                </div>

                <div class="mb-6">
                    <h3 class="text-xl font-semibold mb-2">Service Container & Dependency Injection</h3>
                    <p>We use Laravel's <a href="https://laravel.com/docs/12.x/container" target="_blank" class="text-blue-600 hover:underline">Service Container</a> for dependency injection:</p>
                    <pre><code class="language-php">public function __construct(
    private TelegramChannelService $telegramService,
    private MessageService $messageService,
) {}</code></pre>
                </div>

                <div class="mb-6">
                    <h3 class="text-xl font-semibold mb-2">Service Providers</h3>
                    <p>Custom services are registered in <a href="https://laravel.com/docs/12.x/providers" target="_blank" class="text-blue-600 hover:underline">Service Providers</a>:</p>
                    <pre><code class="language-php">// app/Providers/TelegramServiceProvider.php
$this->app->singleton(TelegramApiInterface::class, function ($app) {
    return new MadelineProtoApiClient();
});</code></pre>
                </div>

                <div class="mb-6">
                    <h3 class="text-xl font-semibold mb-2">Route Model Binding</h3>
                    <p>We use <a href="https://laravel.com/docs/12.x/routing#route-model-binding" target="_blank" class="text-blue-600 hover:underline">implicit binding</a> in routes:</p>
                    <pre><code class="language-php">Route::get('/channels/{channel}/messages/last-id', ...)</code></pre>
                </div>

                <div class="mb-6">
                    <h3 class="text-xl font-semibold mb-2">Caching</h3>
                    <p>We use Laravel's <a href="https://laravel.com/docs/12.x/cache" target="_blank" class="text-blue-600 hover:underline">Cache facade</a>:</p>
                    <pre><code class="language-php">Cache::put($cacheKey, $data, $ttl);
$cachedData = Cache::get($cacheKey);</code></pre>
                </div>
            </section>

            <!-- PHP Standards -->
            <section class="mb-12 bg-white rounded-lg shadow-md p-6">
                <h2 class="text-2xl font-bold mb-4 text-indigo-700">⚙️ PHP Standards & Best Practices</h2>
                
                <div class="mb-6">
                    <h3 class="text-xl font-semibold mb-2">PSR Standards</h3>
                    <ul class="list-disc list-inside ml-4 space-y-1">
                        <li><strong>PSR-4</strong>: Autoloading - <code>App\</code> namespace maps to <code>app/</code> directory</li>
                        <li><strong>PSR-12</strong>: Coding style - Enforced by Laravel Pint</li>
                        <li><strong>PSR-7</strong>: HTTP messages - Used implicitly through Laravel</li>
                    </ul>
                </div>

                <div class="mb-6">
                    <h3 class="text-xl font-semibold mb-2">Type Declarations</h3>
                    <p>We use PHP 8+ type declarations everywhere:</p>
                    <pre><code class="language-php">public function getLastMessageId(string $channel): ?int
{
    // Return type is nullable int
}</code></pre>
                </div>

                <div class="mb-6">
                    <h3 class="text-xl font-semibold mb-2">Constructor Property Promotion</h3>
                    <p>Using PHP 8's constructor property promotion for cleaner code:</p>
                    <pre><code class="language-php">public function __construct(
    private TelegramChannelService $telegramService,
    private MessageService $messageService,
) {}</code></pre>
                </div>
            </section>

            <!-- Custom Design Decisions -->
            <section class="mb-12 bg-white rounded-lg shadow-md p-6">
                <h2 class="text-2xl font-bold mb-4 text-green-700">✨ Our Design Decisions</h2>
                
                <div class="mb-6">
                    <h3 class="text-xl font-semibold mb-2">Request/Response Objects Pattern</h3>
                    <p class="mb-2">Instead of using arrays or stdClass, we use dedicated Request and Response objects:</p>
                    <pre><code class="language-php">// Custom Request Objects
app/Http/Requests/Api/V2/GetLastMessageRequest.php
app/Http/Requests/Api/V2/GetStatisticsRequest.php

// Custom Response Objects  
app/Http/Responses/Api/V2/LastMessageResponse.php
app/Http/Responses/Api/V2/ErrorResponse.php</code></pre>
                    <p><strong>Why?</strong> Type safety, IDE autocompletion, and cleaner controllers. Inspired by 
                    <a href="https://martinfowler.com/eaaCatalog/dataTransferObject.html" target="_blank" class="text-blue-600 hover:underline">DTO pattern</a>.</p>
                </div>

                <div class="mb-6">
                    <h3 class="text-xl font-semibold mb-2">JSON:API Specification</h3>
                    <p class="mb-2">We implement <a href="https://jsonapi.org/format/1.1/" target="_blank" class="text-blue-600 hover:underline">JSON:API v1.1</a> for v2 endpoints:</p>
                    <pre><code class="language-json">{
    "data": {
        "type": "channel-message",
        "id": "channelname",
        "attributes": {
            "last_message_id": 12345
        }
    },
    "meta": {
        "timestamp": "2025-01-10T12:00:00Z",
        "api_version": "v2"
    },
    "jsonapi": {
        "version": "1.1"
    }
}</code></pre>
                    <p><strong>Why?</strong> Standardized format, better for API consumers, supports relationships and includes.</p>
                </div>

                <div class="mb-6">
                    <h3 class="text-xl font-semibold mb-2">Service Layer Pattern</h3>
                    <p class="mb-2">Business logic is extracted into service classes:</p>
                    <ul class="list-disc list-inside ml-4 space-y-1 mb-2">
                        <li><code>MessageService</code> - Handles message fetching and caching</li>
                        <li><code>StatisticsService</code> - Processes channel statistics</li>
                        <li><code>StatisticsCalculator</code> - Complex statistics calculations</li>
                    </ul>
                    <p><strong>Why?</strong> Follows <a href="https://martinfowler.com/eaaCatalog/serviceLayer.html" target="_blank" class="text-blue-600 hover:underline">Service Layer pattern</a>, 
                    keeps controllers thin, improves testability.</p>
                </div>

                <div class="mb-6">
                    <h3 class="text-xl font-semibold mb-2">Minimal Interface Usage</h3>
                    <p class="mb-2">We only use interfaces for external dependencies that might change:</p>
                    <pre><code class="language-php">// Only for the Telegram client that could be swapped
interface TelegramApiInterface {
    public function getChannelInfo(string $channel): ?array;
    public function getMessagesHistory(string $channel, array $params): array;
}</code></pre>
                    <p><strong>Why?</strong> Avoids over-engineering. Internal services don't need interfaces unless there's a real need for multiple implementations.</p>
                </div>

                <div class="mb-6">
                    <h3 class="text-xl font-semibold mb-2">Controller Separation</h3>
                    <p class="mb-2">Each controller has a single responsibility:</p>
                    <ul class="list-disc list-inside ml-4 space-y-1 mb-2">
                        <li><code>MessageController</code> - Message-related endpoints</li>
                        <li><code>StatisticsController</code> - Statistics endpoints</li>
                        <li><code>ChannelInfoController</code> - Channel information</li>
                    </ul>
                    <p><strong>Why?</strong> Follows <a href="https://en.wikipedia.org/wiki/Single-responsibility_principle" target="_blank" class="text-blue-600 hover:underline">Single Responsibility Principle</a>, 
                    easier to maintain and test.</p>
                </div>
            </section>

            <!-- What We DON'T Do -->
            <section class="mb-12 bg-white rounded-lg shadow-md p-6">
                <h2 class="text-2xl font-bold mb-4 text-red-700">🚫 What We Avoid (No Over-Engineering)</h2>
                
                <div class="mb-6">
                    <h3 class="text-xl font-semibold mb-2">No Unnecessary Abstractions</h3>
                    <ul class="list-disc list-inside ml-4 space-y-1">
                        <li>No repository pattern for simple cache operations</li>
                        <li>No interfaces for services that have only one implementation</li>
                        <li>No abstract classes unless there's real shared behavior</li>
                        <li>No design patterns just for the sake of using them</li>
                    </ul>
                </div>

                <div class="mb-6">
                    <h3 class="text-xl font-semibold mb-2">YAGNI Principle</h3>
                    <p class="mb-2">"You Aren't Gonna Need It" - We don't add features or abstractions for hypothetical future needs:</p>
                    <ul class="list-disc list-inside ml-4 space-y-1">
                        <li>No multi-database support (just use Laravel's config)</li>
                        <li>No plugin system</li>
                        <li>No event sourcing or CQRS</li>
                        <li>No microservices architecture for a simple API</li>
                    </ul>
                </div>

                <div class="mb-6">
                    <h3 class="text-xl font-semibold mb-2">Pragmatic SOLID</h3>
                    <p>We apply SOLID principles where they add value, not dogmatically:</p>
                    <pre><code class="language-php">// Good: Service handles one clear responsibility
class MessageService {
    public function getLastMessageId(string $channel): ?int

// Overkill: Interface for a service with one implementation
interface MessageServiceInterface // ❌ We don't need this</code></pre>
                </div>
            </section>

            <!-- Testing Strategy -->
            <section class="mb-12 bg-white rounded-lg shadow-md p-6">
                <h2 class="text-2xl font-bold mb-4 text-purple-700">🔬 Testing Strategy</h2>
                
                <div class="mb-6">
                    <h3 class="text-xl font-semibold mb-2">Unit Tests</h3>
                    <p>Service classes are tested in isolation using <a href="http://docs.mockery.io/en/latest/" target="_blank" class="text-blue-600 hover:underline">Mockery</a>:</p>
                    <pre><code class="language-php">$this->apiClient = Mockery::mock(TelegramApiInterface::class);
$this->apiClient->shouldReceive('getChannelInfo')
    ->with('@' . $channelUsername)
    ->once()
    ->andReturn(['type' => 'channel']);</code></pre>
                </div>

                <div class="mb-6">
                    <h3 class="text-xl font-semibold mb-2">Feature Tests</h3>
                    <p>API endpoints are tested using Laravel's <a href="https://laravel.com/docs/12.x/http-tests" target="_blank" class="text-blue-600 hover:underline">HTTP tests</a>:</p>
                    <pre><code class="language-php">$response = $this->getJson("/api/v2/telegram/channels/{$channel}/messages/last-id");
$response->assertStatus(200)
    ->assertJsonStructure(['data', 'meta', 'jsonapi']);</code></pre>
                </div>
            </section>

            <!-- Performance Decisions -->
            <section class="mb-12 bg-white rounded-lg shadow-md p-6">
                <h2 class="text-2xl font-bold mb-4 text-orange-700">⚡ Performance Optimizations</h2>
                
                <div class="mb-6">
                    <h3 class="text-xl font-semibold mb-2">Caching Strategy</h3>
                    <ul class="list-disc list-inside ml-4 space-y-1">
                        <li>Message IDs: 5 minutes cache</li>
                        <li>Statistics: 1 hour cache</li>
                        <li>Cache keys include parameters for granular invalidation</li>
                    </ul>
                </div>

                <div class="mb-6">
                    <h3 class="text-xl font-semibold mb-2">Rate Limiting</h3>
                    <p>Different limits for different endpoints:</p>
                    <pre><code class="language-php">$channelMiddleware = ['throttle:60,1'];  // 60 requests per minute
$statsMiddleware = ['throttle:10,60'];   // 10 requests per hour</code></pre>
                </div>
            </section>

            <!-- Further Reading -->
            <section class="mb-12 bg-white rounded-lg shadow-md p-6">
                <h2 class="text-2xl font-bold mb-4 text-indigo-700">🌠 Further Reading</h2>
                
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-xl font-semibold mb-2">Laravel Resources</h3>
                        <ul class="list-disc list-inside ml-4 space-y-1">
                            <li><a href="https://laravel.com/docs/12.x" target="_blank" class="text-blue-600 hover:underline">Laravel Documentation</a></li>
                            <li><a href="https://laravel.com/docs/12.x/eloquent-resources" target="_blank" class="text-blue-600 hover:underline">API Resources</a></li>
                            <li><a href="https://laravel.com/docs/12.x/validation#form-request-validation" target="_blank" class="text-blue-600 hover:underline">Form Request Validation</a></li>
                            <li><a href="https://laracasts.com/topics/architecture" target="_blank" class="text-blue-600 hover:underline">Laracasts Architecture</a></li>
                        </ul>
                    </div>
                    
                    <div>
                        <h3 class="text-xl font-semibold mb-2">Design Patterns</h3>
                        <ul class="list-disc list-inside ml-4 space-y-1">
                            <li><a href="https://jsonapi.org/" target="_blank" class="text-blue-600 hover:underline">JSON:API Specification</a></li>
                            <li><a href="https://martinfowler.com/eaaCatalog/serviceLayer.html" target="_blank" class="text-blue-600 hover:underline">Service Layer Pattern</a></li>
                            <li><a href="https://refactoring.guru/design-patterns/catalog" target="_blank" class="text-blue-600 hover:underline">Design Patterns Catalog</a></li>
                            <li><a href="https://adam-wathan.com/factoring-out-form-objects/" target="_blank" class="text-blue-600 hover:underline">Form Objects Pattern</a></li>
                        </ul>
                    </div>
                </div>
            </section>
        </div>
</div>
@endsection

@push('scripts')
    @vite('resources/js/highlight.js')
@endpush