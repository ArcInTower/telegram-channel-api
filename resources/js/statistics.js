import Chart from 'chart.js/auto';

window.Chart = Chart;

// Make functions globally available
window.createHourlyChart = function(hourData) {
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
};

window.createDailyChart = function(dayData) {
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
};