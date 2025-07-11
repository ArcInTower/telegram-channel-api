@extends('layouts.app')

@section('content')
        <div class="text-center mb-12 mt-12">
            <p class="text-2xl text-gray-600 mb-3">
                Get real-time data and statistics from public Telegram channels
            </p>
            <p class="text-base text-gray-400 mb-10">
                An unofficial API for retrieving public channel data
            </p>
            
            <div class="bg-gradient-to-br from-blue-50 to-cyan-50 border border-blue-200 rounded-2xl p-6 max-w-2xl mx-auto shadow-lg">
                <div class="flex items-center justify-center gap-2 mb-4">
                    <span class="inline-block w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                    <span class="text-sm font-semibold text-blue-700">Available Endpoints</span>
                </div>
                <div class="flex flex-col gap-2">
                    <code class="block bg-white px-2 sm:px-4 py-3 rounded-lg text-xs text-gray-800 font-mono border border-gray-200 overflow-x-auto whitespace-nowrap">
                        GET /api/v2/telegram/channels/{channel}/messages/last-id
                    </code>
                    <code class="block bg-white px-2 sm:px-4 py-3 rounded-lg text-xs text-gray-800 font-mono border border-gray-200 overflow-x-auto whitespace-nowrap">
                        GET /api/v2/telegram/channels/{channel}/statistics/{days}
                    </code>
                </div>
                <p class="text-center mt-4 text-sm text-gray-600">
                    Try them out below with interactive examples ↓
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Last Message ID Card -->
            <div class="bg-white border border-gray-200 rounded-xl p-10 shadow-md relative">
                <h2 class="text-2xl font-semibold text-gray-800 mb-3 flex items-center gap-3">🛰️ Last Message ID</h2>
                <p class="text-gray-600 mb-6">Retrieve the latest message ID from any public Telegram channel</p>
                
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <p class="text-blue-700 text-sm m-0">✓ Rate limit: 60 requests per minute | ✓ Cache: 5 minutes TTL (auto-expires)</p>
                </div>
                
                <div class="bg-gray-800 text-gray-200 p-4 rounded-lg font-mono text-sm overflow-x-auto mb-4">GET /api/v2/telegram/channels/{channel}/messages/last-id</div>
                
                <div class="flex gap-4 mb-4 items-end">
                    <div class="flex-1 flex flex-col gap-2">
                        <label class="text-sm text-gray-600">Channel username</label>
                        <input type="text" id="channelInput" placeholder="e.g., python" value="orangeterapy" class="w-full px-4 py-3 border border-gray-200 rounded-lg text-base focus:outline-none focus:border-blue-500 transition-colors">
                    </div>
                    <button onclick="getLastMessage()" class="px-6 py-3 bg-blue-500 text-white rounded-lg font-medium hover:bg-blue-600 transform hover:-translate-y-px transition-all whitespace-nowrap">Try it</button>
                </div>
                
                <div class="mt-4 text-right h-6">
                    <!-- Placeholder space to align with statistics card -->
                </div>
                
                <div id="messageResult" class="mt-6 p-6 rounded-xl font-mono text-sm overflow-x-auto hidden whitespace-pre leading-relaxed"></div>
                
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 mt-8">
                    <h3 class="text-gray-800 text-lg font-semibold mb-4">🚀 Quick Start</h3>
                    <p class="text-gray-600 text-sm leading-relaxed mb-2">Try with cURL:</p>
                    <div class="bg-gray-800 text-gray-200 p-4 rounded-lg font-mono text-sm overflow-x-auto">curl "{{ url('/api/v2/telegram/channels/python/messages/last-id') }}"</div>
                </div>
            </div>

            <!-- Statistics Card -->
            <div class="bg-white border border-gray-200 rounded-xl p-10 shadow-md relative">
                <h2 class="text-2xl font-semibold text-gray-800 mb-3 flex items-center gap-3">🌌 Channel Statistics</h2>
                <p class="text-gray-600 mb-6">Get detailed activity statistics from the last N days</p>
                
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <p class="text-blue-700 text-sm m-0">⚠ Rate limit: 5 requests per hour | ✓ Cache: 1 hour TTL | ✓ Analyzes up to 15 days</p>
                </div>
                
                <div class="bg-gray-800 text-gray-200 p-4 rounded-lg font-mono text-sm overflow-x-auto mb-4">GET /api/v2/telegram/channels/{channel}/statistics/{days}</div>
                
                <div class="flex gap-4 mb-4 items-end">
                    <div class="flex-[2_2_0%] flex flex-col gap-2">
                        <label class="text-sm text-gray-600">Channel username</label>
                        <input type="text" id="statsChannelInput" placeholder="e.g., python" value="orangeterapy" class="w-full px-4 py-3 border border-gray-200 rounded-lg text-base focus:outline-none focus:border-blue-500 transition-colors">
                    </div>
                    <div class="flex-1 flex flex-col gap-2">
                        <label class="text-sm text-gray-600">Days</label>
                        <input type="number" id="statsDaysInput" placeholder="7" value="7" min="1" max="15" class="w-full px-4 py-3 border border-gray-200 rounded-lg text-base focus:outline-none focus:border-blue-500 transition-colors">
                    </div>
                    <button onclick="getChannelStats()" class="px-6 py-3 bg-blue-500 text-white rounded-lg font-medium hover:bg-blue-600 transform hover:-translate-y-px transition-all whitespace-nowrap">Try it</button>
                </div>
                
                <div class="mt-4 text-right">
                    <a href="#" onclick="viewVisualStats(); return false;" class="text-green-600 no-underline font-medium text-sm hover:text-green-700">📊 View Visual Statistics →</a>
                </div>
                
                <div id="statsResult" class="mt-6 p-6 rounded-xl font-mono text-sm overflow-x-auto hidden whitespace-pre leading-relaxed"></div>
                
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 mt-8">
                    <h3 class="text-gray-800 text-lg font-semibold mb-4">🚀 Quick Start</h3>
                    <p class="text-gray-600 text-sm leading-relaxed mb-2">Try with cURL:</p>
                    <div class="bg-gray-800 text-gray-200 p-4 rounded-lg font-mono text-sm overflow-x-auto">curl "{{ url('/api/v2/telegram/channels/python/statistics/7') }}"</div>
                </div>
            </div>

            <!-- Channel Comparison Card -->
            <div class="bg-white border border-gray-200 rounded-xl p-10 shadow-md relative">
                <div class="absolute top-4 right-4">
                    <span class="bg-purple-100 text-purple-800 text-xs font-semibold px-3 py-1 rounded-full">
                        🧪 BETA
                    </span>
                </div>
                
                <h2 class="text-2xl font-semibold text-gray-800 mb-3 flex items-center gap-3">📊 Channel Comparison</h2>
                <p class="text-gray-600 mb-6">Compare statistics between multiple channels side by side</p>
                
                <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 mb-6">
                    <p class="text-purple-700 text-sm font-medium mb-2">⚠️ Experimental Feature</p>
                    <p class="text-purple-600 text-xs">This API endpoint is in beta and may change without prior notice. No deprecation warnings will be provided for breaking changes.</p>
                </div>
                
                <div class="bg-gray-800 text-gray-200 p-4 rounded-lg font-mono text-sm overflow-x-auto mb-4">POST /api/v2/telegram/channels/compare</div>
                
                <p class="text-gray-600 text-sm mb-4">Compare up to 5 channels simultaneously:</p>
                
                <div class="space-y-4 mb-6">
                    <div>
                        <label class="text-sm text-gray-600 mb-2 block">Channels to compare (comma separated)</label>
                        <input type="text" id="compareChannelsInput" placeholder="e.g., nuevomeneame, python, javascript" value="nuevomeneame, orangeterapy" 
                               class="w-full px-4 py-3 border border-gray-200 rounded-lg text-base focus:outline-none focus:border-purple-500 transition-colors">
                    </div>
                    <div class="flex gap-4 items-end">
                        <div class="flex-1">
                            <label class="text-sm text-gray-600 mb-2 block">Time period</label>
                            <select id="compareDaysInput" class="w-full px-4 py-3 border border-gray-200 rounded-lg text-base focus:outline-none focus:border-purple-500 transition-colors">
                                <option value="1">Last 24 hours</option>
                                <option value="3">Last 3 days</option>
                                <option value="7" selected>Last 7 days (max)</option>
                            </select>
                        </div>
                        <button onclick="compareChannels()" class="px-6 py-3 bg-purple-500 text-white rounded-lg font-medium hover:bg-purple-600 transform hover:-translate-y-px transition-all whitespace-nowrap">Try it</button>
                    </div>
                </div>
                
                <div class="mt-4 text-right">
                    <a href="#" onclick="viewVisualComparison(); return false;" class="inline-flex items-center gap-2 text-purple-600 hover:text-purple-800 font-medium text-sm">
                        View Visual Comparison →
                    </a>
                </div>
                
                <div id="compareResult" class="mt-6 p-6 rounded-xl font-mono text-sm overflow-x-auto hidden whitespace-pre leading-relaxed"></div>
                
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 mt-8">
                    <h3 class="text-gray-800 text-lg font-semibold mb-4">🚀 Quick Start</h3>
                    <p class="text-gray-600 text-sm leading-relaxed mb-2">Try with cURL:</p>
                    <div class="bg-gray-800 text-gray-200 p-4 rounded-lg font-mono text-sm overflow-x-auto">curl -X POST "{{ url('/api/v2/telegram/channels/compare') }}" \
  -H "Content-Type: application/json" \
  -d '{"channels": ["nuevomeneame", "python"], "days": 7}'</div>
                </div>
            </div>
        </div>
        
        <!-- Deprecated v1 API Notice -->
        <div class="bg-yellow-100 border border-yellow-400 rounded-xl p-6 mt-12 mb-8 max-w-3xl mx-auto">
            <h3 class="text-yellow-900 text-lg font-semibold mb-4">⚠️ Legacy v1 API (Deprecated)</h3>
            <p class="text-yellow-800 text-sm mb-4">
                The v1 API is still available but deprecated. Please migrate to v2 for better features and JSON:API compliance.
            </p>
            <div class="bg-white border border-yellow-300 rounded-lg p-4 mt-4">
                <p class="text-xs text-yellow-800 mb-2"><strong>v1 Endpoint (deprecated):</strong></p>
                <code class="block bg-yellow-50 p-2 rounded text-xs text-yellow-900 mb-3 font-mono">
                    GET {{ url('/api/telegram/last-message?channel={channel}') }}
                </code>
                <p class="text-xs text-yellow-800 m-0">
                    Returns: <code class="bg-yellow-100 px-1 py-0.5 rounded text-xs">{"success": true, "last_message_id": 12345}</code>
                </p>
            </div>
        </div>
