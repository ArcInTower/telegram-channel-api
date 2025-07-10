<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShowStatisticsRequest;
use Illuminate\View\View;

class StatisticsController extends Controller
{
    public function show(ShowStatisticsRequest $request): View
    {
        $channel = $request->getChannel();
        $days = $request->getDays();

        return view('statistics', compact('channel', 'days'));
    }
}
