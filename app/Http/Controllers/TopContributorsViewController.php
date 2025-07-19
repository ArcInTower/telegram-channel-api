<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TopContributorsViewController extends Controller
{
    /**
     * Display the user values page for a channel
     */
    public function show(Request $request, string $channel, int $days = 7)
    {
        // Validate days
        if ($days < 1 || $days > 365) {
            $days = 7;
        }

        return view('top-contributors', [
            'channel' => $channel,
            'days' => $days,
        ]);
    }
}