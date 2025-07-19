@extends('layouts.app')

@section('content')
<style>
    /* Fix horizontal scroll on mobile for code blocks */
    body {
        overflow-x: hidden;
    }
    
    .overflow-x-auto {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .overflow-x-auto pre {
        margin: 0;
        width: max-content;
        min-width: 100%;
    }
    
    @media (max-width: 800px) {
        .bg-gray-50 .overflow-x-auto {
            max-width: calc(100vw - 4rem);
            margin-right: -0.75rem;
        }
    }
    
    @media (max-width: 733px) {
        body {
            padding-right: 0.5rem;
        }
        
        .max-w-7xl {
            padding-right: 1.5rem !important;
        }
    }
    
    @media (max-width: 640px) {
        .bg-gray-50 .overflow-x-auto {
            max-width: calc(100vw - 5rem);
        }
    }
    
    /* Deprecated API code block */
    .bg-yellow-100 .overflow-x-auto {
        max-width: 100%;
    }
    
    @media (max-width: 800px) {
        .bg-yellow-100 .overflow-x-auto {
            max-width: calc(100vw - 6rem);
            margin-right: -1.5rem;
        }
    }
    
    /* Modal Styles */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(0, 0, 0, 0.5);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        padding: 1rem;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .modal-overlay.show {
        opacity: 1;
    }
    
    .modal-content {
        background: white;
        border-radius: 0.75rem;
        padding: 1.5rem;
        max-width: 28rem;
        width: 100%;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        transform: scale(0.95);
        transition: transform 0.3s ease;
    }
    
    .modal-overlay.show .modal-content {
        transform: scale(1);
    }
    
    .modal-icon {
        width: 3rem;
        height: 3rem;
        margin: 0 auto 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 9999px;
    }
    
    .modal-icon.error {
        background-color: #fee2e2;
        color: #dc2626;
    }
    
    .modal-icon.warning {
        background-color: #fef3c7;
        color: #f59e0b;
    }
    
    .modal-icon.success {
        background-color: #d1fae5;
        color: #10b981;
    }
    
    .modal-icon.info {
        background-color: #dbeafe;
        color: #3b82f6;
    }
    
    .modal-title {
        font-size: 1.125rem;
        font-weight: 600;
        color: #111827;
        text-align: center;
        margin-bottom: 0.5rem;
    }
    
    .modal-message {
        font-size: 0.875rem;
        color: #6b7280;
        text-align: center;
        margin-bottom: 1.5rem;
        line-height: 1.5;
    }
    
    .modal-button {
        width: 100%;
        padding: 0.625rem 1.25rem;
        background-color: #3b82f6;
        color: white;
        font-size: 0.875rem;
        font-weight: 500;
        border-radius: 0.375rem;
        border: none;
        cursor: pointer;
        transition: background-color 0.2s;
    }
    
    .modal-button:hover {
        background-color: #2563eb;
    }
    
    .modal-button:focus {
        outline: none;
        ring: 2px;
        ring-color: #3b82f6;
        ring-opacity: 0.5;
    }
    
    /* API Card Styles */
    .api-card {
        transition: all 0.3s ease;
        border: 1px solid #e5e7eb;
    }
    
    .api-card:hover {
        border-color: #3b82f6;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
    
    .api-card.expanded {
        border-color: #3b82f6;
        background: #f8faff;
    }
    
    .api-header {
        cursor: pointer;
        user-select: none;
        transition: all 0.2s ease;
    }
    
    .api-header:hover {
        background: #f3f4f6;
    }
    
    .chevron {
        transition: transform 0.3s ease;
        color: #6b7280;
        flex-shrink: 0;
    }
    
    .chevron.rotate {
        transform: rotate(180deg);
    }
    
    .api-content {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease-out;
        opacity: 0;
    }
    
    .api-content.show {
        max-height: 3000px;
        transition: max-height 0.5s ease-in;
        opacity: 1;
    }
    
    .endpoint-badge {
        background: #dbeafe;
        color: #1e40af;
        padding: 0.25rem 0.5rem;
        border-radius: 9999px;
        font-size: 0.625rem;
        font-weight: 600;
        display: inline-block;
        white-space: nowrap;
    }
    
    @media (min-width: 640px) {
        .endpoint-badge {
            padding: 0.25rem 0.75rem;
            font-size: 0.75rem;
        }
    }
    
    .method-badge {
        background: #10b981;
        color: white;
        padding: 0.125rem 0.5rem;
        border-radius: 0.25rem;
        font-size: 0.75rem;
        font-weight: 700;
        margin-right: 0.5rem;
    }
    
    
    .result-box {
        background: #1f2937;
        color: #e5e7eb;
        border-radius: 0.5rem;
        padding: 0.75rem;
        font-family: monospace;
        font-size: 0.75rem;
        overflow-x: auto;
        white-space: pre-wrap;
        word-break: break-word;
        max-height: 300px;
        overflow-y: auto;
    }
    
    @media (min-width: 640px) {
        .result-box {
            padding: 1rem;
            font-size: 0.875rem;
            max-height: 400px;
        }
    }
    
    .loading-spinner {
        display: inline-block;
        width: 16px;
        height: 16px;
        border: 2px solid #f3f4f6;
        border-top-color: #3b82f6;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }
    
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    
    /* Mobile-specific adjustments */
    @media (max-width: 639px) {
        .api-header {
            padding: 0.75rem;
        }
        
        .api-content {
            padding: 0 0.75rem 0.75rem;
        }
        
        .endpoint-code {
            font-size: 0.625rem;
            padding: 0.5rem;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
    }
    
    /* Hide content completely when collapsed */
    .api-card:not(.expanded) .api-content {
        display: none !important;
    }
</style>

<!-- Modal Container -->
<div id="modalOverlay" class="modal-overlay" onclick="closeModal(event)">
    <div class="modal-content" onclick="event.stopPropagation()">
        <div id="modalIcon" class="modal-icon">
            <!-- Icon will be inserted here -->
        </div>
        <h3 id="modalTitle" class="modal-title"></h3>
        <p id="modalMessage" class="modal-message"></p>
        <button class="modal-button" onclick="closeModal()">OK</button>
    </div>
</div>

<div class="max-w-7xl mx-auto pl-1 pr-6 sm:px-4 py-4 sm:py-8 sm:pr-6 overflow-x-hidden">
    <!-- Header -->
    <div class="text-center mb-8 sm:mb-12">
        <h1 class="text-2xl sm:text-4xl font-bold text-gray-900 mb-3 sm:mb-4">Telegram Analytics API</h1>
        <p class="text-base sm:text-xl text-gray-600 mb-2">Real-time data from public Telegram channels</p>
        <p class="text-xs sm:text-sm text-gray-500">Click on any endpoint below to try it out</p>
    </div>
    
    <!-- Quick Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-2 sm:gap-4 mb-8">
        <div class="bg-white rounded-lg p-3 sm:p-4 text-center border border-gray-200">
            <div class="text-xl sm:text-2xl font-bold text-blue-600">5</div>
            <div class="text-xs sm:text-sm text-gray-600">API Endpoints</div>
        </div>
        <div class="bg-white rounded-lg p-3 sm:p-4 text-center border border-gray-200">
            <div class="text-xl sm:text-2xl font-bold text-green-600">v2</div>
            <div class="text-xs sm:text-sm text-gray-600">API Version</div>
        </div>
        <div class="bg-white rounded-lg p-3 sm:p-4 text-center border border-gray-200">
            <div class="text-xl sm:text-2xl font-bold text-purple-600">JSON:API</div>
            <div class="text-xs sm:text-sm text-gray-600">Format</div>
        </div>
        <div class="bg-white rounded-lg p-3 sm:p-4 text-center border border-gray-200">
            <div class="text-xl sm:text-2xl font-bold text-orange-600">Cached</div>
            <div class="text-xs sm:text-sm text-gray-600">Responses</div>
        </div>
    </div>
    
    <!-- API Endpoints -->
    <div class="space-y-4">
        <!-- Last Message ID -->
        <div class="api-card bg-white rounded-lg overflow-hidden" data-endpoint="lastMessage">
            <div class="api-header px-3 sm:px-6 py-3 sm:py-4 flex items-center justify-between" onclick="toggleCard('lastMessage')">
                <div class="flex items-center gap-3 sm:gap-4 flex-1 min-w-0">
                    <span class="text-xl sm:text-2xl flex-shrink-0">🚀</span>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-base sm:text-lg font-semibold text-gray-900 truncate">Last Message ID</h3>
                        <p class="text-xs sm:text-sm text-gray-600 hidden sm:block truncate">Get the latest message ID from a channel</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 sm:gap-3">
                    <span class="endpoint-badge hidden sm:inline-block">Fast • 5min cache</span>
                    <svg class="chevron w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </div>
            </div>
            <div class="api-content px-3 sm:px-6 pb-3 sm:pb-6">
                <div class="bg-gray-800 text-gray-200 p-2 sm:p-3 rounded-lg font-mono text-xs sm:text-sm mb-3 sm:mb-4 overflow-x-auto">
                    <div class="whitespace-nowrap">
                        <span class="method-badge">GET</span>
                        <span class="sm:hidden">/api/v2/telegram/channels/<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{channel}/messages/last-id</span>
                        <span class="hidden sm:inline">/api/v2/telegram/channels/{channel}/messages/last-id</span>
                    </div>
                </div>
                
                <div class="space-y-3 mt-4 mb-4">
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">Channel</label>
                        <input type="text" id="channelInput" placeholder="e.g., laravel" 
                                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div class="grid grid-cols-1 gap-2">
                            <button onclick="getLastMessage()" class="flex-1 px-3 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700 transition-colors">
                                Try it
                            </button>
                        </div>
                </div>
                <div id="messageResult" class="mt-4 hidden"></div>
            </div>
        </div>
        
        <!-- Channel Statistics -->
        <div class="api-card bg-white rounded-lg overflow-hidden" data-endpoint="statistics">
            <div class="api-header px-3 sm:px-6 py-3 sm:py-4 flex items-center justify-between" onclick="toggleCard('statistics')">
                <div class="flex items-center gap-3 sm:gap-4 flex-1 min-w-0">
                    <span class="text-xl sm:text-2xl flex-shrink-0">📊</span>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-base sm:text-lg font-semibold text-gray-900 truncate">Channel Statistics</h3>
                        <p class="text-xs sm:text-sm text-gray-600 hidden sm:block truncate">Detailed activity analysis for the last N days</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 sm:gap-3">
                    <span class="endpoint-badge hidden sm:inline-block">Intensive • 1h cache</span>
                    <svg class="chevron w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </div>
            </div>
            <div class="api-content px-3 sm:px-6 pb-3 sm:pb-6">
                <div class="bg-gray-800 text-gray-200 p-2 sm:p-3 rounded-lg font-mono text-xs sm:text-sm mb-3 sm:mb-4 overflow-x-auto">
                    <div class="whitespace-nowrap">
                        <span class="method-badge">GET</span>
                        <span class="sm:hidden">/api/v2/telegram/channels/<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{channel}/statistics/{days}</span>
                        <span class="hidden sm:inline">/api/v2/telegram/channels/{channel}/statistics/{days}</span>
                    </div>
                </div>
                
                <div class="space-y-3 mt-4 mb-4">
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">Channel</label>
                        <input type="text" id="statsChannelInput" placeholder="e.g., laravel" 
                                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">Days</label>
                            <input type="number" id="statsDaysInput" value="7" min="1" max="15" 
                                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <button onclick="getChannelStats()" class="w-full px-3 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700 transition-colors">
                                Try it
                            </button>
                            <button onclick="viewVisualStats()" class="w-full px-3 py-2 bg-blue-50 text-blue-600 text-sm rounded-md hover:bg-blue-100 border border-blue-200 transition-colors font-medium">
                                View visual statistics →
                            </button>
                        </div>
                </div>
                <div id="statsResult" class="mt-4 hidden"></div>
            </div>
        </div>
        
        <!-- Channel Polls -->
        <div class="api-card bg-white rounded-lg overflow-hidden" data-endpoint="polls">
            <div class="api-header px-3 sm:px-6 py-3 sm:py-4 flex items-center justify-between" onclick="toggleCard('polls')">
                <div class="flex items-center gap-3 sm:gap-4 flex-1 min-w-0">
                    <span class="text-xl sm:text-2xl flex-shrink-0">🗳️</span>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-base sm:text-lg font-semibold text-gray-900 truncate">Channel Polls</h3>
                        <p class="text-xs sm:text-sm text-gray-600 hidden sm:block truncate">Get polls with results and analytics</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 sm:gap-3">
                    <span class="endpoint-badge hidden sm:inline-block">Medium • 30min cache</span>
                    <svg class="chevron w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </div>
            </div>
            <div class="api-content px-3 sm:px-6 pb-3 sm:pb-6">
                <div class="bg-gray-800 text-gray-200 p-2 sm:p-3 rounded-lg font-mono text-xs sm:text-sm mb-3 sm:mb-4 overflow-x-auto">
                    <div class="whitespace-nowrap">
                        <span class="method-badge">GET</span>
                        <span class="sm:hidden">/api/v2/telegram/channels/<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{channel}/polls</span>
                        <span class="hidden sm:inline">/api/v2/telegram/channels/{channel}/polls</span>
                    </div>
                </div>
                
                <div class="space-y-3 mt-4 mb-4">
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">Channel</label>
                        <input type="text" id="pollsChannelInput" placeholder="e.g., laravel" 
                                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">Period</label>
                            <select id="pollsPeriodInput" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="1day">Last 24h</option>
                                <option value="7days" selected>Last 7 days</option>
                                <option value="30days">Last 30 days</option>
                                <option value="all">All time</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <button onclick="getChannelPolls()" class="w-full px-3 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700 transition-colors">
                                Try it
                            </button>
                            <button onclick="viewVisualPolls()" class="w-full px-3 py-2 bg-blue-50 text-blue-600 text-sm rounded-md hover:bg-blue-100 border border-blue-200 transition-colors font-medium">
                                View visual polls →
                            </button>
                        </div>
                </div>
                <div id="pollsResult" class="mt-4 hidden"></div>
            </div>
        </div>
        
        <!-- Channel Reactions -->
        <div class="api-card bg-white rounded-lg overflow-hidden" data-endpoint="reactions">
            <div class="api-header px-3 sm:px-6 py-3 sm:py-4 flex items-center justify-between" onclick="toggleCard('reactions')">
                <div class="flex items-center gap-3 sm:gap-4 flex-1 min-w-0">
                    <span class="text-xl sm:text-2xl flex-shrink-0">💜</span>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-base sm:text-lg font-semibold text-gray-900 truncate">Channel Reactions</h3>
                        <p class="text-xs sm:text-sm text-gray-600 hidden sm:block truncate">Analyze engagement through message reactions</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 sm:gap-3">
                    <span class="endpoint-badge hidden sm:inline-block">Medium • Dynamic cache</span>
                    <svg class="chevron w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </div>
            </div>
            <div class="api-content px-3 sm:px-6 pb-3 sm:pb-6">
                <div class="bg-gray-800 text-gray-200 p-2 sm:p-3 rounded-lg font-mono text-xs sm:text-sm mb-3 sm:mb-4 overflow-x-auto">
                    <div class="whitespace-nowrap">
                        <span class="method-badge">GET</span>
                        <span class="sm:hidden">/api/v2/telegram/channels/<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{channel}/reactions</span>
                        <span class="hidden sm:inline">/api/v2/telegram/channels/{channel}/reactions</span>
                    </div>
                </div>
                
                <div class="space-y-3 mt-4 mb-4">
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">Channel</label>
                        <input type="text" id="reactionsChannelInput" placeholder="e.g., laravel" 
                                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">Period</label>
                            <select id="reactionsPeriodInput" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="1hour">Last hour</option>
                                <option value="1day">Last 24h</option>
                                <option value="7days" selected>Last 7 days</option>
                                <option value="30days">Last 30 days</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <button onclick="getChannelReactions()" class="w-full px-3 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700 transition-colors">
                                Try it
                            </button>
                            <button onclick="viewVisualReactions()" class="w-full px-3 py-2 bg-blue-50 text-blue-600 text-sm rounded-md hover:bg-blue-100 border border-blue-200 transition-colors font-medium">
                                View visual reactions →
                            </button>
                        </div>
                </div>
                <div id="reactionsResult" class="mt-4 hidden"></div>
            </div>
        </div>
        
        <!-- Channel Comparison -->
        <div class="api-card bg-white rounded-lg overflow-hidden" data-endpoint="compare">
            <div class="api-header px-3 sm:px-6 py-3 sm:py-4 flex items-center justify-between" onclick="toggleCard('compare')">
                <div class="flex items-center gap-3 sm:gap-4 flex-1 min-w-0">
                    <span class="text-xl sm:text-2xl flex-shrink-0">🔀</span>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-base sm:text-lg font-semibold text-gray-900 truncate">Compare Channels</h3>
                        <p class="text-xs sm:text-sm text-gray-600 hidden sm:block truncate">Compare statistics between multiple channels</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 sm:gap-3">
                    <span class="endpoint-badge hidden sm:inline-block">Intensive • 1h cache</span>
                    <svg class="chevron w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </div>
            </div>
            <div class="api-content px-3 sm:px-6 pb-3 sm:pb-6">
                <div class="bg-gray-800 text-gray-200 p-2 sm:p-3 rounded-lg font-mono text-xs sm:text-sm mb-3 sm:mb-4 overflow-x-auto">
                    <div class="whitespace-nowrap">
                        <span class="method-badge">POST</span>
                        <span class="sm:hidden">/api/v2/telegram/channels/<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;compare</span>
                        <span class="hidden sm:inline">/api/v2/telegram/channels/compare</span>
                    </div>
                </div>
                
                <div class="space-y-3 mt-4 mb-4">
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">Channels</label>
                        <input type="text" id="compareChannelsInput" placeholder="channel1, channel2, channel3" 
                                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">Days</label>
                            <input type="number" id="compareDaysInput" value="7" min="1" max="15" 
                                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <button onclick="compareChannels()" class="w-full px-3 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700 transition-colors">
                                Try it
                            </button>
                            <button onclick="viewVisualComparison()" class="w-full px-3 py-2 bg-blue-50 text-blue-600 text-sm rounded-md hover:bg-blue-100 border border-blue-200 transition-colors font-medium">
                                View visual comparison →
                            </button>
                        </div>
                </div>
                <div id="compareResult" class="mt-4 hidden"></div>
            </div>
        </div>
    </div>
    
    <!-- Legacy v1 API -->
    <div class="bg-yellow-100 border border-yellow-400 rounded-xl p-4 sm:p-6 mt-12 mb-8 max-w-3xl mx-auto">
        <h3 class="text-yellow-900 text-lg font-semibold mb-4">⚠️ Legacy v1 API (Deprecated)</h3>
        <p class="text-yellow-800 text-sm mb-4">
            The v1 API is still available but deprecated. Please migrate to v2 for better features and JSON:API compliance.
        </p>
        <div class="bg-white border border-yellow-300 rounded-lg p-3 sm:p-4 mt-4">
            <p class="text-xs text-yellow-800 mb-2"><strong>v1 Endpoint (deprecated):</strong></p>
            <div class="bg-yellow-50 rounded mb-3 p-2 overflow-hidden">
                <div class="overflow-x-auto">
                    <code class="block text-xs text-yellow-900 font-mono whitespace-pre">GET https://api-telegram.repostea.com/api/telegram/last-message?channel={channel}</code>
                </div>
            </div>
            <p class="text-xs text-yellow-800 m-0">
                Returns: <code class="bg-yellow-100 px-1 py-0.5 rounded text-xs">{"success": true, "last_message_id": 12345}</code>
            </p>
        </div>
    </div>
    
    <!-- Quick Start -->
    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mt-8 max-w-3xl mx-auto">
        <div class="flex items-center gap-2 mb-2">
            <span class="text-base">🚀</span>
            <h3 class="text-gray-800 text-sm font-semibold">Quick Start</h3>
        </div>
        <p class="text-gray-600 text-xs mb-2">cURL examples:</p>
        <div class="bg-gray-900 text-gray-200 p-3 rounded text-xs font-mono overflow-x-auto">
            <pre class="whitespace-pre-wrap"><span class="text-gray-500"># Get Last Message ID</span>
<span class="text-cyan-400">curl</span> <span class="text-green-400">"https://api-telegram.repostea.com/api/v2/telegram/channels/laravel/messages/last-id"</span>

<span class="text-gray-500"># Get Channel Info</span>
<span class="text-cyan-400">curl</span> <span class="text-green-400">"https://api-telegram.repostea.com/api/v2/telegram/channels/laravel"</span>

<span class="text-gray-500"># Get Statistics (7 days)</span>
<span class="text-cyan-400">curl</span> <span class="text-green-400">"https://api-telegram.repostea.com/api/v2/telegram/channels/laravel/statistics/7"</span>

<span class="text-gray-500"># Compare Channels</span>
<span class="text-cyan-400">curl</span> <span class="text-yellow-400">-X</span> <span class="text-purple-400">POST</span> <span class="text-green-400">"https://api-telegram.repostea.com/api/v2/telegram/channels/compare"</span> \
  <span class="text-yellow-400">-H</span> <span class="text-green-400">"Content-Type: application/json"</span> \
  <span class="text-yellow-400">-d</span> <span class="text-green-400">'{"channels":["laravel","php"],"days":7}'</span>

<span class="text-gray-500"># Get Polls</span>
<span class="text-cyan-400">curl</span> <span class="text-green-400">"https://api-telegram.repostea.com/api/v2/telegram/channels/laravel/polls?period=7days"</span>

<span class="text-gray-500"># Get Reactions</span>
<span class="text-cyan-400">curl</span> <span class="text-green-400">"https://api-telegram.repostea.com/api/v2/telegram/channels/laravel/reactions?period=7days"</span></pre>
        </div>
    </div>
    
    <!-- Footer Info -->
    <div class="mt-12 text-center text-sm text-gray-600">
        <p>All endpoints return data in JSON:API v1.1 format</p>
        <p class="mt-2">
            <a href="{{ route('changelog') }}" class="text-blue-600 hover:text-blue-800">View Changelog</a>
            <span class="mx-2">•</span>
            <a href="{{ route('architecture') }}" class="text-blue-600 hover:text-blue-800">Architecture</a>
        </p>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const API_BASE = '/api';
    
    // Modal Functions
    function showModal(title, message, type = 'info') {
        const overlay = document.getElementById('modalOverlay');
        const iconEl = document.getElementById('modalIcon');
        const titleEl = document.getElementById('modalTitle');
        const messageEl = document.getElementById('modalMessage');
        
        // Set icon based on type
        iconEl.className = `modal-icon ${type}`;
        switch(type) {
            case 'error':
                iconEl.innerHTML = '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>';
                break;
            case 'warning':
                iconEl.innerHTML = '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>';
                break;
            case 'success':
                iconEl.innerHTML = '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
                break;
            case 'info':
            default:
                iconEl.innerHTML = '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
                break;
        }
        
        titleEl.textContent = title;
        messageEl.textContent = message;
        
        // Show modal
        overlay.style.display = 'flex';
        setTimeout(() => overlay.classList.add('show'), 10);
    }
    
    function closeModal(event) {
        if (event && event.target !== event.currentTarget) return;
        
        const overlay = document.getElementById('modalOverlay');
        overlay.classList.remove('show');
        setTimeout(() => overlay.style.display = 'none', 300);
    }
    
    // Close modal on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeModal();
    });
    
    function toggleCard(endpoint) {
        const card = document.querySelector(`[data-endpoint="${endpoint}"]`);
        const content = card.querySelector('.api-content');
        const chevron = card.querySelector('.chevron');
        
        if (content.classList.contains('show')) {
            content.classList.remove('show');
            chevron.classList.remove('rotate');
            card.classList.remove('expanded');
        } else {
            content.classList.add('show');
            chevron.classList.add('rotate');
            card.classList.add('expanded');
        }
    }
    
    function showResult(elementId, data, isError = false) {
        const element = document.getElementById(elementId);
        if (!element) return;
        
        element.classList.remove('hidden');
        
        const formattedData = JSON.stringify(data, null, 2);
        
        if (isError) {
            element.innerHTML = `
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                    <p class="text-red-700 font-medium">Error occurred:</p>
                </div>
                <div class="result-box">${formattedData}</div>
            `;
        } else {
            element.innerHTML = `
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                    <p class="text-green-700 font-medium">Success! Response:</p>
                </div>
                <div class="result-box">${formattedData}</div>
            `;
        }
    }
    
    function showLoading(elementId) {
        const element = document.getElementById(elementId);
        if (!element) return;
        
        element.classList.remove('hidden');
        element.innerHTML = `
            <div class="flex items-center justify-center py-8">
                <div class="loading-spinner"></div>
                <span class="ml-2 text-gray-600">Loading...</span>
            </div>
        `;
    }
    
    async function getLastMessage() {
        const channel = document.getElementById('channelInput').value.trim();
        if (!channel) {
            showModal('Input Required', 'Please enter a channel username', 'warning');
            return;
        }
        
        showLoading('messageResult');
        
        try {
            const response = await fetch(`${API_BASE}/v2/telegram/channels/${encodeURIComponent(channel)}/messages/last-id`);
            const data = await response.json();
            showResult('messageResult', data, !response.ok);
        } catch (error) {
            showResult('messageResult', { error: error.message }, true);
        }
    }
    
    async function getChannelStats() {
        const channel = document.getElementById('statsChannelInput').value.trim();
        const days = document.getElementById('statsDaysInput').value;
        
        if (!channel) {
            showModal('Input Required', 'Please enter a channel username', 'warning');
            return;
        }
        
        showLoading('statsResult');
        
        try {
            const response = await fetch(`${API_BASE}/v2/telegram/channels/${encodeURIComponent(channel)}/statistics/${days}`);
            const data = await response.json();
            showResult('statsResult', data, !response.ok);
        } catch (error) {
            showResult('statsResult', { error: error.message }, true);
        }
    }
    
    function viewVisualStats() {
        const channel = document.getElementById('statsChannelInput').value.trim();
        const days = document.getElementById('statsDaysInput').value || '7';
        
        if (!channel) {
            showModal('Input Required', 'Please enter a channel username', 'warning');
            return;
        }
        
        window.location.href = `/statistics/${encodeURIComponent(channel)}/${days}`;
    }
    
    async function compareChannels() {
        const channelsInput = document.getElementById('compareChannelsInput').value.trim();
        const days = document.getElementById('compareDaysInput').value;
        
        if (!channelsInput) {
            showModal('Input Required', 'Please enter at least 2 channel usernames separated by commas', 'warning');
            return;
        }
        
        const channels = channelsInput.split(',').map(c => c.trim()).filter(c => c.length > 0);
        
        if (channels.length < 2) {
            showModal('Invalid Input', 'Please enter at least 2 channels to compare', 'warning');
            return;
        }
        
        if (channels.length > 4) {
            showModal('Too Many Channels', 'Maximum 4 channels allowed for comparison', 'warning');
            return;
        }
        
        showLoading('compareResult');
        
        try {
            const response = await fetch(`${API_BASE}/v2/telegram/channels/compare`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ channels, days: parseInt(days) })
            });
            const data = await response.json();
            showResult('compareResult', data, !response.ok);
        } catch (error) {
            showResult('compareResult', { error: error.message }, true);
        }
    }
    
    function viewVisualComparison() {
        const channelsInput = document.getElementById('compareChannelsInput').value.trim();
        const days = document.getElementById('compareDaysInput').value;
        
        if (!channelsInput) {
            showModal('Input Required', 'Please enter at least 2 channel usernames separated by commas', 'warning');
            return;
        }
        
        const channels = channelsInput.split(',').map(c => c.trim()).filter(c => c.length > 0);
        
        if (channels.length < 2) {
            showModal('Invalid Input', 'Please enter at least 2 channels to compare', 'warning');
            return;
        }
        
        if (channels.length > 4) {
            showModal('Too Many Channels', 'Maximum 4 channels allowed for comparison', 'warning');
            return;
        }
        
        const params = new URLSearchParams();
        channels.forEach(channel => params.append('channels[]', channel));
        params.append('days', days);
        
        window.location.href = `/compare?${params.toString()}`;
    }
    
    async function getChannelPolls() {
        const channel = document.getElementById('pollsChannelInput').value.trim();
        const period = document.getElementById('pollsPeriodInput').value;
        
        if (!channel) {
            showModal('Input Required', 'Please enter a channel username', 'warning');
            return;
        }
        
        showLoading('pollsResult');
        
        try {
            const response = await fetch(`${API_BASE}/v2/telegram/channels/${encodeURIComponent(channel)}/polls?period=${period}`);
            
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                showResult('pollsResult', {
                    jsonapi: { version: '1.1' },
                    errors: [{
                        status: '401',
                        title: 'Authentication Required',
                        detail: 'The bot has been disconnected from Telegram. Please contact the administrator to re-authenticate the bot.'
                    }],
                    meta: {
                        timestamp: new Date().toISOString(),
                        api_version: 'v2'
                    }
                }, true);
                return;
            }
            
            const data = await response.json();
            showResult('pollsResult', data, !response.ok);
        } catch (error) {
            if (error.message.includes('JSON')) {
                showResult('pollsResult', {
                    jsonapi: { version: '1.1' },
                    errors: [{
                        status: '401',
                        title: 'Authentication Required',
                        detail: 'The bot has been disconnected from Telegram. Please contact the administrator to re-authenticate the bot.'
                    }],
                    meta: {
                        timestamp: new Date().toISOString(),
                        api_version: 'v2'
                    }
                }, true);
            } else {
                showResult('pollsResult', { error: error.message }, true);
            }
        }
    }
    
    function viewVisualPolls() {
        const channel = document.getElementById('pollsChannelInput').value.trim();
        const period = document.getElementById('pollsPeriodInput').value || '7days';
        
        if (!channel) {
            showModal('Input Required', 'Please enter a channel username', 'warning');
            return;
        }
        
        window.location.href = `/polls/${encodeURIComponent(channel)}/${period}`;
    }
    
    async function getChannelReactions() {
        const channel = document.getElementById('reactionsChannelInput').value.trim();
        const period = document.getElementById('reactionsPeriodInput').value;
        
        if (!channel) {
            showModal('Input Required', 'Please enter a channel username', 'warning');
            return;
        }
        
        showLoading('reactionsResult');
        
        try {
            const response = await fetch(`${API_BASE}/v2/telegram/channels/${encodeURIComponent(channel)}/reactions?period=${period}`);
            const data = await response.json();
            showResult('reactionsResult', data, !response.ok);
        } catch (error) {
            showResult('reactionsResult', { error: error.message }, true);
        }
    }
    
    function viewVisualReactions() {
        const channel = document.getElementById('reactionsChannelInput').value.trim();
        const period = document.getElementById('reactionsPeriodInput').value || '7days';
        
        if (!channel) {
            showModal('Input Required', 'Please enter a channel username', 'warning');
            return;
        }
        
        window.location.href = `/reactions/${encodeURIComponent(channel)}/${period}`;
    }
    
    // Add Enter key support
    document.getElementById('channelInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') getLastMessage();
    });
    
    document.getElementById('statsChannelInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') getChannelStats();
    });
    
    document.getElementById('statsDaysInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') getChannelStats();
    });
    
    document.getElementById('compareChannelsInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') compareChannels();
    });
    
    document.getElementById('compareDaysInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') compareChannels();
    });
    
    document.getElementById('pollsChannelInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') getChannelPolls();
    });
    
    document.getElementById('reactionsChannelInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') getChannelReactions();
    });
</script>
@endpush