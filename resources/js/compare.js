import Chart from 'chart.js/auto';
import ChartDataLabels from 'chartjs-plugin-datalabels';
import html2canvas from 'html2canvas';
import jsPDF from 'jspdf';

// Register the plugin globally
Chart.register(ChartDataLabels);

let keyMetricsChart = null;
let engagementChart = null;
let messagesPerUserChart = null;
let totalUsersChart = null;
let subscribersChart = null;
let engagementRatioChart = null;

// Helper function to get consistent colors for channels
function getChannelColor(index, alpha = 1) {
    const colors = [
        `rgba(59, 130, 246, ${alpha})`,  // Blue
        `rgba(16, 185, 129, ${alpha})`,  // Green
        `rgba(239, 68, 68, ${alpha})`,   // Red
        `rgba(168, 85, 247, ${alpha})`   // Purple
    ];
    return colors[index % colors.length];
}

// Pre-fill form from URL parameters
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const channels = urlParams.getAll('channels[]');
    const days = urlParams.get('days');
    
    if (channels.length > 0) {
        const container = document.getElementById('channelInputs');
        const inputs = container.querySelectorAll('input[name="channels[]"]');
        
        // Fill existing inputs
        channels.slice(0, 2).forEach((channel, index) => {
            if (inputs[index]) {
                inputs[index].value = channel;
            }
        });
        
        // Add extra inputs if needed
        for (let i = 2; i < channels.length && i < 4; i++) {
            document.getElementById('addChannel').click();
            const newInputs = container.querySelectorAll('input[name="channels[]"]');
            if (newInputs[i]) {
                newInputs[i].value = channels[i];
            }
        }
        
        // Set days if provided
        if (days) {
            document.querySelector('select[name="days"]').value = days;
        }
        
        // Auto-submit if we have valid data
        if (channels.length >= 2) {
            // Trigger form submission (the submit handler will show the loading spinner)
            document.getElementById('compareForm').dispatchEvent(new Event('submit'));
        }
    }
});

// Add/Remove channel inputs
document.getElementById('addChannel').addEventListener('click', function() {
    const container = document.getElementById('channelInputs');
    if (container.children.length >= 3) {
        alert('Maximum 4 channels allowed (including the initial 2)');
        return;
    }
    
    const div = document.createElement('div');
    div.className = 'channel-input-group';
    div.innerHTML = `
        <input type="text" 
               name="channels[]" 
               placeholder="Channel username" 
               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
               required>
        <span class="remove-channel">&times;</span>
    `;
    container.appendChild(div);
    
    // Add remove handler
    div.querySelector('.remove-channel').addEventListener('click', function() {
        if (container.children.length > 2) {
            div.remove();
        }
    });
});

// Handle form submission
document.getElementById('compareForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const channels = formData.getAll('channels[]').filter(c => c.trim() !== '');
    const days = formData.get('days');
    
    if (channels.length < 2) {
        showError('Please enter at least 2 channels to compare');
        return;
    }
    
    // Hide previous results and show loading
    document.getElementById('results').classList.add('hidden');
    document.getElementById('error').classList.add('hidden');
    document.getElementById('loading').style.display = 'flex';
    
    try {
        const response = await fetch('/api/v2/telegram/channels/compare', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ channels, days: parseInt(days) })
        });
        
        const result = await response.json();
        
        if (response.ok) {
            displayResults(result);
        } else {
            showError(result.errors?.[0]?.detail || 'Failed to compare channels');
        }
    } catch (error) {
        showError('Network error: ' + error.message);
    } finally {
        document.getElementById('loading').style.display = 'none';
    }
});

function showError(message) {
    document.getElementById('errorMessage').textContent = message;
    document.getElementById('error').classList.remove('hidden');
    document.getElementById('results').classList.add('hidden');
    // Hide loading spinner when showing error
    document.getElementById('loading').classList.add('hidden');
}

