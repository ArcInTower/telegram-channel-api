<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class ReactionsViewController extends Controller
{
    /**
     * Display the reactions analysis page
     */
    public function index(Request $request, ?string $channel = null, ?string $period = null): View
    {
        // Default values
        $channel = $channel ?? 'nuevomeneame';
        $period = $period ?? '7days';
        
        // Validate period
        $validPeriods = ['1hour', '1day', '7days', '30days', '3months', '6months', '1year'];
        if (!in_array($period, $validPeriods)) {
            $period = '7days';
        }
        
        return view('reactions', [
            'channel' => $channel,
            'period' => $period
        ]);
    }
}