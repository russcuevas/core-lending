// Host Superadmin Scripts & Charts

function initHostCharts(chartData) {
    if (typeof Chart === 'undefined') return;

    const ctx = document.getElementById('dailyTransactionChart');
    if (!ctx) return;

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: chartData.labels || ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            datasets: [
                {
                    label: 'Cash In (₱)',
                    data: chartData.cashIn || [15000, 22000, 18000, 24000, 19500, 31000, 28000],
                    backgroundColor: '#059669',
                    borderRadius: 6,
                },
                {
                    label: 'Cash Out (₱)',
                    data: chartData.cashOut || [10000, 12000, 14000, 15000, 11000, 18000, 16000],
                    backgroundColor: '#d97706',
                    borderRadius: 6,
                },
                {
                    label: 'Loan Collections (₱)',
                    data: chartData.collections || [8500, 14000, 12500, 16000, 14200, 21000, 19800],
                    backgroundColor: '#4f46e5',
                    borderRadius: 6,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        font: {
                            family: "'Plus Jakarta Sans', sans-serif",
                            size: 12
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₱' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
}
