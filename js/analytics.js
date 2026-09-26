/**
 * Placement Analytics Controller
 * Powers charts and department summary table in Admin Analytics Hub
 */

async function loadAnalyticsPage() {
    renderPlacementByDeptChart('chart-dept-placement');
    renderApplicationStatusChart('chart-app-status');
    renderTopCompaniesChart('chart-top-companies');
    renderSkillDemandChart('chart-skill-demand');
    renderSalaryDistributionChart('chart-salary-distribution');

    const tbody = document.getElementById('dept-analytics-tbody');
    if (tbody) {
        try {
            const res = await apiRequest('analytics/placement.php');
            tbody.innerHTML = (res.department_summary || []).map(d => `
                <tr>
                    <td><strong>${d.dept_name}</strong></td>
                    <td>${d.dept_code}</td>
                    <td>${d.total_students}</td>
                    <td>${d.placed_students}</td>
                    <td><strong style="color: var(--success);">${d.placement_percentage}%</strong></td>
                    <td>${d.average_package_lpa ? '₹' + d.average_package_lpa + ' LPA' : 'N/A'}</td>
                    <td>${d.highest_package_lpa ? '₹' + d.highest_package_lpa + ' LPA' : 'N/A'}</td>
                </tr>
            `).join('');
        } catch (e) {
            tbody.innerHTML = '<tr><td colspan="7">Error loading department analytics.</td></tr>';
        }
    }
}
