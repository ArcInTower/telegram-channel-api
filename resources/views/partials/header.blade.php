<header class="bg-white shadow-sm border-b border-gray-200">
    <div class="max-w-6xl mx-auto px-4">
        <div class="flex justify-between items-center flex-wrap gap-4 py-4">
            <h1 class="text-2xl font-bold text-gray-800">🛸 Telegram Channel API</h1>
            <nav class="flex gap-4">
                <a href="/" class="@if(request()->routeIs('home')) text-gray-800 font-semibold @else text-blue-600 hover:text-blue-800 @endif text-sm">Home</a>
                <a href="{{ route('changelog') }}" class="@if(request()->routeIs('changelog')) text-gray-800 font-semibold @else text-blue-600 hover:text-blue-800 @endif text-sm">Changelog</a>
                <a href="{{ route('architecture') }}" class="@if(request()->routeIs('architecture')) text-gray-800 font-semibold @else text-blue-600 hover:text-blue-800 @endif text-sm">Architecture</a>
            </nav>
        </div>
    </div>
</header>