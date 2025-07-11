@extends('layouts.app')

@section('title', 'Channel Exclusion Request')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow-lg p-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Channel Exclusion Request</h1>
        
        <div class="mb-8 p-4 bg-blue-50 rounded-lg">
            <h2 class="text-lg font-semibold text-blue-900 mb-2">How to request channel exclusion:</h2>
            <ol class="list-decimal list-inside space-y-2 text-blue-800">
                <li>Open a new issue on our GitHub repository</li>
                <li>Use the "Channel Exclusion Request" template</li>
                <li>Provide verification that you're the channel admin</li>
                <li>We'll review and process your request within 48 hours</li>
            </ol>
        </div>

        <div class="space-y-6">
            <div>
                <h3 class="text-xl font-semibold text-gray-800 mb-3">Valid reasons for exclusion:</h3>
                <ul class="list-disc list-inside space-y-1 text-gray-700">
                    <li>Privacy concerns</li>
                    <li>Copyright or content protection</li>
                    <li>Channel contains sensitive information</li>
                    <li>Legal requirements</li>
                </ul>
            </div>

            <div>
                <h3 class="text-xl font-semibold text-gray-800 mb-3">Verification methods:</h3>
                <ul class="list-disc list-inside space-y-1 text-gray-700">
                    <li>Post a message in your channel with the issue number</li>
                    <li>Temporarily update your channel description</li>
                    <li>Contact from the official admin account</li>
                </ul>
            </div>

            <div class="mt-8 text-center">
                <a href="https://github.com/ArcInTower/telegram-channel-api/issues/new?template=channel-exclusion-request.md" 
                   target="_blank"
                   class="inline-flex items-center gap-2 bg-gray-800 hover:bg-gray-900 text-white font-medium px-6 py-3 rounded-lg transition duration-200">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                    </svg>
                    Submit Request on GitHub
                </a>
            </div>

            <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                <p class="text-sm text-gray-600 text-center">
                    <strong>Alternative:</strong> Send an email to 
                    <a href="mailto:arc-in-tower@proton.me" class="text-blue-600 hover:text-blue-800">arc-in-tower@proton.me</a>
                    with your channel details and admin verification.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection