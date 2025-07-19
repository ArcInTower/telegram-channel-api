@extends('layouts.app')

@section('title', 'Channel Polls - ' . ($channel ?? 'Telegram'))

@push('styles')
<style>
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
    
    .hidden {
        display: none !important;
    }
    
    .poll-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 0.75rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        transition: all 0.2s;
    }
    
    .poll-card:hover {
        border-color: #3b82f6;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }
    
    .poll-option {
        position: relative;
        background: #f3f4f6;
        border-radius: 0.5rem;
        padding: 0.75rem 1rem;
        margin-bottom: 0.5rem;
        overflow: hidden;
    }
    
    .poll-option-bar {
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        background: #dbeafe;
        transition: width 0.3s ease;
        z-index: 0;
    }
    
    .poll-option-content {
        position: relative;
        z-index: 1;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .poll-option.winner .poll-option-bar {
        background: #bfdbfe;
    }
    
    .poll-option.chosen {
        /* Removed special styling for chosen option for privacy */
    }
    
    .poll-meta {
        display: flex;
        gap: 1rem;
        font-size: 0.875rem;
        color: #6b7280;
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid #e5e7eb;
    }
    
    .poll-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.25rem 0.75rem;
        background: #eff6ff;
        color: #1e40af;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 500;
    }
    
    .poll-badge.closed {
        background: #f3f4f6;
        color: #6b7280;
    }
    
    .poll-badge.quiz {
        background: #f0fdf4;
        color: #166534;
    }
    
    .load-more-container {
        text-align: center;
        margin: 2rem 0;
    }
    
    .load-more-btn {
        background: #3b82f6;
        color: white;
        padding: 0.75rem 2rem;
        border-radius: 0.5rem;
        font-weight: 500;
        transition: all 0.2s;
        border: none;
        cursor: pointer;
    }
    
    .load-more-btn:hover {
        background: #2563eb;
        transform: translateY(-1px);
    }
    
    .load-more-btn:disabled {
        background: #9ca3af;
        cursor: not-allowed;
        transform: none;
    }
    
    .no-polls {
        text-align: center;
        padding: 4rem 2rem;
        color: #6b7280;
    }
    
    .no-polls svg {
        width: 4rem;
        height: 4rem;
        margin: 0 auto 1rem;
        opacity: 0.3;
    }
    
    /* Modal styles */
    .modal-backdrop {
        position: fixed;
        inset: 0;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 40;
    }
    
    .modal-container {
        position: fixed;
        inset: 0;
        z-index: 50;
        overflow-y: auto;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }
    
    .modal-content {
        position: relative;
        background-color: white;
        border-radius: 0.5rem;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        max-width: 28rem;
        width: 100%;
        padding: 1.5rem;
        animation: modalAppear 0.2s ease-out;
    }
    
    @keyframes modalAppear {
        from {
            opacity: 0;
            transform: scale(0.95);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }
</style>
@endpush

@section('content')
<div class="container mx-auto px-4 py-8 max-w-5xl">
    <div id="loading" class="loading">
        <div class="spinner"></div>
    </div>
    
    <div id="error" class="hidden">
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative" role="alert">
            <strong class="font-bold">Error!</strong>
            <span class="block sm:inline" id="errorMessage"></span>
        </div>
    </div>
    
    <div id="content" class="hidden">
        <!-- Header with channel info -->
        <div class="bg-gradient-to-r from-blue-500 to-cyan-600 rounded-lg shadow-lg p-4 sm:p-6 mb-6 text-white">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h2 class="text-2xl sm:text-3xl font-bold">@<span id="channelName">{{ $channel }}</span> Polls</h2>
                    <p class="text-blue-100 mt-1 text-sm sm:text-base">Showing polls from <span id="periodText" class="font-semibold"></span></p>
                </div>
                <div class="sm:text-right">
                    <p class="text-sm text-blue-100">Total polls found:</p>
                    <p class="text-2xl font-bold" id="totalPolls">0</p>
                </div>
            </div>
            
            <!-- Info notice about hidden results -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mt-3">
                <p class="text-sm text-blue-800">
                    <strong>ℹ️ Note:</strong> Some poll results may be hidden. Only closed polls or polls with public voting show results. 
                    <a href="#" onclick="showHowToViewResults(); return false;" class="underline hover:text-blue-900">Learn more</a>
                </p>
            </div>
        </div>
        
        <!-- Period selector -->
        <div class="bg-white rounded-lg shadow p-4 mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Time Period</label>
            <select id="periodSelect" class="w-full sm:w-auto px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                <option value="1hour" {{ $period == '1hour' ? 'selected' : '' }}>Last hour</option>
                <option value="1day" {{ $period == '1day' ? 'selected' : '' }}>Last 24 hours</option>
                <option value="7days" {{ $period == '7days' ? 'selected' : '' }}>Last 7 days</option>
                <option value="30days" {{ $period == '30days' ? 'selected' : '' }}>Last 30 days</option>
                <option value="3months" {{ $period == '3months' ? 'selected' : '' }}>Last 3 months</option>
                <option value="6months" {{ $period == '6months' ? 'selected' : '' }}>Last 6 months</option>
                <option value="1year" {{ $period == '1year' ? 'selected' : '' }}>Last year</option>
            </select>
        </div>
        
        <!-- Polls container -->
        <div id="pollsContainer"></div>
        
        <!-- Load more button -->
        <div id="loadMoreContainer" class="load-more-container hidden">
            <button id="loadMoreBtn" class="load-more-btn">
                <span id="loadMoreText">Load More Polls</span>
                <span id="loadingMoreText" class="hidden">Loading...</span>
            </button>
            <p class="text-sm text-gray-600 mt-2">
                Scanned <span id="messagesScanned">0</span> messages
            </p>
        </div>
        
        <!-- No polls message -->
        <div id="noPollsMessage" class="no-polls hidden">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
            </svg>
            <h3 class="text-xl font-semibold mb-2">No polls found</h3>
            <p>No polls were found in this channel for the selected time period.</p>
        </div>
    </div>
    
    <!-- Modal for viewing hidden results -->
    <div id="howToViewModal" class="hidden">
        <div class="modal-backdrop" onclick="hideHowToViewResults()"></div>
        <div class="modal-container">
            <div class="modal-content">
                <div class="flex justify-between items-start mb-4">
                    <h3 class="text-lg font-semibold">How to View Hidden Poll Results</h3>
                    <button onclick="hideHowToViewResults()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <div class="space-y-3 text-sm text-gray-700">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                        <p class="font-medium text-blue-900">Why are some results hidden?</p>
                        <p class="text-blue-700 mt-1">Telegram only shows poll results for:</p>
                        <ul class="list-disc list-inside mt-2 text-blue-700 text-sm">
                            <li>Closed polls</li>
                            <li>Polls with public voting enabled</li>
                            <li>Users who have voted (when viewing in Telegram)</li>
                        </ul>
                    </div>
                    
                    <div class="mt-4">
                        <p class="font-medium mb-2">This API shows results when available</p>
                        <p class="text-gray-600 text-sm">If results are hidden, it means the poll is still active and doesn't have public voting enabled. The poll creator controls this setting.</p>
                    </div>
                    
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 mt-4">
                        <p class="text-xs text-gray-600">
                            <strong class="text-gray-700">Tip:</strong> To see all results in Telegram, users need to vote first. This API only accesses publicly available data.
                        </p>
                    </div>
                </div>
                
                <button onclick="hideHowToViewResults()" class="mt-6 w-full bg-blue-500 text-white py-2 px-4 rounded-lg hover:bg-blue-600 transition-colors">
                    Got it
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const channel = '{{ $channel }}';
    const initialPeriod = '{{ $period }}';
    let currentPeriod = initialPeriod;
    let nextOffset = null;
    let isLoading = false;
    let allPolls = [];
    
    // Period text mapping
    const periodTexts = {
        '1hour': 'the last hour',
        '1day': 'the last 24 hours',
        '7days': 'the last 7 days',
        '30days': 'the last 30 days',
        '3months': 'the last 3 months',
        '6months': 'the last 6 months',
        '1year': 'the last year'
    };
    
    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadPolls();
        
        // Period change handler
        document.getElementById('periodSelect').addEventListener('change', function(e) {
            currentPeriod = e.target.value;
            // Update URL without reloading
            window.history.pushState({}, '', `/polls/${channel}/${currentPeriod}`);
            resetAndLoad();
        });
        
        // Load more button handler
        document.getElementById('loadMoreBtn').addEventListener('click', function() {
            if (!isLoading && nextOffset) {
                loadPolls(nextOffset);
            }
        });
    });
    
    function resetAndLoad() {
        allPolls = [];
        nextOffset = null;
        document.getElementById('pollsContainer').innerHTML = '';
        document.getElementById('loadMoreContainer').classList.add('hidden');
        document.getElementById('noPollsMessage').classList.add('hidden');
        loadPolls();
    }
    
    async function loadPolls(offset = null) {
        if (isLoading) return;
        isLoading = true;
        
        // Show appropriate loading state
        if (offset === null) {
            document.getElementById('loading').classList.remove('hidden');
            document.getElementById('content').classList.add('hidden');
            document.getElementById('error').classList.add('hidden');
        } else {
            document.getElementById('loadMoreBtn').disabled = true;
            document.getElementById('loadMoreText').classList.add('hidden');
            document.getElementById('loadingMoreText').classList.remove('hidden');
        }
        
        try {
            let url = `/api/v2/telegram/channels/${encodeURIComponent(channel)}/polls?period=${currentPeriod}`;
            if (offset) {
                url += `&offset=${offset}`;
            }
            
            const response = await fetch(url);
            const result = await response.json();
            
            console.log('API Response:', result); // Debug log
            
            if (!response.ok) {
                throw new Error(result.errors?.[0]?.detail || 'Failed to load polls');
            }
            
            const data = result.data;
            
            if (!data) {
                throw new Error('Invalid response format');
            }
            
            // Update UI with data
            updateUI(data, offset === null);
            
            // Handle pagination
            if (data.has_more && data.next_offset) {
                nextOffset = data.next_offset;
                document.getElementById('loadMoreContainer').classList.remove('hidden');
            } else {
                nextOffset = null;
                document.getElementById('loadMoreContainer').classList.add('hidden');
            }
            
            // Update scanned messages count
            document.getElementById('messagesScanned').textContent = data.messages_scanned.toLocaleString();
            
        } catch (error) {
            if (offset === null) {
                showError(error.message);
            } else {
                alert('Error loading more polls: ' + error.message);
            }
        } finally {
            isLoading = false;
            document.getElementById('loadMoreBtn').disabled = false;
            document.getElementById('loadMoreText').classList.remove('hidden');
            document.getElementById('loadingMoreText').classList.add('hidden');
        }
    }
    
    function updateUI(data, isFirstLoad) {
        if (isFirstLoad) {
            document.getElementById('loading').classList.add('hidden');
            document.getElementById('content').classList.remove('hidden');
            
            // Update header info
            document.getElementById('channelName').textContent = data.channel || channel;
            document.getElementById('periodText').textContent = periodTexts[currentPeriod] || currentPeriod;
        }
        
        // Check data structure
        console.log('Data structure:', data);
        console.log('Data keys:', Object.keys(data));
        
        // Add new polls to our array
        const polls = data.polls || [];
        console.log('Polls array:', polls);
        console.log('First poll:', polls[0]);
        
        // Filter out invalid polls before adding
        const validPolls = polls.filter(poll => {
            return poll && 
                   typeof poll === 'object' && 
                   poll.question && 
                   poll.message_id &&
                   poll.answers && 
                   Array.isArray(poll.answers);
        });
        
        console.log(`Valid polls: ${validPolls.length} out of ${polls.length}`);
        
        allPolls = allPolls.concat(validPolls);
        
        // Update total count with valid polls only
        document.getElementById('totalPolls').textContent = allPolls.length;
        
        // Render new polls
        if (validPolls && validPolls.length > 0) {
            renderPolls(validPolls);
        } else if (isFirstLoad && validPolls.length === 0) {
            document.getElementById('noPollsMessage').classList.remove('hidden');
        }
    }
    
    function renderPolls(polls) {
        const container = document.getElementById('pollsContainer');
        
        if (!Array.isArray(polls)) {
            console.error('Polls is not an array:', polls);
            return;
        }
        
        polls.forEach((poll, index) => {
            console.log(`Processing poll ${index}:`, poll);
            
            // Skip invalid polls
            if (!poll || typeof poll !== 'object') {
                console.warn(`Skipping invalid poll at index ${index}:`, poll);
                return;
            }
            
            // Check if it's actually a poll (has question and answers)
            if (!poll.question || !poll.answers || !Array.isArray(poll.answers)) {
                console.warn(`Skipping item without poll data at index ${index}:`, poll);
                return;
            }
            
            const pollCard = createPollCard(poll);
            container.appendChild(pollCard);
        });
    }
    
    function createPollCard(poll) {
        console.log('Creating card for poll:', poll); // Debug log
        
        const card = document.createElement('div');
        card.className = 'poll-card';
        
        // Validate poll data
        if (!poll || typeof poll !== 'object') {
            console.error('Invalid poll data:', poll);
            card.innerHTML = '<p class="text-red-500">Error: Invalid poll data</p>';
            return card;
        }
        
        // Additional validation for required fields
        if (!poll.question || !poll.message_id) {
            console.warn('Poll missing required fields:', poll);
            // Don't render polls without essential data
            card.style.display = 'none';
            return card;
        }
        
        // Find the winning option
        let maxVotes = 0;
        let winnerIndex = -1;
        if (poll.answers && Array.isArray(poll.answers)) {
            poll.answers.forEach((answer, index) => {
                if ((answer.voters || 0) > maxVotes) {
                    maxVotes = answer.voters || 0;
                    winnerIndex = index;
                }
            });
        }
        
        // Create poll HTML
        let optionsHtml = '';
        const resultsVisible = poll.results_visible !== false && poll.total_voters > 0;
        
        if (poll.answers && Array.isArray(poll.answers)) {
            poll.answers.forEach((answer, index) => {
                const percentage = answer.percentage || 0;
                const isWinner = index === winnerIndex && poll.total_voters > 0;
                
                if (resultsVisible) {
                    // Show results
                    optionsHtml += `
                        <div class="poll-option ${isWinner ? 'winner' : ''}">
                            <div class="poll-option-bar" style="width: ${percentage}%"></div>
                            <div class="poll-option-content">
                                <span class="font-medium">${escapeHtml(answer.text)}</span>
                                <span class="text-sm">
                                    <strong>${answer.voters || 0}</strong> (${percentage}%)
                                </span>
                            </div>
                        </div>
                    `;
                } else {
                    // Results hidden - show options without percentages
                    optionsHtml += `
                        <div class="poll-option" style="background: #f9fafb; border: 1px solid #e5e7eb;">
                            <div class="poll-option-content">
                                <span class="font-medium">${escapeHtml(answer.text)}</span>
                                <span class="text-sm text-gray-400">
                                    <a href="#" onclick="showHowToViewResults(); return false;" class="hover:text-gray-600">
                                        Results hidden ℹ️
                                    </a>
                                </span>
                            </div>
                        </div>
                    `;
                }
            });
        }
        
        // Poll badges
        let badges = '';
        if (poll.closed) {
            badges += '<span class="poll-badge closed">Closed</span>';
        }
        if (poll.quiz) {
            badges += '<span class="poll-badge quiz">Quiz</span>';
        }
        if (poll.multiple_choice) {
            badges += '<span class="poll-badge">Multiple Choice</span>';
        }
        
        // Check if we can see results
        if (!resultsVisible && !poll.closed) {
            badges += '<span class="poll-badge" style="background: #fef3c7; color: #92400e;">⚠️ Results hidden</span>';
        }
        
        card.innerHTML = `
            <div class="mb-4">
                <h3 class="text-lg font-semibold mb-3">${escapeHtml(poll.question || 'No question')}</h3>
                ${optionsHtml}
            </div>
            <div class="poll-meta">
                <span>📊 ${poll.total_voters || 0} votes</span>
                <span>📅 ${formatDate(poll.date || new Date().toISOString())}</span>
                <span>
                    <a href="https://t.me/${channel}/${poll.message_id || ''}" target="_blank" class="text-blue-500 hover:underline">
                        View in Telegram →
                    </a>
                </span>
            </div>
            ${badges ? `<div class="mt-3">${badges}</div>` : ''}
            ${poll.message_text ? `<div class="mt-3 text-sm text-gray-600 italic">${escapeHtml(poll.message_text.substring(0, 100))}...</div>` : ''}
        `;
        
        return card;
    }
    
    function formatDate(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);
        
        if (diffMins < 60) {
            return `${diffMins} minutes ago`;
        } else if (diffMins < 1440) {
            const hours = Math.floor(diffMins / 60);
            return `${hours} hour${hours > 1 ? 's' : ''} ago`;
        } else {
            const days = Math.floor(diffMins / 1440);
            if (days < 7) {
                return `${days} day${days > 1 ? 's' : ''} ago`;
            } else {
                return date.toLocaleDateString();
            }
        }
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    function showError(message) {
        document.getElementById('loading').classList.add('hidden');
        document.getElementById('content').classList.add('hidden');
        document.getElementById('error').classList.remove('hidden');
        document.getElementById('errorMessage').textContent = message;
    }
    
    function showHowToViewResults() {
        document.getElementById('howToViewModal').classList.remove('hidden');
    }
    
    function hideHowToViewResults() {
        document.getElementById('howToViewModal').classList.add('hidden');
    }
</script>
@endpush