<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckBlockedChannel
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get channel from route parameter or request
        $channel = $request->route('channel') ??
                  $request->input('channel') ??
                  $request->input('channels.0');

        if ($channel) {
            $channel = $this->normalizeChannel($channel);
            $blockedChannels = config('telegram.blocked_channels', []);

            // Check if channel is blocked
            if (in_array($channel, array_map([$this, 'normalizeChannel'], $blockedChannels))) {
                return response()->json([
                    'errors' => [
                        [
                            'status' => '403',
                            'title' => 'Access Denied',
                            'detail' => 'This channel is not available for access.',
                        ],
                    ],
                ], 403);
            }
        }

        // Check for multiple channels (comparison endpoint)
        $channels = $request->input('channels', []);
        if (!empty($channels)) {
            $blockedChannels = config('telegram.blocked_channels', []);
            $normalizedBlocked = array_map([$this, 'normalizeChannel'], $blockedChannels);

            foreach ($channels as $channel) {
                if (in_array($this->normalizeChannel($channel), $normalizedBlocked)) {
                    return response()->json([
                        'errors' => [
                            [
                                'status' => '403',
                                'title' => 'Access Denied',
                                'detail' => "Channel '@{$channel}' is not available for access.",
                            ],
                        ],
                    ], 403);
                }
            }
        }

        return $next($request);
    }

    /**
     * Normalize channel username
     */
    private function normalizeChannel(string $channel): string
    {
        // Remove @ symbol and convert to lowercase
        $normalized = ltrim($channel, '@');
        
        return strtolower($normalized);
    }
}
