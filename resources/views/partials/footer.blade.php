<footer class="mt-12 bg-gray-800 text-gray-300">
    <div class="container mx-auto px-4 py-8">
        <!-- Main Footer Content -->
        <div class="max-w-4xl mx-auto text-center mb-8">
            <div class="mb-6">
                <h3 class="text-2xl font-bold text-white mb-2">🛸 Telegram Channel API</h3>
                <p class="text-gray-400">
                    An unofficial Laravel-based API for retrieving Telegram channel information
                </p>
            </div>
            
            <!-- Links in a clean row -->
            <div class="flex flex-wrap justify-center gap-8 text-sm">
                <a href="/" class="text-gray-300 hover:text-blue-400 transition-colors font-medium">Home</a>
                <a href="{{ route('changelog') }}" class="text-gray-300 hover:text-blue-400 transition-colors font-medium">Changelog</a>
                <a href="{{ route('architecture') }}" class="text-gray-300 hover:text-blue-400 transition-colors font-medium">Architecture</a>
                <span class="text-gray-600">•</span>
                <a href="https://github.com/ArcInTower/telegram-channel-api" target="_blank" class="text-gray-300 hover:text-blue-400 transition-colors font-medium flex items-center gap-1">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/></svg>
                    GitHub
                </a>
                <a href="https://my.telegram.org" target="_blank" class="text-gray-300 hover:text-blue-400 transition-colors font-medium">API Credentials</a>
            </div>
        </div>
        
        <!-- Bottom Section -->
        <div class="text-center text-sm text-gray-500">
            <p>Built with <span class="text-red-500">❤️</span> using Laravel {{ substr(app()->version(), 0, 2) }} • © {{ date('Y') }} (Unofficial)</p>
            <p class="mt-2">
                <a href="https://github.com/ArcInTower/telegram-channel-api/issues" target="_blank" class="text-gray-400 hover:text-blue-400 transition-colors">
                    🐛 Report an Issue
                </a>
            </p>
        </div>
    </div>
</footer>