<footer style="margin-top: 3rem; background: #1f2937; color: #d1d5db;">
    <div style="max-width: 1200px; margin: 0 auto; padding: 2rem 1rem;">
        <!-- Main Footer Content -->
        <div style="max-width: 56rem; margin: 0 auto; text-align: center; margin-bottom: 2rem;">
            <div style="margin-bottom: 1.5rem;">
                <h3 style="font-size: 1.5rem; font-weight: bold; color: white; margin-bottom: 0.5rem;">🛸 Telegram Channel API</h3>
                <p style="color: #9ca3af; font-size: 0.875rem;">
                    An unofficial Laravel-based API for retrieving Telegram channel information
                </p>
            </div>
            
            <!-- Links in a clean row -->
            <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 2rem; font-size: 0.875rem;">
                <a href="/" style="color: #d1d5db; text-decoration: none; font-weight: 500;">Home</a>
                <a href="{{ route('changelog') }}" style="color: #d1d5db; text-decoration: none; font-weight: 500;">Changelog</a>
                <a href="{{ route('architecture') }}" style="color: #d1d5db; text-decoration: none; font-weight: 500;">Architecture</a>
                <span style="color: #4b5563;">•</span>
                <a href="https://github.com/ArcInTower/telegram-channel-api" target="_blank" style="color: #d1d5db; text-decoration: none; font-weight: 500; display: flex; align-items: center; gap: 0.25rem;">
                    <svg style="width: 1rem; height: 1rem;" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/></svg>
                    GitHub
                </a>
                <a href="https://my.telegram.org" target="_blank" style="color: #d1d5db; text-decoration: none; font-weight: 500;">API Credentials</a>
            </div>
        </div>
        
        <!-- Bottom Section -->
        <div style="text-align: center; font-size: 0.875rem; color: #6b7280;">
            <p>Built with <span style="color: #ef4444;">❤️</span> using Laravel {{ substr(app()->version(), 0, 2) }} • © {{ date('Y') }} (Unofficial)</p>
            <p style="margin-top: 0.5rem;">
                <a href="https://github.com/ArcInTower/telegram-channel-api/issues" target="_blank" style="color: #9ca3af; text-decoration: none;">
                    🐛 Report an Issue
                </a>
            </p>
        </div>
    </div>
</footer>

<style>
footer a:hover {
    color: #60a5fa !important;
}
</style>