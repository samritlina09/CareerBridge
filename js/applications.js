/**
 * Student Applications Tracker Client Controller
 */

async function loadStudentApplicationsTable() {
    const tbody = document.getElementById('student-applications-tbody');
    if (!tbody) return;

    try {
        const res = await apiRequest('student/applications.php');
        const apps = res.applications || [];

        if (apps.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="empty-state"><i class="fas fa-file-alt"></i><h3>No Applications Yet</h3><p>Browse active opportunities and apply to kickstart your recruitment process.</p></td></tr>';
            return;
        }

        tbody.innerHTML = apps.map(a => `
            <tr>
                <td><strong>${a.job_title}</strong><br><small style="color: var(--text-muted);">${a.job_type}</small></td>
                <td>${a.company_name}</td>
                <td>${formatCurrency(a.salary_stipend)}</td>
                <td>${formatDate(a.applied_at)}</td>
                <td><span class="status-pill status-${a.status.toLowerCase()}">${a.status.replace('_', ' ')}</span></td>
                <td>
                    ${(a.interviews && a.interviews.length > 0) ? `
                        <button class="btn btn-outline btn-sm" onclick='viewInterviewsTimeline(${JSON.stringify(a.interviews)})'>
                            <i class="fas fa-calendar-check"></i> ${a.interviews.length} Round(s)
                        </button>
                    ` : '<span style="color: var(--text-muted); font-size: 0.85rem;">None scheduled</span>'}
                </td>
                <td>
                    ${(a.status !== 'SELECTED' && a.status !== 'REJECTED' && a.status !== 'WITHDRAWN') ? `
                        <button class="btn btn-outline btn-sm" onclick="withdrawApplication(${a.application_id})" style="color: var(--danger);" title="Withdraw Application">
                            Withdraw
                        </button>
                    ` : `<span style="color: var(--text-muted); font-size: 0.8rem;">Final</span>`}
                </td>
            </tr>
        `).join('');

    } catch (err) {
        showToast('error', 'Applications Error', err.message);
    }
}

function viewInterviewsTimeline(interviews) {
    const list = document.getElementById('interview-rounds-timeline');
    if (!list) return;

    list.innerHTML = interviews.map(i => `
        <div style="border-left: 3px solid var(--primary); padding-left: 1rem; margin-bottom: 1.25rem; position: relative;">
            <h4 style="font-weight: 700; color: var(--text-primary);">${i.round_name}</h4>
            <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.2rem;">
                <i class="fas fa-clock"></i> ${formatDate(i.scheduled_at)} &bull; Status: <strong>${i.status}</strong>
            </div>
            ${i.meeting_link_location ? `
                <div style="margin-top: 0.4rem; font-size: 0.85rem;">
                    <strong>Location/Link:</strong> <a href="${i.meeting_link_location.startsWith('http') ? i.meeting_link_location : '#'}" target="_blank">${i.meeting_link_location}</a>
                </div>
            ` : ''}
            ${i.result !== 'PENDING' ? `
                <div style="margin-top: 0.35rem;">
                    <span class="badge ${i.result === 'PASSED' ? 'badge-success' : 'badge-danger'}">Result: ${i.result}</span>
                </div>
            ` : ''}
        </div>
    `).join('');

    openModal('interview-rounds-modal');
}

async function withdrawApplication(appId) {
    if (!confirm('Are you sure you want to withdraw this application? This action cannot be undone.')) return;

    try {
        const res = await apiRequest('student/applications.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'withdraw', application_id: appId })
        });
        showToast('success', 'Application Withdrawn', res.message);
        loadStudentApplicationsTable();
    } catch (err) {
        showToast('error', 'Withdrawal Failed', err.message);
    }
}
