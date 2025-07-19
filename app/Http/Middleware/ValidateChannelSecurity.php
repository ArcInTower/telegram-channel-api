<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ValidateChannelSecurity
{
    /**
     * Dangerous patterns that could indicate attempts to access private data
     */
    private const DANGEROUS_PATTERNS = [
        // Numeric IDs
        '/^-?\d+$/',
        // Path traversal
        '/\.\./',
        // URL encoded traversal
        '/%2e%2e/i',
        // Special telegram IDs
        '/^(self|me|saved|settings|bot|botfather)$/i',
        // Suspicious formats
        '/[<>\"\'`\{\}\[\]]/',
        // Null bytes
        '/\x00/',
    ];

    /**
     * Get rate limit from config
     */
    private function getRateLimit(): int
    {
        return config('security.rate_limits.per_channel_per_hour', 300);
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $channel = $request->route('channel');
        
        if (!$channel) {
            return $next($request);
        }

        // Clean the channel name
        $channel = trim($channel);
        
        // Check dangerous patterns
        foreach (self::DANGEROUS_PATTERNS as $pattern) {
            // Debug log
            Log::debug('Testing pattern', ['pattern' => $pattern, 'channel' => $channel]);
            
            try {
                if (preg_match($pattern, $channel)) {
                Log::warning('Channel security validation failed', [
                    'channel' => $channel,
                    'pattern' => $pattern,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'url' => $request->fullUrl()
                ]);
                
                return response()->json([
                    'errors' => [[
                        'status' => '403',
                        'title' => 'Forbidden',
                        'detail' => 'Invalid channel format'
                    ]],
                    'meta' => [
                        'timestamp' => now()->toISOString(),
                        'api_version' => 'v2'
                    ]
                ], 403);
                }
            } catch (\Exception $e) {
                Log::error('Pattern matching error', [
                    'pattern' => $pattern,
                    'channel' => $channel,
                    'error' => $e->getMessage()
                ]);
                // Skip this pattern on error
                continue;
            }
        }

        // Check if channel is in blocklist
        if ($this->isChannelBlocked($channel)) {
            return response()->json([
                'errors' => [[
                    'status' => '403',
                    'title' => 'Forbidden',
                    'detail' => 'This channel is not accessible'
                ]],
                'meta' => [
                    'timestamp' => now()->toISOString(),
                    'api_version' => 'v2'
                ]
            ], 403);
        }

        // Rate limiting per channel
        if (!$this->checkRateLimit($channel, $request->ip())) {
            return response()->json([
                'errors' => [[
                    'status' => '429',
                    'title' => 'Too Many Requests',
                    'detail' => 'Rate limit exceeded for this channel'
                ]],
                'meta' => [
                    'timestamp' => now()->toISOString(),
                    'api_version' => 'v2'
                ]
            ], 429);
        }

        // Log access for security monitoring
        $this->logChannelAccess($channel, $request);

        return $next($request);
    }

    /**
     * Check if channel is in the blocklist
     */
    private function isChannelBlocked(string $channel): bool
    {
        $cleanChannel = strtolower(ltrim($channel, '@'));
        
        // Check whitelist mode first
        if (config('security.channels.whitelist_enabled')) {
            $whitelist = array_map(function($ch) {
                return strtolower(ltrim($ch, '@'));
            }, config('security.channels.whitelist', []));
            
            if (!in_array($cleanChannel, $whitelist)) {
                Log::warning('Channel not in whitelist', [
                    'channel' => $channel,
                    'ip' => request()->ip()
                ]);
                return true; // Block if not in whitelist
            }
        }
        
        // Check blocklist
        $blocklist = config('security.channels.blocklist', []);
        $dynamicBlocklist = Cache::get('channel_security_blocklist', []);
        
        return in_array($cleanChannel, $blocklist) || 
               in_array($cleanChannel, $dynamicBlocklist);
    }

    /**
     * Check rate limit for specific channel
     */
    private function checkRateLimit(string $channel, string $ip): bool
    {
        $key = 'channel_rate_limit:' . md5($channel . ':' . $ip);
        $attempts = Cache::get($key, 0);
        
        if ($attempts >= $this->getRateLimit()) {
            return false;
        }
        
        $attempts++;
        Cache::put($key, $attempts, 3600); // 1 hour
        
        return true;
    }

    /**
     * Log channel access for security monitoring
     */
    private function logChannelAccess(string $channel, Request $request): void
    {
        // Only log suspicious patterns
        if ($this->isSuspiciousChannel($channel)) {
            Log::info('Channel access logged for monitoring', [
                'channel' => $channel,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'endpoint' => $request->path(),
                'timestamp' => now()->toISOString()
            ]);
        }
    }

    /**
     * Check if channel name is suspicious
     */
    private function isSuspiciousChannel(string $channel): bool
    {
        // Channels with less than 5 characters
        if (strlen(ltrim($channel, '@')) < 5) {
            return true;
        }
        
        // Channels with special patterns
        if (preg_match('/^@?[a-z]{1,3}\d{1,10}$/i', $channel)) {
            return true;
        }
        
        return false;
    }
}