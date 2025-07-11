@extends('layouts.app')

@section('title', 'Verify Phone - Telegram Authentication')

@section('content')
    <div class="max-w-2xl mx-auto mt-8">
        <div class="bg-white rounded-xl shadow-lg p-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">📱 Enter Verification Code</h1>
            
            @if(session('info'))
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <p class="text-blue-700">{{ session('info') }}</p>
                </div>
            @endif
            
            @if($errors->any())
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                    @foreach($errors->all() as $error)
                        <p class="text-red-700">{{ $error }}</p>
                    @endforeach
                </div>
            @endif
            
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-3">🔔 Check Your Telegram App</h2>
                <p class="text-gray-700 mb-3">
                    You should have received two notifications in Telegram:
                </p>
                <ol class="list-decimal list-inside space-y-2 text-gray-700 mb-3">
                    <li><strong>A verification code message</strong> with your 5-digit code</li>
                    <li><strong>A login notification</strong> about a new session (this is normal)</li>
                </ol>
                <p class="text-sm text-gray-600">
                    The code is usually 5 digits long and expires in a few minutes.
                </p>
            </div>
            
            <div class="bg-orange-50 border border-orange-200 rounded-lg p-4 mb-8">
                <p class="text-sm text-orange-700">
                    <strong>Remember:</strong> After testing, go to Telegram Settings → Devices and close this session for security.
                </p>
            </div>
            
            <form action="{{ route('telegram.auth.verify') }}" method="POST" class="space-y-6">
                @csrf
                
                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700 mb-2">
                        Verification Code
                    </label>
                    <input type="text" 
                           id="code" 
                           name="code" 
                           class="w-full px-4 py-3 text-2xl text-center font-mono border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                           placeholder="12345"
                           maxlength="5"
                           pattern="[0-9]{5}"
                           autofocus
                           required>
                    <p class="mt-2 text-sm text-gray-500 text-center">Enter the 5-digit code from Telegram</p>
                </div>
                
                <div class="flex justify-center">
                    <button type="submit" 
                            class="px-8 py-3 bg-blue-500 text-white rounded-lg font-medium hover:bg-blue-600 transition-colors">
                        Verify & Complete Setup
                    </button>
                </div>
            </form>
            
            <div class="mt-8 pt-6 border-t border-gray-200">
                <p class="text-sm text-gray-600 text-center">
                    Having trouble? Make sure you entered the correct phone number and that you have 
                    an active Telegram account.
                </p>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // Auto-focus and auto-submit when 5 digits are entered
    document.getElementById('code').addEventListener('input', function(e) {
        if (e.target.value.length === 5) {
            // Optional: auto-submit
            // e.target.form.submit();
        }
    });
</script>
@endpush