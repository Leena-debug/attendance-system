// ===== CHARTS AND DATA VISUALIZATION =====

class AttendanceCharts {
    constructor() {
        this.charts = new Map();
        this.init();
    }

    init() {
        this.setupChartContainers();
        this.loadCharts();
    }

    setupChartContainers() {
        // Initialize chart containers with loading states
        $('.chart-container').each((index, container) => {
            const $container = $(container);
            const chartId = $container.attr('id') || `chart-${index}`;
            $container.attr('id', chartId);
            $container.html('<div class="loading"></div>');
        });
    }

    async loadCharts() {
        try {
            // Load attendance trends chart
            await this.loadAttendanceTrends();
            
            // Load course performance chart
            await this.loadCoursePerformance();
            
            // Load student distribution chart
            await this.loadStudentDistribution();
            
        } catch (error) {
            console.error('Error loading charts:', error);
        }
    }

    async loadAttendanceTrends() {
        const data = await this.fetchChartData('attendance_trends');
        
        if (data && data.length > 0) {
            this.createLineChart('attendanceTrendsChart', {
                labels: data.map(item => item.date),
                datasets: [{
                    label: 'Attendance Rate (%)',
                    data: data.map(item => item.rate),
                    borderColor: '#e91e63',
                    backgroundColor: 'rgba(233, 30, 99, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            });
        }
    }

    async loadCoursePerformance() {
        const data = await this.fetchChartData('course_performance');
        
        if (data && data.length > 0) {
            this.createBarChart('coursePerformanceChart', {
                labels: data.map(item => item.course),
                datasets: [{
                    label: 'Attendance Rate (%)',
                    data: data.map(item => item.rate),
                    backgroundColor: data.map(item => 
                        item.rate >= 80 ? '#4caf50' :
                        item.rate >= 60 ? '#ff9800' : '#f44336'
                    )
                }]
            });
        }
    }

    async loadStudentDistribution() {
        const data = await this.fetchChartData('student_distribution');
        
        if (data) {
            this.createDoughnutChart('studentDistributionChart', {
                labels: ['Excellent (≥80%)', 'Good (60-79%)', 'Needs Attention (<60%)'],
                datasets: [{
                    data: [data.excellent, data.good, data.needs_attention],
                    backgroundColor: ['#4caf50', '#ff9800', '#f44336']
                }]
            });
        }
    }

    async fetchChartData(endpoint) {
        try {
            const response = await $.get(`../api/charts/${endpoint}.php`);
            return response.data || response;
        } catch (error) {
            console.error(`Error fetching ${endpoint} data:`, error);
            return null;
        }
    }

    createLineChart(canvasId, chartData) {
        const ctx = document.getElementById(canvasId);
        if (!ctx) return;

        new Chart(ctx, {
            type: 'line',
            data: chartData,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Attendance Trends Over Time'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                }
            }
        });
    }

    createBarChart(canvasId, chartData) {
        const ctx = document.getElementById(canvasId);
        if (!ctx) return;

        new Chart(ctx, {
            type: 'bar',
            data: chartData,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Course Performance'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                }
            }
        });
    }

    createDoughnutChart(canvasId, chartData) {
        const ctx = document.getElementById(canvasId);
        if (!ctx) return;

        new Chart(ctx, {
            type: 'doughnut',
            data: chartData,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    title: {
                        display: true,
                        text: 'Student Performance Distribution'
                    }
                }
            }
        });
    }

    // Utility method to create simple progress charts
    createMiniProgressChart(container, percentage, label) {
        const color = percentage >= 80 ? '#4caf50' :
                     percentage >= 60 ? '#ff9800' : '#f44336';

        $(container).html(`
            <div class="mini-chart">
                <div class="chart-progress" style="
                    width: 60px;
                    height: 60px;
                    border-radius: 50%;
                    background: conic-gradient(${color} ${percentage * 3.6}deg, #f0f0f0 0deg);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin: 0 auto 10px;
                ">
                    <div style="
                        width: 40px;
                        height: 40px;
                        background: white;
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-weight: bold;
                        color: ${color};
                    ">
                        ${percentage}%
                    </div>
                </div>
                <div class="chart-label" style="text-align: center; font-size: 12px; color: #666;">
                    ${label}
                </div>
            </div>
        `);
    }
}

// Initialize charts when DOM is ready
$(document).ready(function() {
    if (typeof Chart !== 'undefined') {
        window.attendanceCharts = new AttendanceCharts();
    }
});