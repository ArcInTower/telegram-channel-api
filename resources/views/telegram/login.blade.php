@extends('layouts.app')

@section('title', 'Telegram Authentication - API Setup')

@section('content')
    <div class="max-w-3xl mx-auto mt-8">
        <div class="bg-white rounded-xl shadow-lg p-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">🔐 Telegram Authentication</h1>
            
            @if($errors->any())
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                    <h3 class="text-red-800 font-semibold mb-2">⚠️ Error</h3>
                    @foreach($errors->all() as $error)
                        <p class="text-red-700">{{ $error }}</p>
                    @endforeach
                </div>
            @endif
            
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
                <h2 class="text-xl font-semibold text-blue-800 mb-4">📋 What You Need</h2>
                <div class="text-blue-700">
                    <p class="font-semibold mb-3">Just your Telegram phone number!</p>
                    <ul class="list-disc list-inside space-y-2 text-sm">
                        <li>Enter any Telegram account phone number (with country code)</li>
                        <li>You'll receive a verification code via Telegram</li>
                        <li>This creates a session that can only read public channels</li>
                        <li>The session will be stored on this server</li>
                    </ul>
                </div>
            </div>
            
            <div class="bg-yellow-50 border border-yellow-300 rounded-lg p-6 mb-6">
                <h2 class="text-xl font-semibold text-yellow-800 mb-4">⚠️ Important Security Information</h2>
                <ul class="list-disc list-inside space-y-2 text-yellow-700">
                    <li><strong>You will receive a login notification</strong> in Telegram notifying you of this new session - this is normal security behavior from Telegram</li>
                    <li><strong>This will create a session</strong> that allows the API to access public Telegram channels</li>
                    <li><strong>The session is stored on the server</strong> - ensure your server is secure</li>
                    <li><strong>Anyone with server access</strong> can use this session to read public channels</li>
                    <li><strong>The session will have FULL access to the account</strong> (technically), but this API only uses it for:
                        <ul class="ml-6 mt-1 list-circle list-inside text-sm">
                            <li>Reading public channels</li>
                            <li>Getting channel statistics</li>
                        </ul>
                    </li>
                    <li class="text-orange-700"><strong>⚠️ Security recommendation:</strong> Use a dedicated Telegram account for API testing, not your personal account</li>
                    <li><strong>You can revoke this session</strong> anytime from Telegram Settings → Devices</li>
                    <li class="text-orange-700"><strong>⚠️ We strongly recommend</strong> closing this session in Telegram Settings → Devices after you finish testing</li>
                </ul>
            </div>
            
            <div class="bg-red-50 border border-red-300 rounded-lg p-6 mb-8">
                <h2 class="text-xl font-semibold text-red-800 mb-4">🚨 Testing Environment Notice</h2>
                <p class="text-red-700 mb-3">
                    <strong>This authentication is for API testing purposes only.</strong> For production use, we strongly recommend:
                </p>
                <ul class="list-disc list-inside space-y-2 text-red-700">
                    <li><strong>Clone the GitHub repository</strong> to download the source code and have 100% control over it</li>
                    <li><strong>Deploy the code on your own server</strong> to ensure your privacy is not compromised in any way</li>
                    <li><strong>Review the source code</strong> before authenticating to understand exactly what the API does</li>
                    <li><strong>Never use this public demo instance</strong> for sensitive or production workloads</li>
                </ul>
                <p class="text-sm text-red-600 mt-3">
                    GitHub Repository (source code): <a href="https://github.com/ArcInTower/telegram-channel-api" target="_blank" class="underline hover:text-red-800">github.com/ArcInTower/telegram-channel-api</a>
                </p>
                <p class="text-xs text-red-600 mt-2">
                    Use <code class="bg-red-100 px-1 py-0.5 rounded">git clone https://github.com/ArcInTower/telegram-channel-api.git</code> to download
                </p>
            </div>
            
            <form action="{{ route('telegram.auth.start') }}" method="POST" class="space-y-6">
                @csrf
                
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                        Telegram Phone Number
                    </label>
                    <input type="tel" 
                           id="phone" 
                           name="phone" 
                           value="{{ old('phone') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 text-lg"
                           placeholder="+1234567890"
                           required>
                    <p class="mt-1 text-sm text-gray-500">Include country code (e.g., +34 for Spain, +1 for USA)</p>
                </div>
                
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <label class="flex items-start">
                        <input type="checkbox" 
                               name="consent" 
                               class="mt-1 mr-3"
                               required>
                        <span class="text-sm text-gray-700">
                            I understand that:
                            <ul class="ml-5 mt-2 list-disc list-inside space-y-1">
                                <li>This is for API testing purposes only</li>
                                <li>I will receive a login notification in Telegram</li>
                                <li>A session will be created on this server that can access public Telegram channels</li>
                                <li>I should close this session in Telegram Settings → Devices after testing</li>
                                <li>For production use, I should download the code from GitHub and self-host it</li>
                            </ul>
                        </span>
                    </label>
                </div>
                
                <div class="flex justify-between items-center">
                    <a href="{{ route('home') }}" class="text-gray-600 hover:text-gray-800">
                        ← Back to Home
                    </a>
                    <button type="submit" 
                            class="px-6 py-3 bg-blue-500 text-white rounded-lg font-medium hover:bg-blue-600 transition-colors">
                        Continue to Verification →
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection