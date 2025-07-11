<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureJsonResponse
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Ensure we're expecting JSON
        if (!$request->wantsJson()) {
            $request->headers->set('Accept', 'application/json');
        }

        // Capture any output that might be sent before the response
        ob_start();

        try {
            $response = $next($request);

            // Get any output that was captured
            $output = ob_get_clean();

            // If there's output and it looks like HTML (MadelineProto login page)
            if ($output && (str_contains($output, 'MadelineProto') || str_contains($output, '<!DOCTYPE'))) {
                return response()->json([
                    'jsonapi' => ['version' => '1.1'],
                    'errors' => [[
                        'status' => '401',
                        'title' => 'Authentication Required',
                        'detail' => 'The Telegram session has expired or been revoked. Please contact the administrator to re-authenticate the bot.',
                    ]],
                    'meta' => [
                        'timestamp' => now()->toISOString(),
                        'api_version' => 'v2',
                    ],
                ], 401);
            }

            // If there was output but we have a valid response, just log it
            if ($output) {
                \Log::warning('Unexpected output in API request', ['output' => substr($output, 0, 500)]);
            }

            return $response;
        } catch (\Throwable $e) {
            ob_end_clean();

            throw $e;
        }
    }
}
