/**
 * Chart.js Visualizations Controller
 * Real-time dynamic chart rendering connecting MySQL analytics to frontend canvases
 */

const chartInstances = {};

function destroyChart(id) {
    if (chartInstances[id]) {
        chartInstances[id].destroy();
        delete chartInstances[id];
    }
}

// 1. Department Placement Rate Chart (Bar Chart)
async function renderPlacementByDeptChart(canvasId) {
    const canvas = document.getElementById(canvasId);
    if (!canvas || typeof Chart === 'undefined') return;

    try {
        const res = await apiRequest('analytics/placement.php');
        const depts = res.department_summary || [];

        const labels = depts.map(d => d.dept_code || d.dept_name);
        const placementRates = depts.map(d => parseFloat(d.placement_percentage || 0));
        const avgPackages = depts.map(d => parseFloat(d.average_package_lpa || 0));

        destroyChart(canvasId);
        chartInstances[canvasId] = new Chart(canvas, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Placement Rate (%)',
                        data: placementRates,
                        backgroundColor: 'rgba(37, 99, 235, 0.85)',
                        borderColor: '#2563eb',
                        borderWidth: 1,
                        borderRadius: 6,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Avg Package (LPA)',
                        data: avgPackages,
                        backgroundColor: 'rgba(16, 185, 129, 0.85)',
                        borderColor: '#10b981',
                        borderWidth: 1,
                        borderRadius: 6,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        title: { display: true, text: 'Placement %' }
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        grid: { drawOnChartArea: false },
                        title: { display: true, text: 'LPA (₹)' }
                    }
                }
            }
        });
    } catch (e) {
        console.error('Failed to render dept placement chart', e);
    }
}

// 2. Application Funnel / Status Breakdown (Doughnut Chart)
async function renderApplicationStatusChart(canvasId) {
    const canvas = document.getElementById(canvasId);
    if (!canvas || typeof Chart === 'undefined') return;

    try {
        const res = await apiRequest('analytics/applications.php');
        const dist = res.status_distribution || [];

        const labels = dist.map(d => d.status.replace('_', ' '));
        const counts = dist.map(d => parseInt(d.count));

        const colors = [
            '#38bdf8', // Applied
            '#fbbf24', // Under Review
            '#a78bfa', // Shortlisted
            '#818cf8', // Interview
            '#34d399', // Selected
            '#f87171', // Rejected
            '#94a3b8'  // Withdrawn
        ];

        destroyChart(canvasId);
        chartInstances[canvasId] = new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: counts,
                    backgroundColor: colors.slice(0, counts.length),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right' }
                },
                cutout: '65%'
            }
        });
    } catch (e) {
        console.error('Failed to render status chart', e);
    }
}

// 3. Top Companies by Hires (Horizontal Bar Chart)
async function renderTopCompaniesChart(canvasId) {
    const canvas = document.getElementById(canvasId);
    if (!canvas || typeof Chart === 'undefined') return;

    try {
        const res = await apiRequest('analytics/recruitment.php');
        const comps = (res.top_companies || []).slice(0, 7);

        const labels = comps.map(c => c.company_name);
        const hires = comps.map(c => parseInt(c.hired_students || 0));

        destroyChart(canvasId);
        chartInstances[canvasId] = new Chart(canvas, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Students Placed',
                    data: hires,
                    backgroundColor: 'rgba(79, 70, 229, 0.85)',
                    borderRadius: 6
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });
    } catch (e) {
        console.error('Failed to render top companies chart', e);
    }
}

// 4. Skill Demand vs Supply (Grouped Bar Chart)
async function renderSkillDemandChart(canvasId) {
    const canvas = document.getElementById(canvasId);
    if (!canvas || typeof Chart === 'undefined') return;

    try {
        const res = await apiRequest('analytics/skills.php');
        const skills = (res.skills || []).slice(0, 8);

        const labels = skills.map(s => s.skill_name);
        const demand = skills.map(s => parseInt(s.jobs_requiring || 0));
        const supply = skills.map(s => parseInt(s.students_possessing || 0));

        destroyChart(canvasId);
        chartInstances[canvasId] = new Chart(canvas, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Industry Demand (Jobs)',
                        data: demand,
                        backgroundColor: '#ef4444',
                        borderRadius: 4
                    },
                    {
                        label: 'Student Supply (Candidates)',
                        data: supply,
                        backgroundColor: '#3b82f6',
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    } catch (e) {
        console.error('Failed to render skill demand chart', e);
    }
}

// 5. Salary Distribution Brackets (Polar/Bar Chart)
async function renderSalaryDistributionChart(canvasId) {
    const canvas = document.getElementById(canvasId);
    if (!canvas || typeof Chart === 'undefined') return;

    try {
        const res = await apiRequest('analytics/placement.php');
        const brackets = res.salary_brackets || [];

        const labels = brackets.map(b => b.salary_range);
        const counts = brackets.map(b => parseInt(b.count));

        destroyChart(canvasId);
        chartInstances[canvasId] = new Chart(canvas, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Offers Count',
                    data: counts,
                    backgroundColor: 'rgba(16, 185, 129, 0.85)',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });
    } catch (e) {
        console.error('Failed to render salary chart', e);
    }
}

// Master Admin Dashboard Charts Initializer
function renderAdminDashboardCharts() {
    renderPlacementByDeptChart('chart-dept-placement');
    renderApplicationStatusChart('chart-app-status');
    renderTopCompaniesChart('chart-top-companies');
    renderSkillDemandChart('chart-skill-demand');
}
