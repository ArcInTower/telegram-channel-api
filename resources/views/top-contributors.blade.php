@extends('layouts.visual')

@section('title', 'User Value Rankings - ' . ($channel ?? 'Telegram'))

@push('page-styles')
<style>
    /* Page specific styles */
    
    .rank-badge {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 1rem;
        flex-shrink: 0;
    }
    
    .rank-badge.gold {
        background: linear-gradient(135deg, #fbbf24, #f59e0b);
        color: white;
        box-shadow: 0 2px 4px rgba(251, 191, 36, 0.3);
    }
    
    .rank-badge.silver {
        background: linear-gradient(135deg, #e5e7eb, #9ca3af);
        color: white;
        box-shadow: 0 2px 4px rgba(156, 163, 175, 0.3);
    }
    
    .rank-badge.bronze {
        background: linear-gradient(135deg, #f97316, #ea580c);
        color: white;
        box-shadow: 0 2px 4px rgba(249, 115, 22, 0.3);
    }
    
    .rank-badge.regular {
        background: #f3f4f6;
        color: #4b5563;
        font-size: 0.875rem;
    }
    
    .metric-bar {
        background: #f3f4f6;
        height: 0.5rem;
        border-radius: 9999px;
        overflow: hidden;
        position: relative;
    }
    
    .metric-fill {
        background: #3b82f6;
        height: 100%;
        border-radius: 9999px;
        transition: width 0.5s ease;
    }
    
    .category-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 500;
    }
    
    .category-badge.leader {
        background: #dbeafe;
        color: #1e40af;
    }
    
    .category-badge.contributor {
        background: #d1fae5;
        color: #065f46;
    }
    
    .category-badge.member {
        background: #e0e7ff;
        color: #3730a3;
    }
    
    .category-badge.participant {
        background: #fef3c7;
        color: #92400e;
    }
    
    .category-badge.observer {
        background: #f3f4f6;
        color: #6b7280;
    }
    
    .special-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.125rem 0.5rem;
        background: #fef3c7;
        color: #92400e;
        border-radius: 9999px;
        font-size: 0.7rem;
        font-weight: 500;
        margin: 0.125rem;
    }
    
    .metric-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 0.75rem;
        margin-top: 1rem;
    }
    
    .metric-item {
        background: #f9fafb;
        padding: 0.75rem;
        border-radius: 0.5rem;
    }
    
    .metric-label {
        font-size: 0.75rem;
        color: #6b7280;
        margin-bottom: 0.25rem;
    }
    
    .metric-value {
        font-size: 0.875rem;
        font-weight: 600;
        color: #111827;
    }
    
    .summary-card {
        background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
        border-radius: 0.75rem;
        padding: 1rem;
        margin-bottom: 1rem;
    }
    
    .summary-stat {
        text-align: center;
        padding: 0.5rem;
    }
    
    .summary-value {
        font-size: 2rem;
        font-weight: bold;
        color: #111827;
    }
    
    .summary-label {
        font-size: 0.875rem;
        color: #6b7280;
    }
    
    .health-indicator {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        border-radius: 9999px;
        font-weight: 500;
    }
    
    .health-indicator.excellent {
        background: #d1fae5;
        color: #065f46;
    }
    
    .health-indicator.good {
        background: #dbeafe;
        color: #1e40af;
    }
    
    .health-indicator.fair {
        background: #fef3c7;
        color: #92400e;
    }
    
    .health-indicator.poor {
        background: #fee2e2;
        color: #991b1b;
    }
    
    /* Skeleton loading */
    .skeleton {
        background: linear-gradient(90deg, #f3f4f6 25%, #e5e7eb 50%, #f3f4f6 75%);
        background-size: 200% 100%;
        animation: skeleton-loading 1.5s ease-in-out infinite;
    }
    
    @keyframes skeleton-loading {
        0% {
            background-position: 200% 0;
        }
        100% {
            background-position: -200% 0;
        }
    }
</style>
@endpush

@section('visual-content')
    <!-- Header with channel info -->
    <div class="bg-gradient-to-r from-blue-500 to-cyan-600 rounded-lg shadow-lg p-4 sm:p-6 mb-6 text-white">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="text-2xl sm:text-3xl font-bold">@<span id="channelName">{{ e($channel) }}</span></h2>
                <p class="text-blue-100 mt-1 text-sm sm:text-base">User rankings for the last <span id="daysText" class="font-semibold">{{ $days }}</span> days</p>
            </div>
            <div class="sm:text-right">
                <p class="text-xs sm:text-sm text-blue-100">Generated at</p>
                <p class="text-xs sm:text-sm font-medium" id="timestamp"></p>
            </div>
        </div>
    </div>
    
    <!-- Beta Notice & Explanation -->
    <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6">
        <div class="flex items-start gap-3">
            <div class="flex-shrink-0">
                <svg class="w-5 h-5 text-amber-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                </svg>
            </div>
            <div class="flex-1">
                <h3 class="text-sm font-semibold text-amber-800 mb-1">Beta Feature - Algorithm Under Development</h3>
                <p class="text-sm text-amber-700">This ranking system analyzes user engagement patterns to identify valuable community members. Parameters are being continuously adjusted for optimal results.</p>
                
                <details class="mt-3">
                    <summary class="text-sm text-amber-600 hover:text-amber-700 cursor-pointer font-medium">How are rankings calculated?</summary>
                    <div class="mt-2 space-y-2 text-sm text-amber-700">
                        <p class="font-medium">The algorithm evaluates users based on:</p>
                        <ul class="list-disc list-inside space-y-1 ml-2">
                            <li><strong>Message Frequency (15%):</strong> Consistent participation without spamming</li>
                            <li><strong>Engagement Received (25%):</strong> Replies and reactions from other users</li>
                            <li><strong>Content Quality (20%):</strong> Message length and originality</li>
                            <li><strong>Response Speed (10%):</strong> Quick helpful responses to others</li>
                            <li><strong>Conversation Starter (15%):</strong> Initiating meaningful discussions</li>
                            <li><strong>Consistency (10%):</strong> Regular participation over time</li>
                            <li><strong>Helpfulness (5%):</strong> Providing useful responses</li>
                        </ul>
                        <p class="mt-2 text-xs italic">Note: Anonymous users are displayed with their anonymized usernames as configured by the channel administrator.</p>
                    </div>
                </details>
            </div>
        </div>
    </div>
    
    <!-- Days selector -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <label class="block text-sm font-medium text-gray-700 mb-2">Analysis Period</label>
        <div class="flex items-center gap-3">
            <input type="number" id="daysInput" min="1" max="365" value="{{ $days }}" 
                   class="w-24 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-500">
            <span class="text-gray-600">days</span>
            <button id="updateDaysBtn" class="px-4 py-2 bg-purple-500 text-white rounded-lg hover:bg-purple-600 transition-colors">
                Update
            </button>
        </div>
        <p class="text-xs text-gray-500 mt-2">Choose between 1 and 365 days</p>
    </div>
    
    <!-- Summary Cards -->
    <div id="summaryContainer" class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-8"></div>
    
    <!-- Top Users Table -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8" id="topUsersSection">
        <h3 class="text-xl font-bold text-gray-800 mb-4 pb-2 border-b-2 border-gray-200">User Value Rankings</h3>
        <!-- Desktop Table -->
        <div class="hidden sm:block overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rank</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Score</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200" id="rankingsTableBody">
                </tbody>
            </table>
        </div>
        
        <!-- Mobile Cards -->
        <div class="sm:hidden space-y-3" id="rankingsMobile">
        </div>
    </div>
    
    <!-- Load more button -->
    <div id="loadMoreContainer" class="text-center mt-6 hidden">
        <button id="loadMoreBtn" class="px-6 py-3 bg-purple-500 text-white rounded-lg hover:bg-purple-600 transition-colors">
            <span id="loadMoreText">Load More</span>
            <span id="loadingMoreText" class="hidden">Loading...</span>
        </button>
    </div>
    
    <!-- No data message -->
    <div id="noDataMessage" class="text-center py-12 hidden">
        <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
        </svg>
        <h3 class="text-xl font-semibold text-gray-700 mb-2">No user data found</h3>
        <p class="text-gray-500">No messages found in this channel for the selected period.</p>
    </div>
@endsection

@push('page-scripts')
<script>
    const channel = '{{ $channel }}';
    let currentDays = {{ $days }};
    let nextOffset = null;
    let isLoading = false;
    let currentData = null;
    
    // Helper function to escape HTML
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }
    
    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        loadUserValues();
        
        // Event listeners
        const updateBtn = document.getElementById('updateDaysBtn');
        const daysInput = document.getElementById('daysInput');
        const loadMoreBtn = document.getElementById('loadMoreBtn');
        
        if (updateBtn) updateBtn.addEventListener('click', updateDays);
        if (daysInput) daysInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') updateDays();
        });
        if (loadMoreBtn) loadMoreBtn.addEventListener('click', loadMore);
    });
    
    function updateDays() {
        const newDays = parseInt(document.getElementById('daysInput').value);
        if (newDays >= 1 && newDays <= 365 && newDays !== currentDays) {
            currentDays = newDays;
            window.history.pushState({}, '', `/telegram/${channel}/top-contributors/${currentDays}`);
            document.getElementById('daysText').textContent = currentDays;
            loadUserValues();
        }
    }
    
    async function loadUserValues(offset = null) {
        if (isLoading) return;
        isLoading = true;
        
        if (!offset) {
            // Show loading state for initial load
            showLoading();
            nextOffset = null;
        }
        
        try {
            const params = new URLSearchParams({
                days: currentDays
            });
            if (offset) params.append('offset', offset);
            
            const response = await fetch(`/api/v2/telegram/channels/${channel}/top-contributors?${params}`);
            const result = await response.json();
            
            if (!response.ok) {
                throw new Error(result.errors?.[0]?.detail || 'Failed to load user rankings');
            }
            
            const data = result.data;
            
            if (!data) {
                throw new Error('Invalid response format');
            }
            
            currentData = data;
            
            // Show content area
            showContent();
            
            // Update timestamp
            document.getElementById('timestamp').textContent = new Date().toLocaleString();
            
            if (!offset) {
                displaySummary(data.summary);
            }
            
            // Display rankings
            displayRankings(data.rankings, !!offset);
            
            // Handle load more
            if (data.has_more && data.next_offset) {
                nextOffset = data.next_offset;
                document.getElementById('loadMoreContainer').classList.remove('hidden');
            } else {
                document.getElementById('loadMoreContainer').classList.add('hidden');
            }
            
            // Show no data message if empty
            if (!offset && (!data.rankings || data.rankings.length === 0)) {
                document.getElementById('noDataMessage').classList.remove('hidden');
            } else {
                document.getElementById('noDataMessage').classList.add('hidden');
            }
        } catch (error) {
            console.error('Error loading user values:', error);
            showError(error.message || 'Failed to load user rankings');
        } finally {
            isLoading = false;
            const loadMoreText = document.getElementById('loadMoreText');
            const loadingMoreText = document.getElementById('loadingMoreText');
            if (loadMoreText) loadMoreText.classList.remove('hidden');
            if (loadingMoreText) loadingMoreText.classList.add('hidden');
        }
    }
    
    function loadMore() {
        if (nextOffset && !isLoading) {
            document.getElementById('loadMoreText').classList.add('hidden');
            document.getElementById('loadingMoreText').classList.remove('hidden');
            loadUserValues(nextOffset);
        }
    }
    
    function displaySummary(summary) {
        if (!summary) return;
        
        const container = document.getElementById('summaryContainer');
        container.innerHTML = `
            <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow duration-200 p-3 sm:p-5 border-t-4 border-blue-500">
                <p class="text-xs font-medium text-gray-600 uppercase tracking-wider mb-1 sm:mb-2">Total Users</p>
                <p class="text-2xl sm:text-3xl font-bold text-blue-600">${summary.total_users_analyzed || 0}</p>
            </div>
            
            <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow duration-200 p-3 sm:p-5 border-t-4 border-emerald-500">
                <p class="text-xs font-medium text-gray-600 uppercase tracking-wider mb-1 sm:mb-2">Avg Score</p>
                <p class="text-2xl sm:text-3xl font-bold text-emerald-600">${(summary.average_score || 0).toFixed(1)}</p>
            </div>
            
            <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow duration-200 p-3 sm:p-5 border-t-4 border-purple-500">
                <p class="text-xs font-medium text-gray-600 uppercase tracking-wider mb-1 sm:mb-2">Leaders</p>
                <p class="text-2xl sm:text-3xl font-bold text-purple-600">${summary.score_distribution ? (summary.score_distribution['Community Leader'] || 0) : 0}</p>
            </div>
            
            <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow duration-200 p-3 sm:p-5 border-t-4 border-orange-500">
                <p class="text-xs font-medium text-gray-600 uppercase tracking-wider mb-1 sm:mb-2">Health</p>
                <p class="text-xl sm:text-2xl font-bold text-orange-600">${summary.engagement_health || 'Fair'}</p>
            </div>
        `;
    }
    
    function displayRankings(rankings, append = false) {
        if (!rankings || rankings.length === 0) {
            if (!append) {
                document.getElementById('topUsersSection').classList.add('hidden');
            }
            return;
        }
        
        document.getElementById('topUsersSection').classList.remove('hidden');
        
        // Desktop table
        const tbody = document.getElementById('rankingsTableBody');
        const mobileContainer = document.getElementById('rankingsMobile');
        
        if (!append) {
            tbody.innerHTML = '';
            mobileContainer.innerHTML = '';
        }
        
        rankings.forEach((user, relativeIndex) => {
            const index = append ? tbody.children.length / 2 : relativeIndex; // Divide by 2 because we have 2 rows per user
            
            // Desktop rows (main + details)
            const [mainRow, detailsRow] = createDesktopRow(user, index);
            tbody.appendChild(mainRow);
            tbody.appendChild(detailsRow);
            
            // Mobile card
            const card = createMobileCard(user);
            mobileContainer.appendChild(card);
        });
    }
    
    function createDesktopRow(user, index) {
        const rowId = `user-row-${user.user_id}`;
        const detailsId = `user-details-${user.user_id}`;
        
        // Main row
        const row = document.createElement('tr');
        row.id = rowId;
        row.className = index % 2 === 0 ? 'bg-white' : 'bg-gray-50';
        
        // Fix: Get username from the proper field
        const displayName = user.username ? `@${user.username}` : 
                           user.display_name || 
                           user.user_name || 
                           `User ${user.user_id.substr(-6)}`;
                           
        const rankBadge = user.rank === 1 ? '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">🥇</span>' :
                         user.rank === 2 ? '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">🥈</span>' :
                         user.rank === 3 ? '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">🥉</span>' :
                         `<span class="text-gray-500 font-medium">${user.rank}</span>`;
        
        const categoryBadge = getCategoryBadge(user.category);
        
        row.innerHTML = `
            <td class="px-6 py-4 whitespace-nowrap text-sm">
                ${rankBadge}
            </td>
            <td class="px-6 py-4">
                <div>
                    <div class="text-lg font-semibold text-gray-900">${escapeHtml(displayName)}</div>
                    <div class="flex flex-wrap gap-1 mt-1">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${user.category === 'Community Leader' ? 'bg-blue-50 text-blue-700' : 'bg-gray-50 text-gray-600'}">
                            ${user.category}
                        </span>
                        ${user.badges && user.badges.length > 0 ? 
                            `<span class="inline-flex items-center px-2 py-0.5 rounded text-xs text-amber-600">
                                🏆 ×${user.badges.length}
                            </span>` : ''}
                    </div>
                </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-3xl font-bold text-gray-900">${user.total_score}</div>
                        <div class="text-xs text-gray-500">${user.metrics.total_messages} messages</div>
                    </div>
                    <button onclick="toggleUserDetails('${user.user_id}')" 
                            class="ml-8 text-gray-400 hover:text-gray-600 p-2 rounded-full hover:bg-gray-100 transition-all duration-200"
                            title="Show details">
                        <svg class="w-5 h-5 transform transition-transform duration-200" id="arrow-${user.user_id}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                </div>
            </td>
        `;
        
        // Details row (hidden by default)
        const detailsRow = document.createElement('tr');
        detailsRow.id = detailsId;
        detailsRow.className = 'hidden';
        detailsRow.innerHTML = `
            <td colspan="3" class="px-6 py-0 bg-gray-50">
                <div class="overflow-hidden transition-all duration-300" style="max-height: 0;" id="details-content-${user.user_id}">
                    <div class="py-6">
                        ${user.badges && user.badges.length > 0 ? `
                            <div class="mb-6">
                                <h4 class="text-sm font-bold text-gray-700 mb-2 flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                    Achievements
                                </h4>
                                <p class="text-xs text-gray-600 mb-3">Special recognitions earned for outstanding behaviors during this period</p>
                                <div class="space-y-2">
                                    ${user.badges.map(badge => `
                                        <div class="flex items-start gap-2">
                                            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-amber-50 text-amber-700 border border-amber-200 flex-shrink-0">
                                                🏆 ${badge}
                                            </span>
                                            <span class="text-xs text-gray-600 pt-2">${getBadgeDescription(badge)}</span>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        ` : ''}
                        
                        <div class="mb-6">
                            <h4 class="text-sm font-bold text-gray-700 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                                Performance Metrics
                            </h4>
                            <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
                                ${createMetricCard('Message Frequency', user.metrics.message_frequency, 'How consistently they participate', getMetricColor(user.metrics.message_frequency))}
                                ${createMetricCard('Engagement Received', user.metrics.engagement_received, 'Replies and reactions from others', getMetricColor(user.metrics.engagement_received))}
                                ${createMetricCard('Content Quality', user.metrics.content_quality, 'Length and originality of messages', getMetricColor(user.metrics.content_quality))}
                                ${createMetricCard('Response Speed', user.metrics.response_speed, 'How quickly they respond to others', getMetricColor(user.metrics.response_speed))}
                                ${createMetricCard('Conversation Starter', user.metrics.conversation_starter, 'Initiates new discussions', getMetricColor(user.metrics.conversation_starter))}
                                ${createMetricCard('Consistency', user.metrics.consistency, 'Regular participation over time', getMetricColor(user.metrics.consistency))}
                            </div>
                        </div>
                        
                        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-200">
                            <div class="grid grid-cols-3 gap-6">
                                <div class="text-center">
                                    <div class="inline-flex items-center justify-center w-12 h-12 bg-blue-100 rounded-full mb-2">
                                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                                        </svg>
                                    </div>
                                    <div class="text-2xl font-bold text-gray-800">${user.metrics.total_messages}</div>
                                    <div class="text-xs text-gray-600">Total Messages</div>
                                </div>
                                <div class="text-center">
                                    <div class="inline-flex items-center justify-center w-12 h-12 bg-green-100 rounded-full mb-2">
                                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                        </svg>
                                    </div>
                                    <div class="text-2xl font-bold text-gray-800">${user.metrics.avg_message_length}</div>
                                    <div class="text-xs text-gray-600">Avg. Length (chars)</div>
                                </div>
                                <div class="text-center">
                                    <div class="inline-flex items-center justify-center w-12 h-12 bg-purple-100 rounded-full mb-2">
                                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"></path>
                                        </svg>
                                    </div>
                                    <div class="text-2xl font-bold text-gray-800">${user.metrics.helpfulness}%</div>
                                    <div class="text-xs text-gray-600">Helpfulness Score</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </td>
        `;
        
        return [row, detailsRow];
    }
    
    function createMobileCard(user) {
        const card = document.createElement('div');
        card.className = 'bg-white rounded-lg shadow-sm border border-gray-200 p-4';
        
        // Fix: Get username from the proper field
        const displayName = user.username ? `@${user.username}` : 
                           user.display_name || 
                           user.user_name || 
                           `User ${user.user_id.substr(-6)}`;
                           
        const rankBadge = user.rank === 1 ? '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">🥇</span>' :
                         user.rank === 2 ? '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">🥈</span>' :
                         user.rank === 3 ? '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">🥉</span>' :
                         `<span class="text-gray-500 font-medium">#${user.rank}</span>`;
        
        const categoryBadge = getCategoryBadge(user.category);
        
        card.innerHTML = `
            <div class="flex items-start justify-between mb-3">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        ${rankBadge}
                        <span class="font-medium text-gray-900">${escapeHtml(displayName)}</span>
                    </div>
                    <div class="text-xs text-gray-500">${user.metrics.total_messages} messages</div>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-bold text-gray-900">${user.total_score}</div>
                    <div class="text-xs text-gray-500">score</div>
                </div>
            </div>
            <div class="flex flex-wrap gap-1 mb-3">
                ${categoryBadge}
                ${user.badges && user.badges.length > 0 ? 
                    `<span class="inline-flex items-center px-2 py-0.5 rounded text-xs text-amber-600">
                        🏆 ×${user.badges.length}
                    </span>` : ''}
            </div>
            <button onclick="toggleMobileDetails('${user.user_id}')" 
                    class="w-full text-center text-blue-600 hover:text-blue-800 text-sm font-medium py-2 border-t border-gray-100 flex items-center justify-center">
                <span>View Details</span>
                <svg class="w-4 h-4 ml-1 transform transition-transform duration-200" id="mobile-arrow-${user.user_id}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
            <div id="mobile-details-${user.user_id}" class="hidden overflow-hidden transition-all duration-300">
                <div class="pt-4 px-2">
                    ${user.badges && user.badges.length > 0 ? `
                        <div class="mb-4">
                            <h4 class="text-xs font-bold text-gray-700 mb-2">Achievements</h4>
                            <div class="space-y-2">
                                ${user.badges.map(badge => `
                                    <div class="flex items-start gap-2">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200 flex-shrink-0">
                                            🏆 ${badge}
                                        </span>
                                        <span class="text-xs text-gray-600 pt-1">${getBadgeDescription(badge)}</span>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    ` : ''}
                    
                    <div class="space-y-2 mb-3">
                        ${createMobileMetric('Engagement', user.metrics.engagement_received, getMetricColor(user.metrics.engagement_received))}
                        ${createMobileMetric('Quality', user.metrics.content_quality, getMetricColor(user.metrics.content_quality))}
                        ${createMobileMetric('Response Speed', user.metrics.response_speed, getMetricColor(user.metrics.response_speed))}
                        ${createMobileMetric('Consistency', user.metrics.consistency, getMetricColor(user.metrics.consistency))}
                    </div>
                    
                    <div class="grid grid-cols-3 gap-2 text-xs bg-gray-50 rounded-lg p-3">
                        <div class="text-center">
                            <div class="font-bold text-gray-800">${user.metrics.total_messages}</div>
                            <div class="text-gray-600">Messages</div>
                        </div>
                        <div class="text-center">
                            <div class="font-bold text-gray-800">${user.metrics.avg_message_length}</div>
                            <div class="text-gray-600">Avg Length</div>
                        </div>
                        <div class="text-center">
                            <div class="font-bold text-gray-800">${user.metrics.helpfulness}%</div>
                            <div class="text-gray-600">Helpful</div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        return card;
    }
    
    function getCategoryBadge(category) {
        const categoryMap = {
            'Community Leader': { bg: 'bg-blue-100', text: 'text-blue-800' },
            'Active Contributor': { bg: 'bg-green-100', text: 'text-green-800' },
            'Regular Member': { bg: 'bg-purple-100', text: 'text-purple-800' },
            'Casual Participant': { bg: 'bg-yellow-100', text: 'text-yellow-800' },
            'Observer': { bg: 'bg-gray-100', text: 'text-gray-600' }
        };
        
        const style = categoryMap[category] || categoryMap['Observer'];
        return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${style.bg} ${style.text}">${category}</span>`;
    }
    
    function toggleUserDetails(userId) {
        const detailsRow = document.getElementById(`user-details-${userId}`);
        const detailsContent = document.getElementById(`details-content-${userId}`);
        const arrow = document.getElementById(`arrow-${userId}`);
        
        if (detailsRow.classList.contains('hidden')) {
            // Show details
            detailsRow.classList.remove('hidden');
            setTimeout(() => {
                detailsContent.style.maxHeight = detailsContent.scrollHeight + 'px';
                arrow.classList.add('rotate-180');
            }, 10);
        } else {
            // Hide details
            detailsContent.style.maxHeight = '0';
            arrow.classList.remove('rotate-180');
            setTimeout(() => {
                detailsRow.classList.add('hidden');
            }, 300);
        }
    }
    
    function createMetricCard(label, value, description, color = 'blue') {
        const colorClasses = {
            green: { text: 'text-green-600', bg: 'from-green-400 to-green-600' },
            blue: { text: 'text-blue-600', bg: 'from-blue-400 to-blue-600' },
            yellow: { text: 'text-yellow-600', bg: 'from-yellow-400 to-yellow-600' },
            orange: { text: 'text-orange-600', bg: 'from-orange-400 to-orange-600' },
            red: { text: 'text-red-600', bg: 'from-red-400 to-red-600' }
        };
        const colors = colorClasses[color] || colorClasses.blue;
        
        return `
            <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-200 hover:shadow-md transition-shadow duration-200">
                <div class="flex justify-between items-center mb-3">
                    <span class="text-sm font-medium text-gray-700">${label}</span>
                    <span class="text-2xl font-bold ${colors.text}">${value}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2.5 mb-2 overflow-hidden">
                    <div class="bg-gradient-to-r ${colors.bg} h-2.5 rounded-full transition-all duration-700 ease-out" style="width: ${value}%"></div>
                </div>
                <p class="text-xs text-gray-600 leading-relaxed">${description}</p>
            </div>
        `;
    }
    
    function getMetricColor(value) {
        if (value >= 80) return 'green';
        if (value >= 60) return 'blue';
        if (value >= 40) return 'yellow';
        if (value >= 20) return 'orange';
        return 'red';
    }
    
    function toggleMobileDetails(userId) {
        const details = document.getElementById(`mobile-details-${userId}`);
        const arrow = document.getElementById(`mobile-arrow-${userId}`);
        
        if (details.classList.contains('hidden')) {
            details.classList.remove('hidden');
            arrow.classList.add('rotate-180');
        } else {
            details.classList.add('hidden');
            arrow.classList.remove('rotate-180');
        }
    }
    
    function createMobileMetric(label, value, color = 'blue') {
        const colorClasses = {
            green: { text: 'text-green-600', bg: 'from-green-400 to-green-600' },
            blue: { text: 'text-blue-600', bg: 'from-blue-400 to-blue-600' },
            yellow: { text: 'text-yellow-600', bg: 'from-yellow-400 to-yellow-600' },
            orange: { text: 'text-orange-600', bg: 'from-orange-400 to-orange-600' },
            red: { text: 'text-red-600', bg: 'from-red-400 to-red-600' }
        };
        const colors = colorClasses[color] || colorClasses.blue;
        
        return `
            <div class="flex justify-between items-center bg-white rounded-lg p-2 shadow-sm">
                <span class="text-xs font-medium text-gray-700">${label}</span>
                <div class="flex items-center">
                    <div class="w-20 bg-gray-200 rounded-full h-2 mr-2 overflow-hidden">
                        <div class="bg-gradient-to-r ${colors.bg} h-2 rounded-full transition-all duration-500" style="width: ${value}%"></div>
                    </div>
                    <span class="text-xs font-bold ${colors.text}">${value}%</span>
                </div>
            </div>
        `;
    }
    
    function getBadgeDescription(badge) {
        const descriptions = {
            'Lightning Responder': 'Responds to messages within 10 minutes',
            'Conversation Catalyst': 'Frequently starts new discussions',
            'Helpful Hero': 'Provides useful and helpful responses',
            'Daily Devotee': 'Participates consistently every day',
            'Engagement Magnet': 'Receives many replies and reactions',
            'Social Butterfly': 'Interacts with many different users'
        };
        return descriptions[badge] || 'Special achievement';
    }
    
</script>
@endpush