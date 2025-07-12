@extends('layouts.app')

@section('title', 'User Anonymization Request')

@section('content')
<div class="max-w-2xl mx-auto mt-8">
    <div class="bg-white rounded-lg shadow-lg p-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">User Anonymization Request</h1>
        
        <div class="mb-8 p-4 bg-blue-50 rounded-lg">
            <h2 class="text-lg font-semibold text-blue-900 mb-2">How to request user anonymization:</h2>
            <ol class="list-decimal list-inside space-y-2 text-blue-800">
                <li>Open a new issue on our GitHub repository</li>
                <li>Use the "User Anonymization Request" template</li>
                <li>Provide verification that you're the account owner</li>
                <li>We'll review and process your request within 48 hours</li>
            </ol>
        </div>

        <div class="space-y-6">
            <div>
                <h3 class="text-xl font-semibold text-gray-800 mb-3">Valid reasons for anonymization:</h3>
                <ul class="list-disc list-inside space-y-1 text-gray-700">
                    <li>Personal privacy concerns</li>
                    <li>Security or safety reasons</li>
                    <li>Professional identity protection</li>
                    <li>Harassment or threats</li>
                    <li>Identity change</li>
                    <li>Legal requirements (GDPR, etc.)</li>
                </ul>
            </div>

            <div>
                <h3 class="text-xl font-semibold text-gray-800 mb-3">Verification methods:</h3>
                <ul class="list-disc list-inside space-y-1 text-gray-700">
                    <li>Send a message from your Telegram account</li>
                    <li>Provide screenshot of your Telegram profile</li>
                    <li>Email verification (if available)</li>
                </ul>
            </div>

            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <h3 class="text-lg font-semibold text-yellow-800 mb-2">⚠️ Important Notice</h3>
                <p class="text-yellow-700">Anonymization is permanent and cannot be reversed. Your username will appear as first and last letter with asterisks (e.g., <code class="bg-yellow-100 px-1 rounded">j**n</code> for "john").</p>
            </div>

            <div class="mt-8 text-center">
                <a href="https://github.com/ArcInTower/telegram-channel-api/issues/new?template=user-anonymization-request.md" 
                   target="_blank"
                   class="inline-flex items-center gap-2 bg-gray-800 hover:bg-gray-900 text-white font-medium px-6 py-3 rounded-lg transition duration-200">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                    </svg>
                    Submit Request on GitHub
                </a>
            </div>

            <div class="text-center text-sm text-gray-600 mt-6">
                <p>Alternatively, you can email us at 
                   <a href="mailto:privacy@telegram-api.com?subject=User%20Anonymization%20Request" 
                      class="text-blue-600 hover:text-blue-800 underline">privacy@telegram-api.com</a>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection