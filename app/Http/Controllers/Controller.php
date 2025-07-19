<?php

namespace App\Http\Controllers;

use App\Http\Responses\Api\V2\ErrorResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

abstract class Controller
{
    /**
     * Handle Telegram API exceptions with proper error responses
     *
     * @param \Exception $e The exception to handle
     * @param string $context The context/method where the error occurred
     * @return JsonResponse
     */
    protected function handleTelegramException(\Exception $e, string $context): JsonResponse
    {
        Log::error("Error in $context: " . $e->getMessage());
        
        if ($this->isAuthenticationError($e)) {
            return (new ErrorResponse('Authentication required. Please re-authenticate with Telegram.', 401))->toResponse();
        }
        
        return (new ErrorResponse('Internal server error', 500))->toResponse();
    }
    
    /**
     * Check if the exception is related to Telegram authentication
     *
     * @param \Exception $e
     * @return bool
     */
    protected function isAuthenticationError(\Exception $e): bool
    {
        $message = $e->getMessage();
        
        return str_contains($message, 'AUTH_KEY_UNREGISTERED') ||
               str_contains($message, 'SESSION_REVOKED') ||
               str_contains($message, 'LOGIN_REQUIRED') ||
               str_contains($message, 'authentication required');
    }
}
