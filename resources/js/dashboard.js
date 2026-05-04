import Chart from 'chart.js/auto';

// ─── Shared config ────────────────────────────────────────────────────────────

const chartFont = { size: 16, weight: '600' };

const colors = {
    danger: '#DC2626',
    warning: '#CA8A04',
    success: '#16A34A',
    neutral: '#b0b3b6',
    primary: '#286cda',
};

// ─── Student dashboard ────────────────────────────────────────────────────────

function initStudentDashboard() {
    const stats = window.dashboardStats || {
        approvedHours: 0,
        pendingHours: 0,
        remainingHours: 0,
        weeklyHours: [0, 0, 0, 0, 0],
        minHoursDay: 6,
    };

    const charts = [];

    const pieCtx = document.getElementById('hoursPieChart');
    if (pieCtx) {
        charts.push(new Chart(pieCtx, {
            type: 'pie',
            data: {
                labels: ['Approved', 'Pending', 'Missing'],
                datasets: [{
                    data: [stats.approvedHours, stats.pendingHours, stats.remainingHours],
                    backgroundColor: [colors.success, colors.warning, colors.danger],
                    borderColor: '#ffffff',
                    borderWidth: 1.5,
                    hoverOffset: 15,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { font: chartFont, padding: 25, usePointStyle: true },
                    },
                    tooltip: {
                        bodyFont: { size: 14 },
                        callbacks: { label: ctx => ` ${ctx.label}: ${ctx.raw}h` },
                    },
                },
            },
        }));
    }

    const barCtx = document.getElementById('hoursBarChart');
    if (barCtx) {
        charts.push(new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
                datasets: [
                    {
                        label: 'Logged Hours',
                        data: stats.weeklyHours,
                        backgroundColor: colors.primary,
                        borderRadius: 6,
                        maxBarThickness: 45,
                    },
                    {
                        label: `Target (${stats.minHoursDay}h)`,
                        data: Array(5).fill(stats.minHoursDay),
                        backgroundColor: colors.neutral,
                        borderRadius: 6,
                        maxBarThickness: 45,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 10,
                        grid: { color: '#F1F5F9' },
                        ticks: { font: chartFont, callback: v => v + 'h' },
                    },
                    x: {
                        grid: { display: true, color: '#F1F5F9' },
                        ticks: { font: chartFont },
                    },
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { font: chartFont, padding: 20, usePointStyle: true },
                    },
                },
            },
        }));
    }

    window.addEventListener('resize', () => charts.forEach(c => c.resize()));
}

// ─── Coordinator dashboard ────────────────────────────────────────────────────

const coordinatorSmallFont = { size: 12 };

function initCoordinatorCharts() {
    let pieChart = null;
    let barChart = null;

    function render(student) {
        destroy();

        const pieCtx = document.getElementById('studentPieChart');
        if (pieCtx) {
            pieChart = new Chart(pieCtx, {
                type: 'pie',
                data: {
                    labels: ['Approved', 'Pending', 'Rejected', 'Remaining'],
                    datasets: [{
                        data: [
                            student.approved_hours,
                            student.pending_hours,
                            student.rejected_hours,
                            student.remaining_hours,
                        ],
                        backgroundColor: [
                            colors.success,
                            colors.warning,
                            colors.danger,
                            '#e5e7eb'
                        ],
                        borderWidth: 2,
                        borderColor: '#fff',
                        hoverOffset: 5,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                font: coordinatorSmallFont,
                                padding: 12,
                                boxWidth: 10
                            },
                        },
                        tooltip: {
                            callbacks: {
                                label: ctx => ` ${ctx.parsed}h`
                            },
                        },
                    },
                },
            });
        }

        const barCtx = document.getElementById('studentBarChart');
        if (barCtx) {
            barChart = new Chart(barCtx, {
                type: 'bar',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
                    datasets: [{
                        label: 'Hours',
                        data: student.weekly_hours,
                        backgroundColor: colors.primary,
                        borderRadius: 5,
                        borderSkipped: false,
                        maxBarThickness: 40,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: { label: ctx => ` ${ctx.parsed.y}h` },
                        },
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: '#F1F5F9' },
                            ticks: { font: coordinatorSmallFont, callback: v => v + 'h' },
                        },
                        x: {
                            grid: { display: false },
                            ticks: { font: coordinatorSmallFont },
                        },
                    },
                },
            });
        }
    }

    function destroy() {
        if (pieChart) { pieChart.destroy(); pieChart = null; }
        if (barChart) { barChart.destroy(); barChart = null; }
    }

    return { render, destroy };
}

window.coordinatorCharts = initCoordinatorCharts();

document.addEventListener('DOMContentLoaded', () => {
    initStudentDashboard();
});