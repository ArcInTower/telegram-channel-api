<header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-50">
    <div class="max-w-6xl mx-auto px-4">
        <div class="flex justify-between items-center py-4">
            <h1 class="text-xl sm:text-2xl font-bold text-gray-800">🛸 Telegram Analytics API</h1>
            
            <!-- Desktop Navigation -->
            <nav class="hidden sm:flex gap-4 items-center">
                <a href="/" class="@if(request()->routeIs('home')) text-gray-800 font-semibold @else text-blue-600 hover:text-blue-800 @endif text-sm">Home</a>
                <a href="{{ route('changelog') }}" class="@if(request()->routeIs('changelog')) text-gray-800 font-semibold @else text-blue-600 hover:text-blue-800 @endif text-sm">Changelog</a>
                <a href="{{ route('architecture') }}" class="@if(request()->routeIs('architecture')) text-gray-800 font-semibold @else text-blue-600 hover:text-blue-800 @endif text-sm">Architecture</a>
                <span class="text-gray-300">|</span>
                <a href="{{ route('telegram.login') }}" class="@if(request()->routeIs('telegram.*')) text-gray-800 font-semibold @else text-blue-600 hover:text-blue-800 @endif text-sm">🔐 Auth</a>
            </nav>
            
            <!-- Mobile Menu Button -->
            <button onclick="toggleMobileMenu()" class="sm:hidden p-2 rounded-lg hover:bg-gray-100 transition-colors">
                <svg id="menuIcon" class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
                <svg id="closeIcon" class="hidden w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <!-- Mobile Navigation -->
        <nav id="mobileMenu" class="hidden sm:hidden pb-4">
            <div class="flex flex-col gap-2">
                <a href="/" class="@if(request()->routeIs('home')) bg-blue-50 text-blue-700 font-semibold @else text-gray-700 hover:bg-gray-50 @endif px-3 py-2 rounded-lg text-base transition-colors">Home</a>
                <a href="{{ route('changelog') }}" class="@if(request()->routeIs('changelog')) bg-blue-50 text-blue-700 font-semibold @else text-gray-700 hover:bg-gray-50 @endif px-3 py-2 rounded-lg text-base transition-colors">Changelog</a>
                <a href="{{ route('architecture') }}" class="@if(request()->routeIs('architecture')) bg-blue-50 text-blue-700 font-semibold @else text-gray-700 hover:bg-gray-50 @endif px-3 py-2 rounded-lg text-base transition-colors">Architecture</a>
                <hr class="my-2 border-gray-200">
                <a href="{{ route('telegram.login') }}" class="@if(request()->routeIs('telegram.*')) bg-blue-50 text-blue-700 font-semibold @else text-gray-700 hover:bg-gray-50 @endif px-3 py-2 rounded-lg text-base transition-colors">🔐 Authentication</a>
            </div>
        </nav>
    </div>
</header>

<script>
    function toggleMobileMenu() {
        const menu = document.getElementById('mobileMenu');
        const menuIcon = document.getElementById('menuIcon');
        const closeIcon = document.getElementById('closeIcon');
        
        menu.classList.toggle('hidden');
        menuIcon.classList.toggle('hidden');
        closeIcon.classList.toggle('hidden');
    }
</script>