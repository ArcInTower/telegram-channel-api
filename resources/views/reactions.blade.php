@extends('layouts.visual')

@section('title', 'Channel Reactions - ' . ($channel ?? 'Telegram'))

@push('page-styles')
<style>
    /* Page specific styles */
    .reaction-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 0.75rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        transition: all 0.2s;
    }
    
    .reaction-card:hover {
        border-color: #3b82f6;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }
    
    .reaction-bar {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.75rem;
        background: #f9fafb;
        border-radius: 0.5rem;
        margin-bottom: 0.5rem;
    }
    
    .reaction-emoji {
        font-size: 1.5rem;
        min-width: 2rem;
        text-align: center;
    }
    
    .reaction-progress {
        flex: 1;
        height: 2rem;
        background: #e5e7eb;
        border-radius: 9999px;
        overflow: hidden;
        position: relative;
    }
    
    .reaction-progress-bar {
        height: 100%;
        background: linear-gradient(90deg, #3b82f6 0%, #60a5fa 100%);
        transition: width 0.5s ease;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        padding-right: 0.75rem;
        color: white;
        font-weight: 600;
        font-size: 0.875rem;
    }
    
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
    
    
    .premium-badge {
        background: linear-gradient(135deg, #a855f7 0%, #ec4899 100%);
        color: white;
        padding: 0.125rem 0.5rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
        margin-left: 0.5rem;
    }
</style>
@endpush

@section('visual-content')
        <!-- Header -->
        <div class="bg-gradient-to-r from-purple-500 to-pink-600 rounded-lg shadow-lg p-6 mb-6 text-white">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold">@<span id="channelName">{{ $channel }}</span> Reactions</h1>
                    <p class="text-purple-100 mt-1">Engagement analytics for <span id="periodText" class="font-semibold"></span></p>
                </div>
                <div class="flex gap-4">
                    <div class="text-center">
                        <p class="text-sm text-purple-100">Total Reactions</p>
                        <p class="text-2xl font-bold" id="totalReactions">-</p>
                    </div>
                    <div class="text-center">
                        <p class="text-sm text-purple-100">Engagement Rate</p>
                        <p class="text-2xl font-bold"><span id="engagementRate">-</span>%</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Period selector -->
        <div class="bg-white rounded-lg shadow p-4 mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Time Period</label>
            <select id="periodSelect" class="w-full sm:w-auto px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-500">
                <option value="1hour" {{ $period == '1hour' ? 'selected' : '' }}>Last hour</option>
                <option value="1day" {{ $period == '1day' ? 'selected' : '' }}>Last 24 hours</option>
                <option value="7days" {{ $period == '7days' ? 'selected' : '' }}>Last 7 days</option>
                <option value="30days" {{ $period == '30days' ? 'selected' : '' }}>Last 30 days</option>
                <option value="3months" {{ $period == '3months' ? 'selected' : '' }}>Last 3 months</option>
                <option value="6months" {{ $period == '6months' ? 'selected' : '' }}>Last 6 months</option>
                <option value="1year" {{ $period == '1year' ? 'selected' : '' }}>Last year</option>
            </select>
        </div>
        
        <!-- Stats Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="stat-card">
                <div class="stat-label">Messages Analyzed</div>
                <div class="stat-value" id="messagesAnalyzed">-</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">With Reactions</div>
                <div class="stat-value" id="messagesWithReactions">-</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Avg per Message</div>
                <div class="stat-value" id="avgReactions">-</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Reaction Types</div>
                <div class="stat-value" id="reactionTypes">-</div>
            </div>
        </div>
        
        <!-- Reactions List -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-semibold mb-4">Most Used Reactions</h3>
            <div id="reactionsList"></div>
        </div>
@endsection

@push('page-scripts')
<script>
    const channel = '{{ $channel }}';
    const initialPeriod = '{{ $period }}';
    let currentPeriod = initialPeriod;
    let channelData = null;
    
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
    
    // Map emoji to Telegram-style colored versions
    function getTelegramEmoji(emoji) {
        const emojiMap = {
            '❤': '❤️',     // Red heart
            '👍': '👍',     // Thumbs up (already good)
            '👎': '👎',     // Thumbs down
            '🔥': '🔥',     // Fire (already good)
            '🎉': '🎉',     // Party popper (already good)
            '😁': '😁',     // Grinning face
            '😢': '😢',     // Crying face
            '😱': '😱',     // Screaming face
            '🤩': '🤩',     // Star eyes
            '🤔': '🤔',     // Thinking face
            '🤯': '🤯',     // Mind blown
            '😡': '😡',     // Angry face
            '🤮': '🤮',     // Vomiting face
            '💩': '💩',     // Poop
            '🙏': '🙏',     // Praying hands
            '👌': '👌',     // OK hand
            '🕊': '🕊️',    // Dove
            '🤡': '🤡',     // Clown
            '🥰': '🥰',     // Smiling face with hearts
            '🥱': '🥱',     // Yawning face
            '🥴': '🥴',     // Woozy face
            '😍': '😍',     // Heart eyes
            '🐳': '🐳',     // Whale
            '❤‍🔥': '❤️‍🔥', // Heart on fire
            '🌚': '🌚',     // New moon face
            '🌭': '🌭',     // Hot dog
            '💯': '💯',     // 100
            '🤣': '🤣',     // Rolling on floor laughing
            '⚡': '⚡',     // Lightning
            '🍌': '🍌',     // Banana
            '🏆': '🏆',     // Trophy
            '💔': '💔',     // Broken heart
            '🤨': '🤨',     // Raised eyebrow
            '😐': '😐',     // Neutral face
            '🍓': '🍓',     // Strawberry
            '🍾': '🍾',     // Champagne
            '💋': '💋',     // Kiss
            '🖕': '🖕',     // Middle finger
            '😈': '😈',     // Smiling devil
            '😴': '😴',     // Sleeping face
            '🤓': '🤓',     // Nerd face
            '👻': '👻',     // Ghost
            '👨‍💻': '👨‍💻', // Man technologist
            '👀': '👀',     // Eyes
            '🎃': '🎃',     // Jack-o-lantern
            '🙈': '🙈',     // See no evil monkey
            '😇': '😇',     // Smiling face with halo
            '😨': '😨',     // Fearful face
            '🤝': '🤝',     // Handshake
            '✍': '✍️',     // Writing hand
            '🤗': '🤗',     // Hugging face
            '🫡': '🫡',     // Saluting face
            '🎅': '🎅',     // Santa
            '🎄': '🎄',     // Christmas tree
            '☃': '☃️',     // Snowman
            '💅': '💅',     // Nail polish
            '🤪': '🤪',     // Zany face
            '🗿': '🗿',     // Moai
            '🆒': '🆒',     // Cool
            '💘': '💘',     // Heart with arrow
            '🙉': '🙉',     // Hear no evil monkey
            '🦄': '🦄',     // Unicorn
            '😘': '😘',     // Face blowing kiss
            '💊': '💊',     // Pill
            '🙊': '🙊',     // Speak no evil monkey
            '😎': '😎',     // Smiling face with sunglasses
            '👾': '👾',     // Alien monster
            '🤷‍♂': '🤷‍♂️', // Man shrugging
            '🤷': '🤷',     // Person shrugging
            '🤷‍♀': '🤷‍♀️',  // Woman shrugging
            '☺': '☺️',     // Smiling face
            '😊': '😊',     // Smiling face with smiling eyes
        };
        
        return emojiMap[emoji] || emoji;
    }
    
    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadData();
        
        // Period change handler
        document.getElementById('periodSelect').addEventListener('change', function(e) {
            currentPeriod = e.target.value;
            window.history.pushState({}, '', `/reactions/${channel}/${currentPeriod}`);
            loadData();
        });
    });
    
    async function loadData() {
        showLoading();
        
        try {
            // Load channel reactions
            const channelResponse = await fetch(`/api/v2/telegram/channels/${encodeURIComponent(channel)}/reactions?period=${currentPeriod}&limit=1000`);
            
            if (!channelResponse.ok) {
                const error = await channelResponse.json();
                throw new Error(error.errors?.[0]?.detail || 'Failed to load reactions data');
            }
            
            const channelResult = await channelResponse.json();
            channelData = channelResult.data;
            
            updateUI();
            
        } catch (error) {
            showError(error.message);
        }
    }
    
    function updateUI() {
        showContent();
        
        // Update header
        document.getElementById('channelName').textContent = channelData.channel || channel;
        document.getElementById('periodText').textContent = periodTexts[currentPeriod] || currentPeriod;
        document.getElementById('totalReactions').textContent = channelData.total_reactions.toLocaleString();
        document.getElementById('engagementRate').textContent = channelData.engagement_rate.toFixed(1);
        
        // Update stats
        document.getElementById('messagesAnalyzed').textContent = channelData.analyzed_messages.toLocaleString();
        document.getElementById('messagesWithReactions').textContent = channelData.messages_with_reactions.toLocaleString();
        document.getElementById('avgReactions').textContent = channelData.average_reactions_per_message.toFixed(1);
        document.getElementById('reactionTypes').textContent = channelData.reaction_types.length;
        
        // Update reactions list
        updateReactionsList();
    }
    
    function updateReactionsList() {
        const container = document.getElementById('reactionsList');
        container.innerHTML = '';
        
        if (!channelData.reaction_types || channelData.reaction_types.length === 0) {
            container.innerHTML = '<p class="text-gray-500">No reactions found in this period.</p>';
            return;
        }
        
        // Get max count for percentage calculation
        const maxCount = Math.max(...channelData.reaction_types.map(r => r.count));
        
        channelData.reaction_types.forEach(reaction => {
            const percentage = channelData.total_reactions > 0 
                ? ((reaction.count / channelData.total_reactions) * 100).toFixed(1)
                : 0;
            const barWidth = maxCount > 0 ? (reaction.count / maxCount) * 100 : 0;
            
            const reactionBar = document.createElement('div');
            reactionBar.className = 'reaction-bar';
            
            // Don't show icon for premium reactions (custom emojis)
            const showEmoji = !reaction.is_premium || isNaN(reaction.emoji);
            
            reactionBar.innerHTML = `
                <div class="reaction-emoji">${showEmoji ? getTelegramEmoji(reaction.emoji) : ''}</div>
                <div class="reaction-progress">
                    <div class="reaction-progress-bar" style="width: ${barWidth}%">
                        ${reaction.count}
                    </div>
                </div>
                <div class="text-sm text-gray-600" style="min-width: 60px; text-align: right;">
                    ${percentage}%
                    ${reaction.is_premium ? '<span class="premium-badge">Premium</span>' : ''}
                </div>
            `;
            
            container.appendChild(reactionBar);
        });
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
</script>
@endpush