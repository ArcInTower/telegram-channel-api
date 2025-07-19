<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PollsViewController extends Controller
{
    /**
     * Display the polls page for a channel
     */
    public function show(Request $request, string $channel, string $period = '7days')
    {
        // Validate period
        $validPeriods = ['1hour', '1day', '7days', '30days', '3months', '6months', '1year'];
        if (!in_array($period, $validPeriods)) {
            $period = '7days';
        }

        return view('polls', [
            'channel' => $channel,
            'period' => $period,
        ]);
    }
}