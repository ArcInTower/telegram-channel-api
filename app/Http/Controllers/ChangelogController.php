<?php

namespace App\Http\Controllers;

class ChangelogController extends Controller
{
    public function index()
    {
        $changelog = [
            [
                'version' => '2.1.0',
                'date' => '2025-07-19',
                'type' => 'minor',
                'changes' => [
                    'Added' => [
                        'Reactions API endpoint: GET /api/v2/telegram/channels/{channel}/reactions',
                        'Polls API endpoint: GET /api/v2/telegram/channels/{channel}/polls',
                        'Top Contributors API endpoint (BETA): GET /api/v2/telegram/channels/{channel}/top-contributors',
                        'Dynamic cache TTL based on period for better performance',
                    ],
                    'Changed' => [
                        'Optimized reactions API to fetch fewer messages based on period',
                        'Cache TTL now varies by period (5 min for 1hour, up to 24h for 1year)',
                    ],
                    'Fixed' => [
                        'Authentication error handling across all endpoints',
                    ],
                ],
            ],
            [
                'version' => '2.0.0',
                'date' => '2025-07-10',
                'type' => 'major',
                'changes' => [
                    'Added' => [
                        'New v2 API with JSON:API v1.1 specification',
                        'Channel statistics endpoint: GET /api/v2/telegram/channels/{channel}/statistics/{days}',
                        'Channel info endpoint: GET /api/v2/telegram/channels/{channel}',
                        'Channel comparison endpoint: POST /api/v2/telegram/channels/compare',
                    ],
                    'Changed' => [
                        'Statistics limited to 15 days maximum',
                        'Statistics results cached for 1 hour',
                    ],
                    'Deprecated' => [
                        'v1 API endpoint (/api/telegram/last-message) - migrate to v2',
                    ],
                ],
            ],
            [
                'version' => '1.0.0',
                'date' => '2025-07-07',
                'type' => 'major',
                'changes' => [
                    'Added' => [
                        'Initial release',
                        'Get last message ID endpoint',
                        '5 minute cache for API responses',
                    ],
                ],
            ],
        ];

        return view('changelog', compact('changelog'));
    }
}