function displayResults(result) {
    // Always hide loading spinner first
    document.getElementById('loading').classList.add('hidden');
    
    const data = result.data;
    const summary = data.attributes.summary;
    const comparisons = data.attributes.comparison;
    const errors = data.attributes.errors || [];
    
    // Update summary
    document.getElementById('channelsAnalyzed').textContent = summary.channels_analyzed;
    document.getElementById('totalMessages').textContent = summary.total_messages.toLocaleString();
    document.getElementById('totalUsers').textContent = summary.total_unique_users.toLocaleString();
    document.getElementById('mostActive').textContent = summary.most_active_channel ? '@' + summary.most_active_channel : '-';
    
    // Display channel cards
    const container = document.getElementById('channelComparisons');
    container.innerHTML = '';
    
    comparisons.forEach(channel => {
        const card = createChannelCard(channel);
        container.appendChild(card);
    });
    
    // Display errors if any
    errors.forEach(error => {
        const errorCard = createErrorCard(error);
        container.appendChild(errorCard);
    });
    
    // Create comparison charts
    createComparisonCharts(comparisons);
    createActivityPatterns(comparisons);
    
    // Show results and hide loading
    document.getElementById('results').classList.remove('hidden');
    document.getElementById('loading').classList.add('hidden');
}

function createChannelCard(channel) {
    const div = document.createElement('div');
    div.className = 'comparison-card bg-white rounded-lg shadow-md overflow-hidden';
    
    const replyRateClass = channel.reply_rate > 50 ? 'bg-green-100 text-green-800' : 
                          channel.reply_rate > 25 ? 'bg-yellow-100 text-yellow-800' : 
                          'bg-red-100 text-red-800';
    
    const messagesPerUser = channel.active_users > 0 ? 
        Math.round((channel.total_messages / channel.active_users) * 10) / 10 : 0;
    
    const engagementScore = channel.active_users > 0 ? 
        Math.round((channel.total_messages / channel.active_users) * (channel.reply_rate / 100) * 10) / 10 : 0;
    
    // Calculate engagement percentage if we have subscriber data
    const engagementPercentage = channel.total_participants > 0 ? 
        Math.round((channel.active_users / channel.total_participants) * 100) : null;
    
    div.innerHTML = `
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-500 to-cyan-600 p-4">
            <h3 class="text-xl font-bold text-white">@${channel.channel}</h3>
        </div>
        
        <div class="p-4 space-y-4">
            <!-- Channel Overview (if available) -->
            ${channel.total_participants || channel.approx_total_messages ? `
            <div class="bg-gray-50 rounded-lg p-3">
                <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Channel Overview</h4>
                <div class="grid grid-cols-2 gap-3">
                    ${channel.total_participants ? `
                    <div>
                        <p class="text-2xl font-bold text-blue-600">${channel.total_participants.toLocaleString()}</p>
                        <p class="text-xs text-gray-600">Total Subscribers</p>
                    </div>
                    ` : ''}
                    ${channel.approx_total_messages ? `
                    <div>
                        <p class="text-2xl font-bold text-indigo-600">${channel.approx_total_messages.toLocaleString()}</p>
                        <p class="text-xs text-gray-600">Total Messages</p>
                    </div>
                    ` : ''}
                </div>
                ${engagementPercentage !== null ? `
                <div class="mt-3 pt-3 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-600">Engagement Rate</span>
                        <span class="text-sm font-bold ${engagementPercentage > 10 ? 'text-green-600' : engagementPercentage > 5 ? 'text-yellow-600' : 'text-red-600'}">
                            ${engagementPercentage}%
                        </span>
                    </div>
                    <div class="mt-1 w-full bg-gray-200 rounded-full h-2">
                        <div class="h-2 rounded-full ${engagementPercentage > 10 ? 'bg-green-500' : engagementPercentage > 5 ? 'bg-yellow-500' : 'bg-red-500'}" 
                             style="width: ${Math.min(engagementPercentage, 100)}%"></div>
                    </div>
                </div>
                ` : ''}
            </div>
            ` : ''}
            
            <!-- Period Statistics -->
            <div class="bg-blue-50 rounded-lg p-3">
                <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Last ${channel.period_days} Days</h4>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <p class="text-xl font-bold text-gray-800">${channel.total_messages.toLocaleString()}</p>
                        <p class="text-xs text-gray-600">Messages</p>
                    </div>
                    <div>
                        <p class="text-xl font-bold text-gray-800">${channel.active_users.toLocaleString()}</p>
                        <p class="text-xs text-gray-600">Active Users</p>
                    </div>
                </div>
            </div>
            
            <!-- Engagement Metrics -->
            <div class="space-y-2">
                <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Engagement Metrics</h4>
                
                <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                    <span class="text-sm text-gray-700">Messages per User</span>
                    <span class="text-sm font-bold">${messagesPerUser}</span>
                </div>
                
                <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                    <span class="text-sm text-gray-700">Reply Rate</span>
                    <span class="text-sm font-bold ${replyRateClass} px-2 py-1 rounded">${channel.reply_rate}%</span>
                </div>
                
                <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                    <span class="text-sm text-gray-700">Avg Message Length</span>
                    <span class="text-sm font-bold">${Math.round(channel.average_message_length)} chars</span>
                </div>
                
                <div class="flex items-center justify-between p-2 bg-purple-50 rounded">
                    <span class="text-sm text-gray-700">Engagement Score</span>
                    <span class="text-sm font-bold text-purple-600">${engagementScore} pts</span>
                </div>
            </div>
            
            <!-- Activity Patterns -->
            <div class="pt-3 border-t border-gray-200">
                <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Peak Activity</h4>
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="text-gray-700">${channel.peak_hour}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <span class="text-gray-700">${channel.peak_day}</span>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    return div;
}

function createErrorCard(error) {
    const div = document.createElement('div');
    div.className = 'bg-red-50 border border-red-200 rounded-lg p-6';
    
    div.innerHTML = `
        <h3 class="text-xl font-bold text-red-800 mb-2">@${error.channel}</h3>
        <p class="text-red-700">${error.error}</p>
    `;
    
    return div;
}


function createComparisonCharts(comparisons) {
    const labels = comparisons.map(c => '@' + c.channel);
    
    // Key Metrics Chart
    const ctx1 = document.getElementById('keyMetricsChart').getContext('2d');
    
    if (keyMetricsChart) {
        keyMetricsChart.destroy();
    }
    
    const messages = comparisons.map(c => c.total_messages);
    const users = comparisons.map(c => c.active_users);
    const messagesPerUser = comparisons.map(c => c.active_users > 0 ? Math.round((c.total_messages / c.active_users) * 10) / 10 : 0);
    
    // Destroy old chart and create 3 separate charts with independent scales
    const keyMetricsContainer = document.getElementById('keyMetricsChart').parentElement;
    keyMetricsContainer.innerHTML = `
        <h3 class="text-xl font-bold text-gray-800 mb-4">Total Messages Comparison</h3>
        <canvas id="totalMessagesChart" height="60"></canvas>
        <p class="text-sm text-gray-600 mt-2 mb-6 bg-gray-50 p-3 rounded">📊 Shows the total number of messages sent in each channel during the selected period. Higher bars indicate more active channels.</p>
        
        <h3 class="text-xl font-bold text-gray-800 mb-4 mt-6">Active Users Comparison</h3>
        <canvas id="activeUsersChart" height="60"></canvas>
        <p class="text-sm text-gray-600 mt-2 mb-6 bg-gray-50 p-3 rounded">👥 Number of unique users who sent at least one message. This helps identify channels with active communities.</p>
        
        <h3 class="text-xl font-bold text-gray-800 mb-4 mt-6">Messages per User Comparison</h3>
        <canvas id="messagesPerUserRatioChart" height="60"></canvas>
        <p class="text-sm text-gray-600 mt-2 bg-gray-50 p-3 rounded">💬 Average messages per active user. Higher values suggest more engaged users who participate frequently in discussions.</p>
    `;
    
    // Total Messages Chart
    new Chart(document.getElementById('totalMessagesChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Total Messages',
                data: messages,
                backgroundColor: comparisons.map((_, index) => getChannelColor(index, 0.8)),
                borderColor: comparisons.map((_, index) => getChannelColor(index)),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                datalabels: {
                    anchor: 'end',
                    align: (context) => {
                        const value = context.dataset.data[context.dataIndex];
                        const max = Math.max(...context.dataset.data);
                        return value > max * 0.85 ? 'bottom' : 'top';
                    },
                    formatter: (value) => value.toLocaleString(),
                    font: {
                        weight: 'bold',
                        size: 11
                    },
                    color: (context) => {
                        const value = context.dataset.data[context.dataIndex];
                        const max = Math.max(...context.dataset.data);
                        return value > max * 0.85 ? '#ffffff' : '#374151';
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grace: '10%',
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
    
    // Active Users Chart
    new Chart(document.getElementById('activeUsersChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Active Users',
                data: users,
                backgroundColor: comparisons.map((_, index) => getChannelColor(index, 0.6)),
                borderColor: comparisons.map((_, index) => getChannelColor(index)),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                datalabels: {
                    anchor: 'end',
                    align: (context) => {
                        const value = context.dataset.data[context.dataIndex];
                        const max = Math.max(...context.dataset.data);
                        return value > max * 0.85 ? 'bottom' : 'top';
                    },
                    formatter: (value) => value.toLocaleString(),
                    font: {
                        weight: 'bold',
                        size: 11
                    },
                    color: (context) => {
                        const value = context.dataset.data[context.dataIndex];
                        const max = Math.max(...context.dataset.data);
                        return value > max * 0.85 ? '#ffffff' : '#374151';
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grace: '10%',
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
    
    // Messages per User Ratio Chart
    new Chart(document.getElementById('messagesPerUserRatioChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Messages per User',
                data: messagesPerUser,
                backgroundColor: comparisons.map((_, index) => getChannelColor(index, 0.4)),
                borderColor: comparisons.map((_, index) => getChannelColor(index)),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                datalabels: {
                    anchor: 'end',
                    align: (context) => {
                        const value = context.dataset.data[context.dataIndex];
                        const max = Math.max(...context.dataset.data);
                        return value > max * 0.85 ? 'bottom' : 'top';
                    },
                    formatter: (value) => value.toFixed(1),
                    font: {
                        weight: 'bold',
                        size: 11
                    },
                    color: (context) => {
                        const value = context.dataset.data[context.dataIndex];
                        const max = Math.max(...context.dataset.data);
                        return value > max * 0.85 ? '#ffffff' : '#374151';
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.parsed.y.toFixed(1) + ' messages/user';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grace: '10%'
                }
            }
        }
    });
    
    // Add description for Engagement Chart
    const engagementContainer = document.getElementById('engagementChart').parentElement;
    const engagementDescription = document.createElement('p');
    engagementDescription.className = 'text-sm text-gray-600 mt-2 mb-6 bg-gray-50 p-3 rounded';
    engagementDescription.innerHTML = '📊 Key engagement metrics comparison. Reply rate shows conversation activity, while engagement score combines multiple factors.';
    engagementContainer.appendChild(engagementDescription);
    
    // Engagement Metrics Chart
    const ctx2 = document.getElementById('engagementChart').getContext('2d');
    
    if (engagementChart) {
        engagementChart.destroy();
    }
    
    const replyRates = comparisons.map(c => c.reply_rate);
    const avgLengths = comparisons.map(c => Math.round(c.average_message_length));
    const engagementScores = comparisons.map(c => c.active_users > 0 ? 
        Math.round((c.total_messages / c.active_users) * (c.reply_rate / 100) * 10) / 10 : 0);
    
    engagementChart = new Chart(ctx2, {
        type: 'bar',
        data: {
            labels: ['Reply Rate (%)', 'Avg Message Length', 'Engagement Score'],
            datasets: comparisons.map((channel, index) => ({
                label: '@' + channel.channel,
                data: [
                    replyRates[index],
                    avgLengths[index],
                    engagementScores[index]
                ],
                backgroundColor: getChannelColor(index, 0.8),
                borderColor: getChannelColor(index),
                borderWidth: 1
            }))
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                datalabels: {
                    anchor: 'end',
                    align: 'top',
                    formatter: (value, ctx) => {
                        // Always show percentages and key metrics
                        if (ctx.dataIndex === 0) return value + '%';
                        if (ctx.dataIndex === 2) return value.toFixed(1);
                        return ''; // Don't show avg length as it's less important
                    },
                    font: {
                        weight: 'bold',
                        size: 10
                    },
                    color: (ctx) => ctx.dataset.borderColor
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.dataIndex === 0) {
                                label += context.parsed.y + '%';
                            } else if (context.dataIndex === 1) {
                                label += context.parsed.y + ' chars';
                            } else {
                                label += context.parsed.y + ' points';
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grace: '10%'
                }
            }
        }
    });
    
    // Add descriptions for remaining charts
    const messagesPerUserContainer = document.getElementById('messagesPerUserChart').parentElement;
    const messagesPerUserDesc = document.createElement('p');
    messagesPerUserDesc.className = 'text-sm text-gray-600 mt-2 mb-6 bg-gray-50 p-3 rounded';
    messagesPerUserDesc.innerHTML = '💬 Shows user engagement intensity. Values above 10 indicate highly active participants.';
    messagesPerUserContainer.appendChild(messagesPerUserDesc);
    
    // Messages per User Chart
    const ctx3 = document.getElementById('messagesPerUserChart').getContext('2d');
    
    if (messagesPerUserChart) {
        messagesPerUserChart.destroy();
    }
    
    messagesPerUserChart = new Chart(ctx3, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Messages per User',
                data: messagesPerUser,
                backgroundColor: comparisons.map((_, index) => getChannelColor(index)),
                borderColor: comparisons.map((_, index) => getChannelColor(index)),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                },
                datalabels: {
                    anchor: 'end',
                    align: (context) => {
                        const value = context.dataset.data[context.dataIndex];
                        const max = Math.max(...context.dataset.data);
                        return value > max * 0.85 ? 'bottom' : 'top';
                    },
                    formatter: (value) => value.toFixed(1),
                    font: {
                        weight: 'bold',
                        size: 11
                    },
                    color: (context) => {
                        const value = context.dataset.data[context.dataIndex];
                        const max = Math.max(...context.dataset.data);
                        return value > max * 0.85 ? '#ffffff' : '#374151';
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.parsed.y + ' messages per user';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grace: '10%',
                    title: {
                        display: true,
                        text: 'Average Messages'
                    }
                }
            }
        }
    });
    
    // Add description for Total Users
    const totalUsersContainer = document.getElementById('totalUsersChart').parentElement;
    const totalUsersDesc = document.createElement('p');
    totalUsersDesc.className = 'text-sm text-gray-600 mt-2 mb-6 bg-gray-50 p-3 rounded';
    totalUsersDesc.innerHTML = '👥 Direct comparison of unique active users. Useful for understanding community size differences.';
    totalUsersContainer.appendChild(totalUsersDesc);
    
    // Total Users Chart
    const ctx4 = document.getElementById('totalUsersChart').getContext('2d');
    
    if (totalUsersChart) {
        totalUsersChart.destroy();
    }
    
    totalUsersChart = new Chart(ctx4, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Active Users',
                data: users,
                backgroundColor: comparisons.map((_, index) => getChannelColor(index, 0.8)),
                borderColor: comparisons.map((_, index) => getChannelColor(index)),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                },
                datalabels: {
                    anchor: 'end',
                    align: (context) => {
                        const value = context.dataset.data[context.dataIndex];
                        const max = Math.max(...context.dataset.data);
                        return value > max * 0.85 ? 'bottom' : 'top';
                    },
                    formatter: (value) => value.toLocaleString(),
                    font: {
                        weight: 'bold',
                        size: 11
                    },
                    color: (context) => {
                        const value = context.dataset.data[context.dataIndex];
                        const max = Math.max(...context.dataset.data);
                        return value > max * 0.85 ? '#ffffff' : '#374151';
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.parsed.y.toLocaleString() + ' active users';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grace: '10%',
                    title: {
                        display: true,
                        text: 'Number of Users'
                    },
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
    
    // Subscribers vs Active Users Chart
    const ctx5 = document.getElementById('subscribersChart').getContext('2d');
    
    if (subscribersChart) {
        subscribersChart.destroy();
    }
    
    // Only show channels that have subscriber data
    const channelsWithSubs = comparisons.filter(c => c.total_participants > 0);
    
    if (channelsWithSubs.length > 0) {
        // Calculate engagement percentages
        const engagementPercentages = channelsWithSubs.map(c => 
            c.total_participants > 0 ? Math.round((c.active_users / c.total_participants) * 100) : 0
        );
        
        subscribersChart = new Chart(ctx5, {
            type: 'bar',
            data: {
                labels: channelsWithSubs.map(c => '@' + c.channel),
                datasets: [
                    {
                        label: 'Total Subscribers',
                        data: channelsWithSubs.map(c => c.total_participants),
                        backgroundColor: 'rgba(59, 130, 246, 0.5)',
                        borderColor: 'rgb(59, 130, 246)',
                        borderWidth: 1
                    },
                    {
                        label: 'Active Users (' + (comparisons[0]?.period_days || 7) + ' days)',
                        data: channelsWithSubs.map(c => c.active_users),
                        backgroundColor: 'rgba(16, 185, 129, 0.8)',
                        borderColor: 'rgb(16, 185, 129)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    datalabels: {
                        anchor: 'end',
                        align: 'top',
                        formatter: (value, ctx) => {
                            if (ctx.datasetIndex === 0) {
                                // For subscribers, only show if > 1000
                                return value > 1000 ? value.toLocaleString() : '';
                            } else {
                                // For active users, always show percentage
                                const percentage = engagementPercentages[ctx.dataIndex];
                                return `${percentage}%`;
                            }
                        },
                        font: {
                            weight: 'bold',
                            size: 11
                        },
                        color: (ctx) => ctx.datasetIndex === 0 ? 'rgb(59, 130, 246)' : 'rgb(16, 185, 129)'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += context.parsed.y.toLocaleString();
                                
                                // Add percentage for active users
                                if (context.datasetIndex === 1) {
                                    const subscribers = context.chart.data.datasets[0].data[context.dataIndex];
                                    const percentage = subscribers > 0 ? Math.round((context.parsed.y / subscribers) * 100) : 0;
                                    label += ` (${percentage}% of subscribers)`;
                                }
                                
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grace: '10%',
                        title: {
                            display: true,
                            text: 'Number of Users'
                        },
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    } else {
        // Hide the chart container if no subscriber data
        document.getElementById('subscribersChart').parentElement.style.display = 'none';
    }
    
    // Add descriptions for Subscribers chart if visible
    if (channelsWithSubs.length > 0) {
        const subscribersContainer = document.getElementById('subscribersChart').parentElement;
        const subscribersDesc = document.createElement('p');
        subscribersDesc.className = 'text-sm text-gray-600 mt-2 mb-6 bg-gray-50 p-3 rounded';
        subscribersDesc.innerHTML = '📈 Compares total subscribers vs active users. The percentage shows engagement rate - how many subscribers are actually active.';
        subscribersContainer.appendChild(subscribersDesc);
    }
    
    // User Engagement Ratio Chart
    const ctx6 = document.getElementById('engagementRatioChart').getContext('2d');
    
    if (engagementRatioChart) {
        engagementRatioChart.destroy();
    }
    
    if (channelsWithSubs.length > 0) {
        const engagementRatios = channelsWithSubs.map(c => 
            c.total_participants > 0 ? Math.round((c.active_users / c.total_participants) * 1000) / 10 : 0
        );
        
        engagementRatioChart = new Chart(ctx6, {
            type: 'bar',
            data: {
                labels: channelsWithSubs.map(c => '@' + c.channel),
                datasets: [{
                    label: 'Engagement Rate',
                    data: engagementRatios,
                    backgroundColor: engagementRatios.map((ratio, index) => {
                        // Color based on engagement level
                        if (ratio > 10) return 'rgba(16, 185, 129, 0.8)'; // Green for high
                        if (ratio > 5) return 'rgba(251, 191, 36, 0.8)';  // Yellow for medium
                        return 'rgba(239, 68, 68, 0.8)';                    // Red for low
                    }),
                    borderColor: engagementRatios.map((ratio, index) => {
                        if (ratio > 10) return 'rgb(16, 185, 129)';
                        if (ratio > 5) return 'rgb(251, 191, 36)';
                        return 'rgb(239, 68, 68)';
                    }),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: false },
                    datalabels: {
                        anchor: 'end',
                        align: 'top',
                        formatter: (value, ctx) => {
                            const channel = channelsWithSubs[ctx.dataIndex];
                            return `${value}%\n(${channel.active_users.toLocaleString()} / ${channel.total_participants.toLocaleString()})`;
                        },
                        font: {
                            weight: 'bold',
                            size: 11
                        },
                        color: (ctx) => {
                            const value = ctx.dataset.data[ctx.dataIndex];
                            if (value > 10) return 'rgb(16, 185, 129)';
                            if (value > 5) return 'rgb(251, 191, 36)';
                            return 'rgb(239, 68, 68)';
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const channel = channelsWithSubs[context.dataIndex];
                                return [
                                    `Engagement: ${context.parsed.y}%`,
                                    `Active: ${channel.active_users.toLocaleString()} of ${channel.total_participants.toLocaleString()}`
                                ];
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grace: '10%',
                        max: Math.max(...engagementRatios) * 1.2, // 20% headroom
                        title: {
                            display: true,
                            text: 'Engagement Rate (%)'
                        },
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                }
            }
        });
        
        // Add description for engagement ratio
        const engagementRatioContainer = document.getElementById('engagementRatioChart').parentElement;
        const engagementRatioDesc = document.createElement('p');
        engagementRatioDesc.className = 'text-sm text-gray-600 mt-2 mb-6 bg-gray-50 p-3 rounded';
        engagementRatioDesc.innerHTML = '🎯 Engagement rate percentage. Green (>10%) = excellent, Yellow (5-10%) = good, Red (<5%) = needs improvement.';
        engagementRatioContainer.appendChild(engagementRatioDesc);
    } else {
        document.getElementById('engagementRatioChart').parentElement.style.display = 'none';
    }
}

function createActivityPatterns(comparisons) {
    const container = document.getElementById('activityPatterns');
    container.innerHTML = '';
    
    comparisons.forEach(channel => {
        const card = document.createElement('div');
        card.className = 'bg-gray-50 rounded-lg p-4';
        card.innerHTML = `
            <h4 class="font-semibold text-gray-800 mb-2">@${channel.channel}</h4>
            <div class="text-sm space-y-1">
                <p class="text-gray-600">
                    <span class="font-medium">Peak Hour:</span> 
                    <span class="text-blue-600">${channel.peak_hour}</span>
                </p>
                <p class="text-gray-600">
                    <span class="font-medium">Peak Day:</span> 
                    <span class="text-green-600">${channel.peak_day}</span>
                </p>
            </div>
        `;
        container.appendChild(card);
    });
}

// Add capture functionality
document.addEventListener('DOMContentLoaded', function() {
    // Capture as Image
    const captureImageButton = document.getElementById('captureImageButton');
    if (captureImageButton) {
        captureImageButton.addEventListener('click', async function() {
            const resultsElement = document.getElementById('results');
            
            // Show loading state
            this.disabled = true;
            this.innerHTML = '<svg class="animate-spin h-5 w-5" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Generating...';
            
            try {
                const canvas = await html2canvas(resultsElement, {
                    scale: 2,
                    logging: false,
                    useCORS: true,
                    backgroundColor: '#ffffff'
                });
                
                // Convert to blob and download
                canvas.toBlob(function(blob) {
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    const timestamp = new Date().toISOString().slice(0, 10);
                    a.href = url;
                    a.download = `telegram-comparison-${timestamp}.png`;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                });
            } catch (error) {
                console.error('Error capturing image:', error);
                alert('Failed to capture image. Please try again.');
            } finally {
                // Restore button
                this.disabled = false;
                this.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg><span class="hidden sm:inline ml-2">Save as Image</span><span class="sm:hidden ml-2">Image</span>';
            }
        });
    }
    
    // Capture as PDF
    const capturePdfButton = document.getElementById('capturePdfButton');
    if (capturePdfButton) {
        capturePdfButton.addEventListener('click', async function() {
            const resultsElement = document.getElementById('results');
            
            // Show loading state
            this.disabled = true;
            this.innerHTML = '<svg class="animate-spin h-5 w-5" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Generating...';
            
            try {
                const canvas = await html2canvas(resultsElement, {
                    scale: 2,
                    logging: false,
                    useCORS: true,
                    backgroundColor: '#ffffff'
                });
                
                // Calculate PDF dimensions
                const imgWidth = 210; // A4 width in mm
                const pageHeight = 297; // A4 height in mm
                const imgHeight = (canvas.height * imgWidth) / canvas.width;
                let heightLeft = imgHeight;
                
                const pdf = new jsPDF('p', 'mm', 'a4');
                let position = 0;
                
                // Add title
                pdf.setFontSize(20);
                pdf.text('Telegram Channel Comparison Report', 105, 15, { align: 'center' });
                pdf.setFontSize(12);
                pdf.text(new Date().toLocaleDateString(), 105, 22, { align: 'center' });
                
                position = 30;
                
                // Add image to PDF (handle multiple pages if needed)
                const imgData = canvas.toDataURL('image/png');
                
                if (heightLeft >= pageHeight - 30) {
                    // First page
                    pdf.addImage(imgData, 'PNG', 0, position, imgWidth, pageHeight - 30);
                    heightLeft -= (pageHeight - 30);
                    
                    // Add more pages if needed
                    while (heightLeft >= 0) {
                        pdf.addPage();
                        position = heightLeft > pageHeight ? -pageHeight + 10 : -heightLeft + 10;
                        pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                        heightLeft -= pageHeight;
                    }
                } else {
                    // Fits in one page
                    pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                }
                
                // Save PDF
                const timestamp = new Date().toISOString().slice(0, 10);
                pdf.save(`telegram-comparison-${timestamp}.pdf`);
                
            } catch (error) {
                console.error('Error generating PDF:', error);
                alert('Failed to generate PDF. Please try again.');
            } finally {
                // Restore button
                this.disabled = false;
                this.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg><span class="hidden sm:inline ml-2">Save as PDF</span><span class="sm:hidden ml-2">PDF</span>';
            }
        });
    }
});