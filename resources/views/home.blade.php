<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>Telegram Channel API - Get Channel Data & Statistics</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    
    <!-- Tailwind CSS for footer -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            corePlugins: {
                preflight: false,
            }
        }
    </script>
    
    <!-- Styles -->
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Figtree', ui-sans-serif, system-ui, sans-serif;
            background: #f8fafc;
            color: #334155;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            width: 100%;
        }
        
        header {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            padding: 1.5rem 0;
            margin-bottom: 3rem;
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: 600;
            color: #0ea5e9;
            text-decoration: none;
        }
        
        main {
            flex: 1;
        }
        
        .hero {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 1rem;
        }
        
        .subtitle {
            font-size: 1.125rem;
            color: #64748b;
        }
        
        .endpoints-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 2.5rem;
            box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1);
            width: 100%;
            max-width: 100%;
            position: relative;
        }
        
        .card:not(:last-child)::after {
            content: '';
            position: absolute;
            bottom: -1.25rem;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 2px;
            background: #e2e8f0;
        }
        
        .card h2 {
            font-size: 1.75rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .card .description {
            color: #64748b;
            font-size: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .input-group {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            align-items: flex-end;
        }
        
        .input-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .input-label {
            font-size: 0.875rem;
            color: #64748b;
        }
        
        input[type="text"], input[type="number"] {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            font-size: 1rem;
            font-family: inherit;
            transition: border-color 0.2s;
        }
        
        input[type="text"]:focus, input[type="number"]:focus {
            outline: none;
            border-color: #0ea5e9;
        }
        
        button {
            padding: 0.75rem 1.5rem;
            background: #0ea5e9;
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
            align-self: flex-end;
        }
        
        button:hover {
            background: #0284c7;
            transform: translateY(-1px);
        }
        
        button:hover[style*="background: #10b981"] {
            background: #059669 !important;
        }
        
        button:active {
            transform: translateY(0);
        }
        
        .result {
            margin-top: 1.5rem;
            padding: 1.5rem;
            border-radius: 0.75rem;
            font-family: ui-monospace, SFMono-Regular, Consolas, monospace;
            font-size: 0.875rem;
            overflow-x: auto;
            display: none;
            white-space: pre;
            line-height: 1.6;
        }
        
        
        .stats-section {
            margin-bottom: 1.25rem;
        }
        
        .stats-section h4 {
            font-size: 1rem;
            font-weight: 600;
            color: #1e293b;
            margin: 1.5rem 0 0.75rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }
        
        .stat-box {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            text-align: center;
            transition: all 0.2s;
        }
        
        .stat-box:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px -1px rgb(0 0 0 / 0.1);
        }
        
        .stat-box .value {
            font-size: 1.25rem;
            font-weight: 600;
            color: #0ea5e9;
            line-height: 1.2;
        }
        
        .stat-box .label {
            font-size: 0.75rem;
            color: #64748b;
            margin-top: 0.25rem;
            font-weight: 500;
        }
        
        .user-row {
            display: grid;
            grid-template-columns: 1fr 60px 80px 60px;
            gap: 0.75rem;
            padding: 0.5rem 0.75rem;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 0.375rem;
            margin-bottom: 0.375rem;
            align-items: center;
            font-size: 0.8125rem;
        }
        
        .user-row:hover {
            background: #f0f9ff;
        }
        
        .user-row .user-id {
            font-weight: 500;
            color: #1e293b;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .activity-chart {
            display: flex;
            gap: 0.25rem;
            align-items: flex-end;
            height: 80px;
            margin-bottom: 0.75rem;
            padding: 0.75rem;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
        }
        
        .activity-bar {
            flex: 1;
            background: #0ea5e9;
            border-radius: 0.375rem 0.375rem 0 0;
            min-height: 4px;
            position: relative;
            transition: all 0.2s;
        }
        
        .activity-bar:hover {
            background: #0284c7;
        }
        
        .activity-bar:hover::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: #1e293b;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            white-space: nowrap;
            margin-bottom: 0.25rem;
        }
        
        .result.success {
            background: #1e293b;
            border: 1px solid #334155;
            color: #e2e8f0;
        }
        
        .result.error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }
        
        .result.loading {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            color: #0369a1;
        }
        
        
        .code-block {
            background: #1e293b;
            color: #e2e8f0;
            padding: 1rem;
            border-radius: 0.5rem;
            font-family: ui-monospace, SFMono-Regular, Consolas, monospace;
            font-size: 0.875rem;
            overflow-x: auto;
            margin-bottom: 1rem;
            word-break: break-all;
            white-space: pre-wrap;
        }
        
        @media (min-width: 1024px) {
            .code-block {
                word-break: normal;
                white-space: pre;
            }
        }
        
        .info-box {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .info-box p {
            color: #0369a1;
            font-size: 0.875rem;
            margin: 0;
        }
        
        .quick-start {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-top: 2rem;
        }
        
        .quick-start h3 {
            color: #1e293b;
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .quick-start p {
            color: #64748b;
            font-size: 0.875rem;
            line-height: 1.5;
            margin-bottom: 0.5rem;
        }
        
        footer {
            margin-top: 4rem;
            padding: 2rem 0;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            color: #64748b;
            font-size: 0.875rem;
        }
        
        footer a {
            color: #0ea5e9;
            text-decoration: none;
        }
        
        footer a:hover {
            text-decoration: underline;
        }
        
        .stats-summary {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .stat-card {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 0.5rem;
            padding: 1rem;
            text-align: center;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: 600;
            color: #0369a1;
        }
        
        .stat-label {
            font-size: 0.75rem;
            color: #64748b;
            margin-top: 0.25rem;
        }
        
        @media (max-width: 1024px) {
            .endpoints-container {
                grid-template-columns: 1fr;
            }
        }
        
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.5;
            }
        }
        
        @media (max-width: 768px) {
            .card {
                padding: 1.5rem;
            }
            
            .input-group {
                flex-direction: column;
                gap: 0.75rem;
            }
            
            .input-wrapper {
                width: 100%;
            }
            
            button {
                width: 100%;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.75rem;
            }
            
            .user-row {
                font-size: 0.75rem;
                grid-template-columns: 1fr 60px 80px 60px;
                gap: 0.5rem;
                padding: 0.5rem;
            }
            
            .activity-chart {
                height: 80px;
                padding: 0.75rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span class="logo">🛸 Telegram Channel API</span>
                <nav style="display: flex; gap: 1.5rem;">
                    <a href="/" style="color: #1e293b; text-decoration: none; font-weight: 600;">Home</a>
                    <a href="{{ route('changelog') }}" style="color: #0ea5e9; text-decoration: none; font-weight: 500;">Changelog</a>
                    <a href="{{ route('architecture') }}" style="color: #0ea5e9; text-decoration: none; font-weight: 500;">Architecture</a>
                </nav>
            </div>
        </div>
    </header>

    <main class="container">
        <div class="hero">
            <p class="subtitle" style="font-size: 1.5rem; margin-bottom: 0.75rem;">
                Get real-time data and statistics from public Telegram channels
            </p>
            <p style="font-size: 1rem; color: #94a3b8; margin-bottom: 2.5rem;">
                An unofficial API for retrieving public channel data
            </p>
            
            <div style="background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border: 1px solid #bae6fd; border-radius: 1rem; padding: 1.5rem; max-width: 600px; margin: 0 auto; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);">
                <div style="display: flex; items-center; justify-content: center; gap: 0.5rem; margin-bottom: 1rem;">
                    <span style="display: inline-block; width: 8px; height: 8px; background: #22c55e; border-radius: 50%; animation: pulse 2s infinite;"></span>
                    <span style="font-size: 0.875rem; font-weight: 600; color: #0369a1;">Available Endpoints</span>
                </div>
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <code style="display: block; background: white; padding: 0.75rem 1rem; border-radius: 0.5rem; font-size: 0.8125rem; color: #1e293b; font-family: ui-monospace, monospace; border: 1px solid #e2e8f0;">
                        GET /api/v2/telegram/channels/{channel}/messages/last-id
                    </code>
                    <code style="display: block; background: white; padding: 0.75rem 1rem; border-radius: 0.5rem; font-size: 0.8125rem; color: #1e293b; font-family: ui-monospace, monospace; border: 1px solid #e2e8f0;">
                        GET /api/v2/telegram/channels/{channel}/statistics/{days}
                    </code>
                </div>
                <p style="text-align: center; margin-top: 1rem; font-size: 0.875rem; color: #64748b;">
                    Try them out below with interactive examples ↓
                </p>
            </div>
        </div>

        <div class="endpoints-container">
            <!-- Last Message ID Card -->
            <div class="card">
                <h2>🛰️ Last Message ID</h2>
                <p class="description">Retrieve the latest message ID from any public Telegram channel</p>
                
                <div class="info-box">
                    <p>✓ Rate limit: 60 requests per minute | ✓ Cache: 5 minutes TTL (auto-expires)</p>
                </div>
                
                <div class="code-block">GET /api/v2/telegram/channels/{channel}/messages/last-id</div>
                
                <div class="input-group">
                    <div class="input-wrapper">
                        <label class="input-label">Channel username</label>
                        <input type="text" id="channelInput" placeholder="e.g., python" value="orangeterapy">
                    </div>
                    <button onclick="getLastMessage()">Try it</button>
                </div>
                
                <div style="margin-top: 1rem; text-align: right; height: 1.5rem;">
                    <!-- Placeholder space to align with statistics card -->
                </div>
                
                <div id="messageResult" class="result"></div>
                
                <div class="quick-start">
                    <h3>🚀 Quick Start</h3>
                    <p>Try with cURL:</p>
                    <div class="code-block">curl "{{ url('/api/v2/telegram/channels/python/messages/last-id') }}"</div>
                </div>
            </div>

            <!-- Statistics Card -->
            <div class="card">
                <h2>🌌 Channel Statistics</h2>
                <p class="description">Get detailed activity statistics from the last N days</p>
                
                <div class="info-box">
                    <p>⚠ Rate limit: 5 requests per hour | ✓ Cache: 1 hour TTL | ✓ Analyzes up to 15 days</p>
                </div>
                
                <div class="code-block">GET /api/v2/telegram/channels/{channel}/statistics/{days}</div>
                
                <div class="input-group">
                    <div class="input-wrapper" style="flex: 2;">
                        <label class="input-label">Channel username</label>
                        <input type="text" id="statsChannelInput" placeholder="e.g., python" value="orangeterapy">
                    </div>
                    <div class="input-wrapper" style="flex: 1;">
                        <label class="input-label">Days</label>
                        <input type="number" id="statsDaysInput" placeholder="7" value="7" min="1" max="15">
                    </div>
                    <button onclick="getChannelStats()">Try it</button>
                </div>
                
                <div style="margin-top: 1rem; text-align: right;">
                    <a href="#" onclick="viewVisualStats(); return false;" style="color: #10b981; text-decoration: none; font-weight: 500; font-size: 0.875rem;">📊 View Visual Statistics →</a>
                </div>
                
                <div id="statsResult" class="result"></div>
                
                <div class="quick-start">
                    <h3>🚀 Quick Start</h3>
                    <p>Try with cURL:</p>
                    <div class="code-block">curl "{{ url('/api/v2/telegram/channels/python/statistics/7') }}"</div>
                </div>
            </div>
        </div>
        
        <!-- Deprecated v1 API Notice -->
        <div style="background: #fef3c7; border: 1px solid #fcd34d; border-radius: 0.75rem; padding: 1.5rem; margin: 3rem auto 2rem; max-width: 800px;">
            <h3 style="color: #92400e; font-size: 1.125rem; font-weight: 600; margin-bottom: 1rem;">⚠️ Legacy v1 API (Deprecated)</h3>
            <p style="color: #78350f; font-size: 0.875rem; margin-bottom: 1rem;">
                The v1 API is still available but deprecated. Please migrate to v2 for better features and JSON:API compliance.
            </p>
            <div style="background: white; border: 1px solid #fbbf24; border-radius: 0.5rem; padding: 1rem; margin-top: 1rem;">
                <p style="font-size: 0.8125rem; color: #78350f; margin-bottom: 0.5rem;"><strong>v1 Endpoint (deprecated):</strong></p>
                <code style="display: block; background: #fffbeb; padding: 0.5rem; border-radius: 0.25rem; font-size: 0.8125rem; color: #451a03; margin-bottom: 0.75rem;">
                    GET {{ url('/api/telegram/last-message?channel={channel}') }}
                </code>
                <p style="font-size: 0.75rem; color: #92400e; margin: 0;">
                    Returns: <code style="background: #fef3c7; padding: 0.125rem 0.25rem; border-radius: 0.125rem;">{"success": true, "last_message_id": 12345}</code>
                </p>
            </div>
        </div>
    </main>

    @include('partials.footer')

    <script>
        const API_BASE = '/api';
        
        function showResult(elementId, data, isError = false) {
            const element = document.getElementById(elementId);
            element.style.display = 'block';
            element.className = `result ${isError ? 'error' : 'success'}`;
            
            // Check if this is a deprecated v1 response
            if (!isError && data.success === true && !data.jsonapi) {
                // Add deprecation notice for v1 responses
                const deprecationNotice = {
                    "⚠️ DEPRECATION WARNING": "You are using the deprecated v1 API. Please migrate to v2.",
                    ...data
                };
                element.innerHTML = formatJSONWithColors(deprecationNotice);
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
            element.className = 'result loading';
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
                const data = await response.json();
                if (data.errors) {
                    showResult('messageResult', data, true);
                } else {
                    // Show formatted JSON response directly
                    showResult('messageResult', data, false);
                }
            } catch (error) {
                showResult('messageResult', { error: error.message }, true);
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
                const data = await response.json();
                
                if (data.error) {
                    showResult('statsResult', data, true);
                } else {
                    // Show formatted JSON response directly, same as Last Message ID
                    showResult('statsResult', data, false);
                }
            } catch (error) {
                showResult('statsResult', { error: error.message }, true);
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
    </script>
</body>
</html>