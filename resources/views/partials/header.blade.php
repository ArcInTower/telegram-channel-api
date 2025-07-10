<header style="background: white; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1); border-bottom: 1px solid #e5e7eb;">
    <div style="max-width: 1200px; margin: 0 auto; padding: 1rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <h1 style="font-size: 1.5rem; font-weight: bold; color: #1f2937; margin: 0;">🛸 Telegram Channel API</h1>
            <nav style="display: flex; gap: 1rem;">
                <a href="/" style="color: {{ request()->routeIs('home') ? '#1f2937' : '#3b82f6' }}; text-decoration: none; font-weight: {{ request()->routeIs('home') ? '600' : '500' }}; font-size: 0.875rem;">Home</a>
                <a href="{{ route('changelog') }}" style="color: {{ request()->routeIs('changelog') ? '#1f2937' : '#3b82f6' }}; text-decoration: none; font-weight: {{ request()->routeIs('changelog') ? '600' : '500' }}; font-size: 0.875rem;">Changelog</a>
                <a href="{{ route('architecture') }}" style="color: {{ request()->routeIs('architecture') ? '#1f2937' : '#3b82f6' }}; text-decoration: none; font-weight: {{ request()->routeIs('architecture') ? '600' : '500' }}; font-size: 0.875rem;">Architecture</a>
            </nav>
        </div>
    </div>
</header>

<style>
@media (max-width: 640px) {
    header h1 {
        font-size: 1.25rem !important;
    }
    header nav {
        gap: 0.75rem !important;
    }
    header nav a {
        font-size: 0.8125rem !important;
    }
}
</style>