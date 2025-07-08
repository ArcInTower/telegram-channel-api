<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TelegramChannelService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class TelegramController extends Controller
{
    private TelegramChannelService $telegramService;
    
    public function __construct(TelegramChannelService $telegramService)
    {
        $this->telegramService = $telegramService;
    }
    
    public function getLastMessageId(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'channel' => 'required|string|min:1|max:100|regex:/^[a-zA-Z0-9_@]+$/'
            ]);
            
            $channel = $validated['channel'];
            $lastMessageId = $this->telegramService->getLastMessageId($channel);
            
            if ($lastMessageId === null) {
                return response()->json([
                    'success' => false,
                    'error' => 'Channel not found or no messages available',
                    'channel' => $channel
                ], 404);
            }
            
            // Get cache info
            $cache = \App\Models\TelegramCache::where('channel_username', $channel)->first();
            $fromCache = false;
            $cacheAge = null;
            
            if ($cache && $cache->last_checked_at) {
                $fromCache = !$cache->last_checked_at->greaterThan(now()->subSeconds(5)); // If checked within last 5 seconds, it's from cache
                $cacheAge = $cache->last_checked_at->diffInSeconds(now());
            }
            
            return response()->json([
                'success' => true,
                'channel' => $channel,
                'last_message_id' => $lastMessageId,
                'from_cache' => $fromCache,
                'cache_age_seconds' => $cacheAge,
                'timestamp' => now()->toISOString()
            ]);
            
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid channel format',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('Error in getLastMessageId: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Internal server error'
            ], 500);
        }
    }
}