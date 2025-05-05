/**
 * Main JavaScript for Instagram Post Scheduler
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Auto-dismiss alerts after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
        alerts.forEach(function(alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);

    // Initialize Flatpickr date pickers with Portuguese localization
    if (document.querySelector('.datepicker')) {
        flatpickr('.datepicker', {
            dateFormat: 'd/m/Y',
            locale: 'pt',
            allowInput: true
        });
    }

    // Initialize Flatpickr time pickers
    if (document.querySelector('.timepicker')) {
        flatpickr('.timepicker', {
            enableTime: true,
            noCalendar: true,
            dateFormat: 'H:i',
            time_24hr: true,
            minuteIncrement: 15,
            allowInput: true
        });
    }

    // Handle mobile navigation
    const navbarToggler = document.querySelector('.navbar-toggler');
    if (navbarToggler) {
        navbarToggler.addEventListener('click', function() {
            document.querySelector('.navbar-collapse').classList.toggle('show');
        });
    }

    // Dashboard charts
    initDashboardCharts();
});

/**
 * Initialize Dashboard Charts
 */
function initDashboardCharts() {
    const postsChartEl = document.getElementById('postsChart');
    const clientsChartEl = document.getElementById('clientsChart');
    
    if (postsChartEl) {
        new Chart(postsChartEl, {
            type: 'line',
            data: {
                labels: getLastSevenDays(),
                datasets: [{
                    label: 'Postagens',
                    data: [0, 0, 0, 0, 0, 0, 0], // Will be populated from API
                    borderColor: '#E1306C',
                    backgroundColor: 'rgba(225, 48, 108, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Postagens nos Últimos 7 Dias'
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
    
    if (clientsChartEl) {
        new Chart(clientsChartEl, {
            type: 'doughnut',
            data: {
                labels: ['Postagens Agendadas', 'Postagens Publicadas', 'Falhas'],
                datasets: [{
                    data: [0, 0, 0], // Will be populated from API
                    backgroundColor: [
                        '#405DE6',
                        '#58CF86',
                        '#ED4956'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Status de Postagens'
                    }
                }
            }
        });
    }
}

/**
 * Get array of last seven days for chart labels
 */
function getLastSevenDays() {
    const days = [];
    const today = new Date();
    
    for (let i = 6; i >= 0; i--) {
        const date = new Date();
        date.setDate(today.getDate() - i);
        days.push(date.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' }));
    }
    
    return days;
}

/**
 * Update dashboard data
 */
function updateDashboardData(data) {
    // Update posts chart
    if (data.postsData && window.postsChart) {
        window.postsChart.data.datasets[0].data = data.postsData;
        window.postsChart.update();
    }
    
    // Update clients chart
    if (data.statusData && window.clientsChart) {
        window.clientsChart.data.datasets[0].data = data.statusData;
        window.clientsChart.update();
    }
    
    // Update stats cards
    if (data.stats) {
        document.querySelector('#totalPosts').textContent = data.stats.totalPosts || 0;
        document.querySelector('#totalClients').textContent = data.stats.totalClients || 0;
        document.querySelector('#totalUsers').textContent = data.stats.totalUsers || 0;
    }
}
