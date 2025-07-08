<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>Telegram Channel API - Get Last Message ID</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    
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
            max-width: 800px;
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
        
        .card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 2rem;
            box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1);
            max-width: 600px;
            margin: 0 auto 2rem;
        }
        
        .input-group {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        input[type="text"] {
            flex: 1;
            padding: 0.75rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            font-size: 1rem;
            font-family: inherit;
            transition: border-color 0.2s;
        }
        
        input[type="text"]:focus {
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
        }
        
        button:hover {
            background: #0284c7;
            transform: translateY(-1px);
        }
        
        button:active {
            transform: translateY(0);
        }
        
        .result {
            margin-top: 1rem;
            padding: 1rem;
            border-radius: 0.5rem;
            font-family: ui-monospace, SFMono-Regular, Consolas, monospace;
            font-size: 0.875rem;
            white-space: pre-wrap;
            word-break: break-all;
            display: none;
        }
        
        .result.success {
            background: #f0fdf4;
            border: 1px solid #86efac;
            color: #166534;
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
        
        .cache-info {
            margin-top: 0.5rem;
            padding: 0.5rem;
            background: #fef3c7;
            border: 1px solid #fcd34d;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            color: #92400e;
            display: none;
        }
        
        .cache-info.fresh {
            background: #dbeafe;
            border-color: #60a5fa;
            color: #1e40af;
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
    </style>
</head>
<body>
    <header>
        <div class="container">
            <span class="logo">📡 Telegram Channel API</span>
        </div>
    </header>

    <main class="container">
        <div class="hero">
            <h1>Get Last Message ID</h1>
            <p class="subtitle">Retrieve the latest message ID from any public Telegram channel</p>
        </div>

        <div class="card">
            <div class="info-box">
                <p>✅ Rate limit: 60 requests per minute | ✅ Cache: 5 minutes TTL</p>
            </div>
            
            <div class="code-block">
                GET {{ url('/api/telegram/last-message?channel={username}') }}
            </div>
            
            <div class="input-group">
                <input type="text" id="channelInput" placeholder="Enter channel username (e.g., python)" value="orangeterapy">
                <button onclick="getLastMessage()">Get ID</button>
            </div>
            
            <div id="result" class="result"></div>
            <div id="cacheInfo" class="cache-info"></div>
            
            <div class="quick-start">
                <h3>🚀 Quick Start</h3>
                <p>Try with cURL:</p>
                <div class="code-block">
curl "{{ url('/api/telegram/last-message?channel=python') }}"</div>
                <p>Popular channels to test: <strong>python</strong>, <strong>laravel</strong>, <strong>nodejs</strong>, <strong>javascript</strong></p>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>
                Built with Laravel {{ app()->version() }} and MadelineProto | 
                <a href="https://my.telegram.org" target="_blank">Get your API credentials</a>
            </p>
        </div>
    </footer>

    <script>
        const API_BASE = '/api/telegram';
        
        function showResult(data, isError = false) {
            const element = document.getElementById('result');
            element.style.display = 'block';
            element.className = `result ${isError ? 'error' : 'success'}`;
            
            // Pretty format the response, but highlight cache info
            let displayData = {...data};
            element.textContent = JSON.stringify(displayData, null, 2);
            
            // Show cache info if successful
            if (!isError && data.from_cache !== undefined) {
                showCacheInfo(data.from_cache, data.cache_age_seconds);
            }
        }
        
        function showCacheInfo(fromCache, cacheAge) {
            const cacheElement = document.getElementById('cacheInfo');
            cacheElement.style.display = 'block';
            
            if (fromCache) {
                cacheElement.className = 'cache-info';
                cacheElement.textContent = `⚡ Served from cache (${cacheAge} seconds old)`;
            } else {
                cacheElement.className = 'cache-info fresh';
                cacheElement.textContent = '🔄 Fresh data fetched from Telegram';
            }
        }
        
        function showLoading() {
            const element = document.getElementById('result');
            element.style.display = 'block';
            element.className = 'result loading';
            element.textContent = 'Loading...';
            
            // Hide cache info while loading
            document.getElementById('cacheInfo').style.display = 'none';
        }
        
        async function getLastMessage() {
            const channel = document.getElementById('channelInput').value.trim();
            if (!channel) {
                alert('Please enter a channel username');
                return;
            }
            
            showLoading();
            
            try {
                const response = await fetch(`${API_BASE}/last-message?channel=${encodeURIComponent(channel)}`);
                const data = await response.json();
                showResult(data, !data.success);
            } catch (error) {
                showResult({ error: error.message }, true);
            }
        }
        
        // Enter key support
        document.getElementById('channelInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') getLastMessage();
        });
    </script>
</body>
</html>