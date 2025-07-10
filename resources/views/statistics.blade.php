<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Channel Statistics - {{ $channel ?? 'Telegram' }}</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        body {
            font-family: 'Figtree', ui-sans-serif, system-ui, sans-serif;
            background: #f0f4f8;
            color: #334155;
            line-height: 1.6;
        }
        
        .loading {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 400px;
        }
        
        .spinner {
            border: 3px solid #f3f4f6;
            border-top: 3px solid #0ea5e9;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    @include('partials.header')

    <main class="container mx-auto px-4 py-8">
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
                        <h2 class="text-2xl sm:text-3xl font-bold" id="channelName"></h2>
                        <p class="text-blue-100 mt-1 text-sm sm:text-base">Statistics for the last <span id="periodDays" class="font-semibold"></span> days</p>
                    </div>
                    <div class="sm:text-right">
                        <p class="text-xs sm:text-sm text-blue-100">Generated at</p>
                        <p class="text-xs sm:text-sm font-medium" id="timestamp"></p>
                    </div>
                </div>
            </div>
            
            <!-- Summary Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-8">
                <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow duration-200 p-3 sm:p-5 border-t-4 border-blue-500">
                    <p class="text-xs font-medium text-gray-600 uppercase tracking-wider mb-1 sm:mb-2">Total Messages</p>
                    <p class="text-2xl sm:text-3xl font-bold text-blue-600" id="totalMessages">0</p>
                </div>
                
                <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow duration-200 p-3 sm:p-5 border-t-4 border-emerald-500">
                    <p class="text-xs font-medium text-gray-600 uppercase tracking-wider mb-1 sm:mb-2">Active Users</p>
                    <p class="text-2xl sm:text-3xl font-bold text-emerald-600" id="activeUsers">0</p>
                </div>
                
                <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow duration-200 p-3 sm:p-5 border-t-4 border-purple-500">
                    <p class="text-xs font-medium text-gray-600 uppercase tracking-wider mb-1 sm:mb-2">Reply Rate</p>
                    <p class="text-2xl sm:text-3xl font-bold text-purple-600" id="replyRate">0%</p>
                </div>
                
                <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow duration-200 p-3 sm:p-5 border-t-4 border-orange-500">
                    <p class="text-xs font-medium text-gray-600 uppercase tracking-wider mb-1 sm:mb-2">Avg. Length</p>
                    <p class="text-2xl sm:text-3xl font-bold text-orange-600" id="avgLength">0</p>
                </div>
            </div>
            
            <!-- Top Users Table -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8" id="topUsersSection">
                <h3 class="text-xl font-bold text-gray-800 mb-4 pb-2 border-b-2 border-gray-200">Top Active Users</h3>
                <!-- Desktop Table -->
                <div class="hidden sm:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rank</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Messages</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Avg. Length</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Replies</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Activity</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="topUsersBody">
                        </tbody>
                    </table>
                </div>
                
                <!-- Mobile Cards -->
                <div class="sm:hidden space-y-3" id="topUsersMobile">
                </div>
            </div>
            
            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Activity by Hour Chart -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-4 pb-2 border-b-2 border-blue-200">Activity by Hour (UTC)</h3>
                    <div style="height: 250px;">
                        <canvas id="hourlyChart"></canvas>
                    </div>
                    <p class="text-sm text-gray-600 mt-3 bg-blue-50 rounded p-2" id="peakHour"></p>
                </div>
                
                <!-- Activity by Day Chart -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-4 pb-2 border-b-2 border-emerald-200">Activity by Day</h3>
                    <div style="height: 250px;">
                        <canvas id="dailyChart"></canvas>
                    </div>
                    <p class="text-sm text-gray-600 mt-3 bg-emerald-50 rounded p-2" id="peakDay"></p>
                </div>
            </div>
        </div>
    </main>
    
    @include('partials.footer')

    <script>
        const channel = '{{ $channel }}';
        const days = {{ $days }};
        
        async function loadStatistics() {
            try {
                const response = await fetch(`/api/v2/telegram/channels/${encodeURIComponent(channel)}/statistics/${days}`);
                const result = await response.json();
                
                if (result.errors) {
                    showError(result.errors[0].detail);
                    return;
                }
                
                if (!result.data || !result.data.attributes || !result.data.attributes.statistics) {
                    showError('No statistics data available');
                    return;
                }
                
                const data = result.data;
                const stats = data.attributes.statistics;
                
                // Hide loading, show content
                document.getElementById('loading').classList.add('hidden');
                document.getElementById('content').classList.remove('hidden');
                
                // Update header
                document.getElementById('channelName').textContent = '@' + data.id;
                document.getElementById('periodDays').textContent = result.meta.period_days;
                document.getElementById('timestamp').textContent = new Date(result.meta.timestamp).toLocaleString();
                
                // Update summary cards
                document.getElementById('totalMessages').textContent = stats.summary.total_messages.toLocaleString();
                document.getElementById('activeUsers').textContent = stats.summary.unique_users.toLocaleString();
                document.getElementById('replyRate').textContent = stats.summary.reply_rate + '%';
                document.getElementById('avgLength').textContent = Math.round(stats.summary.average_message_length).toLocaleString();
                
                // Create charts
                createHourlyChart(stats.activity_patterns.by_hour);
                createDailyChart(stats.activity_patterns.by_weekday);
                
                // Update peak times
                document.getElementById('peakHour').textContent = `Peak hour: ${stats.peak_activity.hour}`;
                document.getElementById('peakDay').textContent = `Peak day: ${stats.peak_activity.weekday}`;
                
                // Populate top users
                populateTopUsers(stats.top_users);
                
            } catch (error) {
                showError('Failed to load statistics: ' + error.message);
            }
        }
        
        function showError(message) {
            document.getElementById('loading').classList.add('hidden');
            document.getElementById('error').classList.remove('hidden');
            document.getElementById('errorMessage').textContent = message;
        }
        
        function createHourlyChart(hourData) {
            const ctx = document.getElementById('hourlyChart').getContext('2d');
            const hours = Object.keys(hourData);
            const values = Object.values(hourData);
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: hours,
                    datasets: [{
                        label: 'Messages',
                        data: values,
                        backgroundColor: 'rgba(14, 165, 233, 0.8)',
                        borderColor: '#0284c7',
                        borderWidth: 1,
                        borderRadius: 4,
                        hoverBackgroundColor: 'rgba(14, 165, 233, 1)'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        }
        
        function createDailyChart(dayData) {
            const ctx = document.getElementById('dailyChart').getContext('2d');
            const days = Object.keys(dayData);
            const values = Object.values(dayData);
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: days,
                    datasets: [{
                        label: 'Messages',
                        data: values,
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderColor: '#10b981',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#10b981',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        }
        
        function populateTopUsers(users) {
            if (!users || users.length === 0) {
                document.getElementById('topUsersSection').classList.add('hidden');
                return;
            }
            
            // Desktop table
            const tbody = document.getElementById('topUsersBody');
            tbody.innerHTML = '';
            
            // Mobile cards
            const mobileContainer = document.getElementById('topUsersMobile');
            mobileContainer.innerHTML = '';
            
            users.forEach((user, index) => {
                // Desktop row
                const row = document.createElement('tr');
                row.className = index % 2 === 0 ? 'bg-white' : 'bg-gray-50';
                
                const displayName = user.user_name || `User ${user.user_id}`;
                const activityBar = createActivityBar(user.message_count, users[0].message_count);
                const rankBadge = index === 0 ? '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">🥇</span>' :
                                index === 1 ? '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">🥈</span>' :
                                index === 2 ? '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">🥉</span>' :
                                `<span class="text-gray-500 font-medium">${index + 1}</span>`;
                
                row.innerHTML = `
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        ${rankBadge}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        ${displayName}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 text-center font-semibold">
                        ${user.message_count}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 text-center">
                        ${user.average_message_length}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 text-center">
                        ${user.reply_count}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        ${activityBar}
                    </td>
                `;
                
                tbody.appendChild(row);
                
                // Mobile card
                const mobileCard = document.createElement('div');
                mobileCard.className = 'bg-white rounded-lg shadow-sm border border-gray-200 p-4';
                mobileCard.innerHTML = `
                    <div class="flex items-start justify-between mb-2">
                        <div class="flex items-center gap-2">
                            ${rankBadge}
                            <span class="font-medium text-gray-900">${displayName}</span>
                        </div>
                        <span class="text-lg font-bold text-gray-800">${user.message_count}</span>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-sm text-gray-600 mb-3">
                        <div>
                            <span class="text-xs text-gray-500">Avg. Length:</span> ${user.average_message_length}
                        </div>
                        <div>
                            <span class="text-xs text-gray-500">Replies:</span> ${user.reply_count}
                        </div>
                    </div>
                    ${activityBar}
                `;
                
                mobileContainer.appendChild(mobileCard);
            });
        }
        
        function createActivityBar(count, maxCount) {
            const percentage = (count / maxCount) * 100;
            const barColor = percentage > 75 ? 'bg-gradient-to-r from-blue-500 to-blue-600' :
                           percentage > 50 ? 'bg-gradient-to-r from-cyan-500 to-blue-500' :
                           percentage > 25 ? 'bg-gradient-to-r from-teal-500 to-cyan-500' :
                           'bg-gradient-to-r from-gray-400 to-teal-500';
            return `
                <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                    <div class="${barColor} h-3 rounded-full transition-all duration-500" style="width: ${percentage}%"></div>
                </div>
            `;
        }
        
        // Load statistics on page load
        loadStatistics();
    </script>
</body>
</html>