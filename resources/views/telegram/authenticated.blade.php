@extends('layouts.app')

@section('title', 'Already Authenticated - Telegram')

@section('content')
    <div class="max-w-3xl mx-auto mt-8">
        <div class="bg-white rounded-xl shadow-lg p-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">✅ Already Authenticated</h1>
            
            <div class="bg-green-50 border border-green-200 rounded-lg p-6 mb-6">
                <h2 class="text-xl font-semibold text-green-800 mb-3">The API is already authenticated!</h2>
                <p class="text-green-700">
                    This server has an active Telegram session. The API can access public channels. No need to authenticate again.
                </p>
            </div>
            
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
                <h2 class="text-lg font-semibold text-blue-800 mb-3">📱 What can this session do?</h2>
                <ul class="list-disc list-inside space-y-2 text-blue-700 text-sm">
                    <li>Read public Telegram channels</li>
                    <li>Get channel statistics and message counts</li>
                    <li>Access public channel messages</li>
                </ul>
                <div class="mt-3 p-3 bg-red-100 rounded text-sm text-red-800">
                    <strong>⚠️ Important:</strong> This API is configured to ONLY access public channels. However, the underlying session technically has full access to the authenticated account. This is why we strongly recommend using a dedicated account for testing and closing the session when done.
                </div>
            </div>
            
            <div class="bg-orange-50 border border-orange-200 rounded-lg p-6 mb-6">
                <h2 class="text-lg font-semibold text-orange-800 mb-3">🔒 How to close this session</h2>
                <p class="text-orange-700 mb-3">
                    For security, we recommend closing this session when you're done testing:
                </p>
                <ol class="list-decimal list-inside space-y-2 text-orange-700 text-sm">
                    <li>Open Telegram on your phone</li>
                    <li>Go to <strong>Settings → Devices</strong> (or <strong>Settings → Privacy and Security → Active Sessions</strong>)</li>
                    <li>Find the session named <strong>"api telegram"</strong> or similar</li>
                    <li>Tap on it and select <strong>"Terminate Session"</strong></li>
                </ol>
            </div>
            
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-3">📊 Ready to use the API</h2>
                <p class="text-gray-700 mb-4">
                    The API is ready to fetch data from public Telegram channels. Try these endpoints:
                </p>
                <div class="space-y-2">
                    <code class="block bg-white px-3 py-2 rounded text-xs text-gray-800 font-mono border border-gray-300">
                        GET /api/v2/telegram/channels/{channel}/messages/last-id
                    </code>
                    <code class="block bg-white px-3 py-2 rounded text-xs text-gray-800 font-mono border border-gray-300">
                        GET /api/v2/telegram/channels/{channel}/statistics/{days}
                    </code>
                </div>
                <div class="mt-4 flex gap-4">
                    <a href="{{ route('home') }}" 
                       class="px-4 py-2 bg-blue-500 text-white rounded-lg font-medium hover:bg-blue-600 transition-colors">
                        🏠 Go to API Tester
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection