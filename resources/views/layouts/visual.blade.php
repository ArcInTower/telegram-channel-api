@extends('layouts.app')

@push('styles')
<style>
    /* Common styles for all visual pages */
    .loading {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 400px;
    }
    
    .spinner {
        border: 4px solid #f3f4f6;
        border-top: 4px solid #3b82f6;
        border-radius: 50%;
        width: 48px;
        height: 48px;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .visual-hidden {
        display: none !important;
    }
    
    /* Common stat card styles */
    .stat-card {
        background: white;
        border-radius: 0.75rem;
        padding: 1.5rem;
        text-align: center;
        border: 1px solid #e5e7eb;
        transition: all 0.2s;
    }
    
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
    
    .stat-value {
        font-size: 2.5rem;
        font-weight: 700;
        color: #1f2937;
        margin: 0.5rem 0;
    }
    
    .stat-label {
        color: #6b7280;
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
</style>
@stack('page-styles')
@endpush

@section('content')
<div class="container mx-auto px-2 sm:px-4 py-4 sm:py-8 max-w-6xl mt-2">
    <!-- Loading State -->
    <div id="loading" class="loading">
        <div class="spinner"></div>
    </div>
    
    <!-- Error State -->
    <div id="error" class="visual-hidden">
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg relative" role="alert">
            <strong class="font-bold">Error!</strong>
            <span class="block sm:inline" id="errorMessage"></span>
        </div>
    </div>
    
    <!-- Main Content -->
    <div id="content" class="visual-hidden">
        @yield('visual-content')
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Common functions for all visual pages
    function showError(message) {
        document.getElementById('loading').classList.add('visual-hidden');
        document.getElementById('content').classList.add('visual-hidden');
        document.getElementById('error').classList.remove('visual-hidden');
        document.getElementById('errorMessage').textContent = message;
    }
    
    function showContent() {
        document.getElementById('loading').classList.add('visual-hidden');
        document.getElementById('error').classList.add('visual-hidden');
        document.getElementById('content').classList.remove('visual-hidden');
    }
    
    function showLoading() {
        document.getElementById('loading').classList.remove('visual-hidden');
        document.getElementById('error').classList.add('visual-hidden');
        document.getElementById('content').classList.add('visual-hidden');
    }
    
    // Format numbers with commas
    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
    
    // Format date relative to now
    function formatRelativeDate(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);
        
        if (diffMins < 60) {
            return `${diffMins} minute${diffMins !== 1 ? 's' : ''} ago`;
        } else if (diffMins < 1440) {
            const hours = Math.floor(diffMins / 60);
            return `${hours} hour${hours !== 1 ? 's' : ''} ago`;
        } else {
            const days = Math.floor(diffMins / 1440);
            if (days < 7) {
                return `${days} day${days !== 1 ? 's' : ''} ago`;
            } else {
                return date.toLocaleDateString();
            }
        }
    }
    
    // Escape HTML to prevent XSS
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
</script>
@stack('page-scripts')
@endpush