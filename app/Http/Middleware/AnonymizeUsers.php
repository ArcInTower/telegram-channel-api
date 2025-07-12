<?php

namespace App\Http\Middleware;

use App\Services\UserAnonymizationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AnonymizeUsers
{
    protected UserAnonymizationService $anonymizationService;

    public function __construct(UserAnonymizationService $anonymizationService)
    {
        $this->anonymizationService = $anonymizationService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only process JSON responses
        if ($response->headers->get('content-type') === 'application/vnd.api+json' ||
            str_contains($response->headers->get('content-type'), 'application/json')) {

            $content = json_decode($response->getContent(), true);

            if ($content !== null) {
                $processedContent = $this->anonymizationService->processData($content);
                $response->setContent(json_encode($processedContent));
            }
        }

        return $response;
    }
}
