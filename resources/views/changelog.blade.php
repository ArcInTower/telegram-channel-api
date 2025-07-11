<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Changelog - Laravel Telegram API</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-50">
    @include('partials.header')

    <main class="container mx-auto px-4 py-8">
    <h1 class="text-4xl font-bold mb-8 text-gray-800">Changelog</h1>
    
    <div class="prose max-w-none">
        <p class="text-gray-600 mb-8">
            All notable changes to the Laravel Telegram Channel API will be documented here.
        </p>
        
        @foreach($changelog as $release)
            <div class="mb-12 bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center mb-4">
                    <h2 class="text-2xl font-bold mr-4">Version {{ $release['version'] }}</h2>
                    <span class="px-3 py-1 rounded-full text-sm font-semibold
                        @if($release['type'] === 'major') bg-red-100 text-red-800
                        @elseif($release['type'] === 'minor') bg-yellow-100 text-yellow-800
                        @else bg-green-100 text-green-800
                        @endif">
                        {{ ucfirst($release['type']) }} Release
                    </span>
                    <span class="text-gray-500 ml-auto">{{ \Carbon\Carbon::parse($release['date'])->format('F j, Y') }}</span>
                </div>
                
                @foreach($release['changes'] as $changeType => $changes)
                    <div class="mb-4">
                        <h3 class="text-lg font-semibold mb-2 
                            @if($changeType === 'Added') text-green-700
                            @elseif($changeType === 'Changed') text-blue-700
                            @elseif($changeType === 'Deprecated') text-orange-700
                            @elseif($changeType === 'Removed') text-red-700
                            @elseif($changeType === 'Fixed') text-purple-700
                            @else text-gray-700
                            @endif">
                            {{ $changeType }}
                        </h3>
                        <ul class="list-disc list-inside space-y-1">
                            @foreach($changes as $change)
                                <li class="text-gray-600">{{ $change }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
    
    <div class="mt-12 p-6 bg-blue-50 rounded-lg">
        <h3 class="text-lg font-semibold mb-2">API Deprecation Notice</h3>
        <p class="text-gray-700">
            The v1 API endpoint is deprecated. Please migrate to the v2 API:
        </p>
        <ul class="mt-2 space-y-1">
            <li class="text-gray-600">
                <code class="bg-gray-100 px-1">/api/telegram/last-message</code> → 
                <code class="bg-gray-100 px-1">/api/v2/telegram/channels/{channel}/messages/last-id</code>
            </li>
        </ul>
    </div>
    </main>

    @include('partials.footer')
</body>
</html>