@endsection

@push('scripts')
<script>
        const API_BASE = '/api';
        
        function showResult(elementId, data, isError = false) {
            const element = document.getElementById(elementId);
            element.style.display = 'block';
            
            // Apply Tailwind classes based on error state
            if (isError) {
                element.className = 'mt-6 p-6 rounded-xl font-mono text-sm overflow-x-auto whitespace-pre leading-relaxed bg-red-50 border border-red-200 text-red-700';
            } else {
                element.className = 'mt-6 p-6 rounded-xl font-mono text-sm overflow-x-auto whitespace-pre leading-relaxed bg-gray-800 border border-gray-700 text-gray-200';
            }
            
            // Check if this is a deprecated v1 response
            if (!isError && data.success === true && !data.jsonapi) {
                // Add deprecation notice for v1 responses
                const deprecationNotice = {
                    "⚠️ DEPRECATION WARNING": "You are using the deprecated v1 API. Please migrate to v2.",
                    ...data
                };
                element.innerHTML = formatJSONWithColors(deprecationNotice);
            } else if (isError && data.errors && data.errors[0] && data.errors[0].detail && data.errors[0].status === '401') {
                // Special handling for authentication errors with HTML links
                const error = data.errors[0];
                element.innerHTML = `<div class="space-y-3">
                    <div class="font-bold text-red-800 text-base">${error.title}</div>
                    <div class="text-sm">${error.detail}</div>
                    ${error.meta ? '<div class="text-xs mt-2 opacity-75">' + error.meta.help + '</div>' : ''}
                    ${error.links && error.links.about ? '<div class="mt-3 text-xs">API Link: <a href="' + error.links.about + '" class="underline hover:text-red-600">' + error.links.about + '</a></div>' : ''}
                </div>`;
            } else {
                element.innerHTML = formatJSONWithColors(data);
            }
        }
        
        function formatJSONWithColors(data) {
            const json = JSON.stringify(data, null, 2);
            
            // Escape HTML first to prevent injection
            const escaped = json
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');
            
            // Apply colors to the escaped content
            const colored = escaped
                // Property names in quotes
                .replace(/"([^"]+)":/g, '<span style="color: #60a5fa">"$1"</span>:')
                // String values in quotes (must be before numbers to avoid conflicts)
                .replace(/:\s*"([^"]*)"/g, ': <span style="color: #34d399">"$1"</span>')
                // Numbers (including decimals) - but NOT inside strings
                .replace(/:\s*(-?\d+\.?\d*)(?=\s*[,\}])/g, ': <span style="color: #f87171">$1</span>')
                // Booleans
                .replace(/:\s*(true|false)(?=\s*[,\}])/g, ': <span style="color: #c084fc">$1</span>')
                // Null values
                .replace(/:\s*null(?=\s*[,\}])/g, ': <span style="color: #9ca3af">null</span>')
                // Array and object brackets
                .replace(/([[{])/g, '<span style="color: #94a3b8">$1</span>')
                .replace(/([}\]])/g, '<span style="color: #94a3b8">$1</span>');
            
            return colored;
        }
        
        
        
        function showLoading(elementId) {
            const element = document.getElementById(elementId);
            element.style.display = 'block';
            element.className = 'mt-6 p-6 rounded-xl font-mono text-sm overflow-x-auto whitespace-pre leading-relaxed bg-blue-50 border border-blue-200 text-blue-700';
            element.textContent = 'Loading...';
        }
        
        async function getLastMessage() {
            const channel = document.getElementById('channelInput').value.trim();
            if (!channel) {
                alert('Please enter a channel username');
                return;
            }
            
            showLoading('messageResult');
            
            try {
                const response = await fetch(`${API_BASE}/v2/telegram/channels/${encodeURIComponent(channel)}/messages/last-id`);
                
                // Check if response is JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    // If we got HTML, it means MadelineProto needs authentication
                    showResult('messageResult', {
                        jsonapi: { version: '1.1' },
                        errors: [{
                            status: '401',
                            title: 'Authentication Required',
                            detail: '🔐 The bot has been disconnected from Telegram. Please contact the administrator to re-authenticate the bot.'
                        }],
                        meta: {
                            timestamp: new Date().toISOString(),
                            api_version: 'v2'
                        }
                    }, true);
                    return;
                }
                
                const data = await response.json();
                if (data.errors) {
                    showResult('messageResult', data, true);
                } else {
                    // Show formatted JSON response directly
                    showResult('messageResult', data, false);
                }
            } catch (error) {
                // Check if it's a JSON parse error
                if (error.message.includes('JSON')) {
                    showResult('messageResult', {
                        jsonapi: { version: '1.1' },
                        errors: [{
                            status: '401',
                            title: 'Authentication Required',
                            detail: '🔐 The bot has been disconnected from Telegram. Please contact the administrator to re-authenticate the bot.'
                        }],
                        meta: {
                            timestamp: new Date().toISOString(),
                            api_version: 'v2'
                        }
                    }, true);
                } else {
                    showResult('messageResult', { error: error.message }, true);
                }
            }
        }
        
        
        function formatStatsHTML(data) {
            if (!data.statistics) return '';
            
            const stats = data.statistics;
            const summary = stats.summary;
            
            // Check if there's no data
            if (summary.total_messages === 0) {
                return `
                    <div class="stats-section" style="text-align: center; padding: 2rem;">
                        <h4>📊 No Activity Found</h4>
                        <p style="color: #64748b; margin-top: 1rem;">
                            No messages found in the last ${data.period_days} days for channel @${data.channel}
                        </p>
                        <p style="color: #94a3b8; font-size: 0.875rem; margin-top: 0.5rem;">
                            Try increasing the number of days or check a different channel
                        </p>
                    </div>
                `;
            }
            
            let html = `
                <div class="stats-section" style="margin-top: 1rem;">
                    <h4 style="margin: 1rem 0 0.75rem 0; font-size: 0.9375rem;">📈 Summary</h4>
                    <div class="stats-grid">
                        <div class="stat-box">
                            <div class="value">${summary.total_messages.toLocaleString()}</div>
                            <div class="label">Total Messages</div>
                        </div>
                        <div class="stat-box">
                            <div class="value">${summary.unique_users}</div>
                            <div class="label">Active Users</div>
                        </div>
                        <div class="stat-box">
                            <div class="value">${summary.reply_rate}%</div>
                            <div class="label">Reply Rate</div>
                        </div>
                        <div class="stat-box">
                            <div class="value">${Math.round(summary.average_message_length)}</div>
                            <div class="label">Avg. Length</div>
                        </div>
                    </div>
                </div>
            `;
            
            if (stats.top_users && stats.top_users.length > 0) {
                html += `
                    <div class="stats-section" style="margin-top: 1.5rem;">
                        <h4 style="margin: 1.5rem 0 0.75rem 0; font-size: 0.9375rem;">👥 Top Active Users</h4>
                        <div style="margin-bottom: 0.5rem; font-size: 0.75rem; color: #64748b;">
                            <div class="user-row" style="font-weight: 600;">
                                <div>User ID</div>
                                <div style="text-align: center;">Messages</div>
                                <div style="text-align: center;">Avg. Length</div>
                                <div style="text-align: center;">Replies</div>
                            </div>
                        </div>
                `;
                
                stats.top_users.forEach(user => {
                    const displayName = user.user_name || `ID: ${user.user_id}`;
                    html += `
                        <div class="user-row">
                            <div class="user-id" title="ID: ${user.user_id}">${displayName}</div>
                            <div style="text-align: center;">${user.message_count}</div>
                            <div style="text-align: center;">${user.average_message_length}</div>
                            <div style="text-align: center;">${user.reply_count}</div>
                        </div>
                    `;
                });
                
                html += `</div>`;
            }
            
            // Activity by hour chart
            if (stats.activity_patterns && stats.activity_patterns.by_hour) {
                const hourData = stats.activity_patterns.by_hour;
                const maxHourValue = Math.max(...Object.values(hourData));
                
                html += `
                    <div class="stats-section" style="margin-top: 1.5rem;">
                        <h4 style="margin: 1.5rem 0 0.75rem 0; font-size: 0.9375rem;">⏰ Activity by Hour (UTC)</h4>
                        <div class="activity-chart">
                `;
                
                Object.entries(hourData).forEach(([hour, count]) => {
                    const height = maxHourValue > 0 ? (count / maxHourValue) * 100 : 0;
                    html += `<div class="activity-bar" style="height: ${height}%;" data-tooltip="${hour}: ${count} messages"></div>`;
                });
                
                html += `
                        </div>
                        <div style="font-size: 0.75rem; color: #64748b; text-align: center;">
                            Peak hour: ${stats.peak_activity.hour} | Peak day: ${stats.peak_activity.weekday}
                        </div>
                    </div>
                `;
            }
            
            return html;
        }
        
        // Removed formatStatsWithJSON - now using showResult directly
        
        
        async function getChannelStats() {
            const channel = document.getElementById('statsChannelInput').value.trim();
            const days = document.getElementById('statsDaysInput').value || 7;
            
            if (!channel) {
                alert('Please enter a channel username');
                return;
            }
            
            showLoading('statsResult');
            
            try {
                const response = await fetch(`${API_BASE}/v2/telegram/channels/${encodeURIComponent(channel)}/statistics/${days}`);
                
                // Check if response is JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    // If we got HTML, it means MadelineProto needs authentication
                    showResult('statsResult', {
                        jsonapi: { version: '1.1' },
                        errors: [{
                            status: '401',
                            title: 'Authentication Required',
                            detail: '🔐 The bot has been disconnected from Telegram. Please contact the administrator to re-authenticate the bot.'
                        }],
                        meta: {
                            timestamp: new Date().toISOString(),
                            api_version: 'v2'
                        }
                    }, true);
                    return;
                }
                
                const data = await response.json();
                
                if (data.errors) {
                    showResult('statsResult', data, true);
                } else {
                    // Show formatted JSON response directly, same as Last Message ID
                    showResult('statsResult', data, false);
                }
            } catch (error) {
                // Check if it's a JSON parse error
                if (error.message.includes('JSON')) {
                    showResult('statsResult', {
                        jsonapi: { version: '1.1' },
                        errors: [{
                            status: '401',
                            title: 'Authentication Required',
                            detail: '🔐 The bot has been disconnected from Telegram. Please contact the administrator to re-authenticate the bot.'
                        }],
                        meta: {
                            timestamp: new Date().toISOString(),
                            api_version: 'v2'
                        }
                    }, true);
                } else {
                    showResult('statsResult', { error: error.message }, true);
                }
            }
        }
        
        // Enter key support
        document.getElementById('channelInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') getLastMessage();
        });
        
        document.getElementById('statsChannelInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') getChannelStats();
        });
        
        document.getElementById('statsDaysInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') getChannelStats();
        });
        
        // View visual stats function
        function viewVisualStats() {
            const channel = document.getElementById('statsChannelInput').value.trim();
            const days = document.getElementById('statsDaysInput').value || 7;
            
            if (!channel) {
                alert('Please enter a channel username');
                return;
            }
            
            // Navigate to visual stats page
            window.location.href = `/statistics/${encodeURIComponent(channel)}/${days}`;
        }
        
        async function compareChannels() {
            const channelsInput = document.getElementById('compareChannelsInput').value.trim();
            const days = document.getElementById('compareDaysInput').value;
            
            if (!channelsInput) {
                alert('Please enter at least 2 channel usernames');
                return;
            }
            
            // Split by comma and clean up
            const channels = channelsInput.split(',').map(c => c.trim()).filter(c => c.length > 0);
            
            if (channels.length < 2) {
                alert('Please enter at least 2 channels to compare');
                return;
            }
            
            if (channels.length > 4) {
                alert('Maximum 4 channels allowed');
                return;
            }
            
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
                alert('Please enter at least 2 channel usernames');
                return;
            }
            
            // Split by comma and clean up
            const channels = channelsInput.split(',').map(c => c.trim()).filter(c => c.length > 0);
            
            if (channels.length < 2) {
                alert('Please enter at least 2 channels to compare');
                return;
            }
            
            if (channels.length > 4) {
                alert('Maximum 4 channels allowed');
                return;
            }
            
            // Build URL with query parameters
            const params = new URLSearchParams();
            channels.forEach(channel => params.append('channels[]', channel));
            params.append('days', days);
            
            // Navigate to comparison page with pre-filled channels
            window.location.href = `/compare?${params.toString()}`;
        }
</script>
@endpush