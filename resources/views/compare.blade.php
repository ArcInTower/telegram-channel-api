@extends('layouts.app')

@section('title', 'Compare Channels')

@push('styles')
<style>
    .channel-input-group {
        position: relative;
    }
    
    .remove-channel {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: #ef4444;
        font-weight: bold;
        font-size: 20px;
        line-height: 1;
        padding: 5px;
    }
    
    .comparison-card {
        transition: all 0.3s ease;
    }
    
    .comparison-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }
    
    .stat-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 9999px;
        font-size: 0.875rem;
        font-weight: 500;
    }
    
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
</style>
@endpush

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 mt-8">
    <div class="mb-8">
        <div class="flex items-start justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Compare Telegram Channels</h1>
                <p class="text-gray-600">Compare statistics from up to 4 channels (max 7 days)</p>
            </div>
            <span class="bg-purple-100 text-purple-800 text-sm font-semibold px-4 py-2 rounded-full">
                🧪 Beta Feature
            </span>
        </div>
        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 mt-4">
            <p class="text-purple-700 text-sm font-medium mb-1">⚠️ Experimental Feature Notice</p>
            <p class="text-purple-600 text-xs">This feature is in beta and may change without prior notice. Breaking changes may occur without deprecation warnings.</p>
        </div>
    </div>
    
    <!-- Input Form -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <form id="compareForm">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Channels to Compare</label>
                <div id="channelInputs" class="space-y-3">
                    <div class="channel-input-group">
                        <input type="text" 
                               name="channels[]" 
                               placeholder="Channel username (e.g., nuevomeneame)" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               required>
                    </div>
                    <div class="channel-input-group">
                        <input type="text" 
                               name="channels[]" 
                               placeholder="Channel username" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               required>
                    </div>
                </div>
                
                <button type="button" 
                        id="addChannel" 
                        class="mt-3 text-blue-600 hover:text-blue-800 font-medium text-sm">
                    + Add another channel
                </button>
            </div>
            
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Time Period</label>
                <select name="days" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="1">Last 24 hours</option>
                    <option value="3">Last 3 days</option>
                    <option value="7" selected>Last 7 days (max)</option>
                </select>
            </div>
            
            <button type="submit" 
                    class="w-full bg-blue-600 text-white font-medium py-3 rounded-lg hover:bg-blue-700 transition duration-200">
                Compare Channels
            </button>
        </form>
    </div>
    
    <!-- Loading State -->
    <div id="loading" class="loading hidden">
        <div class="spinner"></div>
    </div>
    
    <!-- Error State -->
    <div id="error" class="hidden">
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg" role="alert">
            <strong class="font-bold">Error!</strong>
            <span class="block sm:inline" id="errorMessage"></span>
        </div>
    </div>
    
    <!-- Results -->
    <div id="results" class="hidden">
        <!-- Summary -->
        <div class="bg-gradient-to-r from-blue-500 to-cyan-600 rounded-lg shadow-lg p-6 mb-8 text-white">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-bold">Comparison Summary</h2>
                <button id="captureImageButton" class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg transition duration-200 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    Save as Image
                </button>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <p class="text-blue-100 text-sm">Channels Analyzed</p>
                    <p class="text-3xl font-bold" id="channelsAnalyzed">0</p>
                </div>
                <div>
                    <p class="text-blue-100 text-sm">Total Messages</p>
                    <p class="text-3xl font-bold" id="totalMessages">0</p>
                </div>
                <div>
                    <p class="text-blue-100 text-sm">Total Users</p>
                    <p class="text-3xl font-bold" id="totalUsers">0</p>
                </div>
                <div>
                    <p class="text-blue-100 text-sm">Most Active</p>
                    <p class="text-xl font-bold" id="mostActive">-</p>
                </div>
            </div>
        </div>
        
        <!-- Channel Comparisons -->
        <div id="channelComparisons" class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        </div>
        
        <!-- Chart Comparisons -->
        <div class="grid gap-6 mt-8">
            <!-- Key Metrics Chart -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-xl font-bold text-gray-800 mb-4">Key Metrics Comparison</h3>
                <canvas id="keyMetricsChart" height="80"></canvas>
            </div>
            
            <!-- Engagement Metrics Chart -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-xl font-bold text-gray-800 mb-4">Engagement Metrics</h3>
                <canvas id="engagementChart" height="80"></canvas>
            </div>
            
            <!-- Messages per User Chart -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-xl font-bold text-gray-800 mb-4">Average Messages per User</h3>
                <p class="text-sm text-gray-600 mb-3">How active are users in each channel?</p>
                <canvas id="messagesPerUserChart" height="80"></canvas>
            </div>
            
            <!-- Total Users Comparison -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-xl font-bold text-gray-800 mb-4">Active Users Comparison</h3>
                <p class="text-sm text-gray-600 mb-3">Number of unique users who posted messages in the selected period</p>
                <canvas id="totalUsersChart" height="80"></canvas>
            </div>
            
            <!-- Subscribers vs Active Users -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-xl font-bold text-gray-800 mb-4">Subscribers vs Active Users</h3>
                <p class="text-sm text-gray-600 mb-3">Total channel subscribers compared to active users</p>
                <canvas id="subscribersChart" height="80"></canvas>
            </div>
            
            <!-- User Engagement Ratio -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-xl font-bold text-gray-800 mb-4">User Engagement Ratio</h3>
                <p class="text-sm text-gray-600 mb-3">Percentage of subscribers who were active in the selected period</p>
                <canvas id="engagementRatioChart" height="80"></canvas>
            </div>
            
            <!-- Activity Patterns -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-xl font-bold text-gray-800 mb-4">Peak Activity Comparison</h3>
                <div id="activityPatterns" class="grid gap-4"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    @vite('resources/js/compare.js')
@endpush