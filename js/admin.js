/**
 * Admin / T&P Cell Portal Client Controller
 * Recruiter & Job Approvals, Academic Management, Placements, Reports
 */

async function initAdminView() {
    let currentUser = {
        name: 'T&P Administrator',
        email: 'admin@campusplacement.com',
        role: 'ADMIN'
    };

    try {
        let auth = await apiRequest('auth/me.php');
        if (auth && auth.authenticated && auth.user?.role === 'ADMIN') {
            currentUser = auth.user;
            localStorage.setItem('careerbridge_user', JSON.stringify(currentUser));
        } else {
            const auto = await apiRequest('auth/auto_login.php?role=ADMIN');
            if (auto && auto.success && auto.user) {
                currentUser = auto.user;
                if (auto.token) {
                    localStorage.setItem('careerbridge_token', auto.token);
                    localStorage.setItem('careerbridge_token_ADMIN', auto.token);
                    localStorage.setItem('careerbridge_user', JSON.stringify(auto.user));
                }
            }
        }
    } catch (e) {
        console.warn('Admin auth auto-sync (defaulting to T&P Administrator):', e);
    }

    const nameEls = document.querySelectorAll('.admin-display-name');
    nameEls.forEach(el => el.textContent = currentUser.name || 'T&P Administrator');

    try {
        loadAdminNotificationCount();
    } catch (notifErr) {
        // ignore
    }
    return true;
}

async function loadAdminNotificationCount() {
    try {
        const res = await apiRequest('student/notifications.php');
        const badge = document.getElementById('notif-badge-count');
        if (badge) {
            if (res.unread_count > 0) {
                badge.textContent = res.unread_count;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }
        }
    } catch (e) {
        console.error(e);
    }
}

// Global Admin Dashboard Loader
async function loadAdminDashboard() {
    try {
        const res = await apiRequest('analytics/dashboard_stats.php');
        const g = res.global || {};

        document.getElementById('kpi-admin-students').textContent = g.total_students || 0;
        document.getElementById('kpi-admin-companies').textContent = g.total_companies || 0;
        document.getElementById('kpi-admin-jobs').textContent = g.active_jobs || 0;
        document.getElementById('kpi-admin-apps').textContent = g.total_applications || 0;
        document.getElementById('kpi-admin-placed').textContent = g.selected_students || 0;
        document.getElementById('kpi-admin-rate').textContent = `${g.placement_rate || 0}%`;
        document.getElementById('kpi-admin-avg-pkg').textContent = `₹${g.avg_package_lpa || 0} LPA`;
        document.getElementById('kpi-admin-max-pkg').textContent = `₹${g.max_package_lpa || 0} LPA`;

        // Render charts
        if (typeof renderAdminDashboardCharts === 'function') {
            renderAdminDashboardCharts();
        }

        // Load queues previews
        loadPendingRecruitersPreview();
        loadPendingJobsPreview();

    } catch (err) {
        showToast('error', 'Dashboard Error', err.message);
    }
}

// Pending Recruiters Preview
async function loadPendingRecruitersPreview() {
    const tbody = document.getElementById('dash-pending-recruiters-tbody');
    if (!tbody) return;

    try {
        const res = await apiRequest('admin/recruiters.php?status=PENDING');
        const list = res.recruiters || [];
        if (list.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="empty-state"><i class="fas fa-check-circle" style="color: var(--success); font-size: 1.5rem;"></i><p>No recruiters pending verification.</p></td></tr>';
            return;
        }

        tbody.innerHTML = list.map(r => `
            <tr>
                <td><strong>${r.company_name}</strong><br><small style="color: var(--text-muted);">${r.recruiter_name} &bull; ${r.email}</small></td>
                <td>${r.designation || 'Hiring Lead'}</td>
                <td>${r.location || 'N/A'}</td>
                <td><span class="status-pill status-pending">Pending Approval</span></td>
                <td>
                    <button class="btn btn-success btn-sm" onclick="handleRecruiterApproval(${r.recruiter_id}, 'approve')">Approve</button>
                    <button class="btn btn-outline btn-sm" onclick="handleRecruiterApproval(${r.recruiter_id}, 'reject')" style="color: var(--danger); border-color: var(--danger);">Reject</button>
                </td>
            </tr>
        `).join('');
    } catch (e) {
        tbody.innerHTML = '<tr><td colspan="5">Error loading pending recruiters.</td></tr>';
    }
}

// Pending Jobs Preview
async function loadPendingJobsPreview() {
    const tbody = document.getElementById('dash-pending-jobs-tbody');
    if (!tbody) return;

    try {
        const res = await apiRequest('admin/jobs.php?status=PENDING');
        if (res.jobs.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="empty-state"><i class="fas fa-check-circle" style="color: var(--success); font-size: 1.5rem;"></i><p>No job postings pending review.</p></td></tr>';
            return;
        }

        tbody.innerHTML = res.jobs.map(j => `
            <tr>
                <td><strong>${j.title}</strong><br><small style="color: var(--text-muted);">${j.company_name}</small></td>
                <td><span class="badge badge-primary">${j.job_type}</span></td>
                <td>${formatCurrency(j.salary_stipend)}</td>
                <td><span class="status-pill status-pending">PENDING</span></td>
                <td>
                    <button class="btn btn-success btn-sm" onclick="handleJobApproval(${j.job_id}, 'approve')">Approve</button>
                    <button class="btn btn-outline btn-sm" onclick="handleJobApproval(${j.job_id}, 'reject')" style="color: var(--danger);">Reject</button>
                </td>
            </tr>
        `).join('');
    } catch (e) {
        tbody.innerHTML = '<tr><td colspan="5">Error loading pending jobs.</td></tr>';
    }
}

let currentRecruiterFilter = '';

function filterRecruiterStatus(btn, status) {
    currentRecruiterFilter = status;
    document.querySelectorAll('.filter-tab').forEach(b => {
        b.classList.remove('btn-primary', 'active');
        b.classList.add('btn-outline');
    });
    if (btn) {
        btn.classList.remove('btn-outline');
        btn.classList.add('btn-primary', 'active');
    }
    loadAllRecruitersTable(status);
}

// Recruiter Approval Action (Approve, Reject, Revoke)
async function handleRecruiterApproval(recruiterId, action) {
    const actionLabels = {
        'approve': 'Approve recruiter and authorize job postings?',
        'reject': 'Reject this recruiter registration?',
        'revoke': 'Revoke approval and disable recruiter permissions?'
    };
    if (action === 'revoke' || action === 'reject') {
        if (!confirm(actionLabels[action])) return;
    }

    try {
        const res = await apiRequest('admin/recruiters.php', {
            method: 'POST',
            body: JSON.stringify({ recruiter_id: recruiterId, action })
        });
        showToast('success', 'Recruiter Updated', res.message);
        if (typeof loadPendingRecruitersPreview === 'function') loadPendingRecruitersPreview();
        if (typeof loadAllRecruitersTable === 'function') loadAllRecruitersTable(currentRecruiterFilter);
    } catch (err) {
        showToast('error', 'Action Failed', err.message);
    }
}

// All Recruiters Full Table & Pending Section
async function loadAllRecruitersTable(statusFilter = currentRecruiterFilter) {
    const allTbody = document.getElementById('all-recruiters-tbody');
    const pendingTbody = document.getElementById('pending-recruiters-tbody');
    const pendingBadge = document.getElementById('pending-recruiters-badge');

    try {
        const res = await apiRequest('admin/recruiters.php');
        const allList = res.recruiters || [];
        const counts = res.counts || { total: allList.length, pending: 0, approved: 0, rejected: 0 };

        // Update count badges
        const countAll = document.getElementById('count-all');
        const countPending = document.getElementById('count-pending');
        const countApproved = document.getElementById('count-approved');
        const countRejected = document.getElementById('count-rejected');
        if (countAll) countAll.textContent = counts.total;
        if (countPending) countPending.textContent = counts.pending;
        if (countApproved) countApproved.textContent = counts.approved;
        if (countRejected) countRejected.textContent = counts.rejected;
        if (pendingBadge) pendingBadge.textContent = `${counts.pending} Pending`;

        // 1. Render Dedicated Pending Table
        if (pendingTbody) {
            const pendingList = allList.filter(r => r.approval_status === 'PENDING');
            if (pendingList.length === 0) {
                pendingTbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="empty-state" style="padding: 2rem;">
                            <i class="fas fa-check-circle" style="color: var(--success); font-size: 1.5rem; margin-bottom: 0.5rem;"></i>
                            <p style="margin: 0;">No recruiters currently awaiting approval.</p>
                        </td>
                    </tr>
                `;
            } else {
                pendingTbody.innerHTML = pendingList.map(r => `
                    <tr>
                        <td>
                            <strong>${r.recruiter_name}</strong><br>
                            <small style="color: var(--text-muted);"><i class="fas fa-envelope"></i> ${r.email}</small>
                        </td>
                        <td>${r.designation || 'Hiring Specialist'}</td>
                        <td><strong>${r.company_name}</strong></td>
                        <td>
                            <div style="font-size: 0.85rem; color: var(--text-secondary);">
                                <div><i class="fas fa-map-marker-alt"></i> ${r.location || 'Not specified'}</div>
                                <div><i class="fas fa-industry"></i> ${r.industry || 'General'}</div>
                                ${r.website ? `<div><a href="${r.website}" target="_blank" style="color: var(--primary); text-decoration: underline;"><i class="fas fa-external-link-alt"></i> Website</a></div>` : ''}
                            </div>
                        </td>
                        <td>${formatDate(r.created_at)}</td>
                        <td><span class="status-pill status-pending">Pending Approval</span></td>
                        <td>
                            <div style="display: flex; gap: 0.35rem;">
                                <button class="btn btn-success btn-sm" onclick="handleRecruiterApproval(${r.recruiter_id}, 'approve')">
                                    <i class="fas fa-check"></i> Approve
                                </button>
                                <button class="btn btn-outline btn-sm" onclick="handleRecruiterApproval(${r.recruiter_id}, 'reject')" style="color: var(--danger); border-color: var(--danger);">
                                    <i class="fas fa-times"></i> Reject
                                </button>
                            </div>
                        </td>
                    </tr>
                `).join('');
            }
        }

        // 2. Render Main Table (Filtered)
        if (allTbody) {
            let filteredList = allList;
            if (statusFilter) {
                filteredList = allList.filter(r => r.approval_status === statusFilter);
            }

            if (filteredList.length === 0) {
                allTbody.innerHTML = `<tr><td colspan="7" class="empty-state">No recruiters found for selected filter.</td></tr>`;
                return;
            }

            allTbody.innerHTML = filteredList.map(r => {
                let statusBadge = '<span class="status-pill status-pending">Pending Approval</span>';
                if (r.approval_status === 'APPROVED') {
                    statusBadge = '<span class="status-pill status-selected">Approved</span>';
                } else if (r.approval_status === 'REJECTED') {
                    statusBadge = '<span class="status-pill status-rejected">Rejected</span>';
                }

                let actionHtml = '';
                if (r.approval_status === 'PENDING') {
                    actionHtml = `
                        <div style="display: flex; gap: 0.35rem;">
                            <button class="btn btn-success btn-sm" onclick="handleRecruiterApproval(${r.recruiter_id}, 'approve')">Approve</button>
                            <button class="btn btn-outline btn-sm" onclick="handleRecruiterApproval(${r.recruiter_id}, 'reject')" style="color: var(--danger); border-color: var(--danger);">Reject</button>
                        </div>
                    `;
                } else if (r.approval_status === 'APPROVED') {
                    actionHtml = `
                        <button class="btn btn-outline btn-sm" onclick="handleRecruiterApproval(${r.recruiter_id}, 'revoke')" style="color: var(--danger); border-color: var(--border-color);" title="Revoke recruiter privileges">
                            <i class="fas fa-user-slash"></i> Revoke
                        </button>
                    `;
                } else if (r.approval_status === 'REJECTED') {
                    actionHtml = `
                        <button class="btn btn-outline btn-sm" onclick="handleRecruiterApproval(${r.recruiter_id}, 'approve')" style="color: var(--success); border-color: var(--success);" title="Re-authorize recruiter">
                            <i class="fas fa-redo"></i> Re-Approve
                        </button>
                    `;
                }

                return `
                    <tr>
                        <td>
                            <strong>${r.recruiter_name}</strong><br>
                            <small style="color: var(--text-muted);"><i class="fas fa-envelope"></i> ${r.email}</small>
                        </td>
                        <td>${r.designation || 'Hiring Lead'}</td>
                        <td><strong>${r.company_name}</strong></td>
                        <td>
                            <div style="font-size: 0.85rem; color: var(--text-secondary);">
                                <span>${r.location || 'N/A'}</span> &bull; <span>${r.industry || 'Industry'}</span>
                                ${r.website ? `<br><a href="${r.website}" target="_blank" style="color: var(--primary); text-decoration: underline; font-size: 0.8rem;">${r.website.replace(/^https?:\/\//, '')}</a>` : ''}
                            </div>
                        </td>
                        <td>${statusBadge}</td>
                        <td>${formatDate(r.created_at)}</td>
                        <td>${actionHtml}</td>
                    </tr>
                `;
            }).join('');
        }
    } catch (err) {
        if (allTbody) allTbody.innerHTML = `<tr><td colspan="7">Error loading recruiters: ${err.message}</td></tr>`;
        showToast('error', 'Recruiters Error', err.message);
    }
}

// Handle Job Approval Action (Approve, Reject, Close)
async function handleJobApproval(jobId, action) {
    const actionLabels = {
        'approve': 'Approve this job posting and publish it to the Student Portal?',
        'reject': 'Reject this job posting?',
        'close': 'Close this job posting?'
    };
    if (action === 'reject' || action === 'close') {
        if (!confirm(actionLabels[action])) return;
    }

    try {
        const res = await apiRequest('admin/jobs.php', {
            method: 'POST',
            body: JSON.stringify({ job_id: jobId, action })
        });
        showToast('success', 'Job Updated', res.message);
        if (typeof loadPendingJobsPreview === 'function') loadPendingJobsPreview();
        if (typeof loadAllJobsTable === 'function') loadAllJobsTable(currentJobFilter);
    } catch (err) {
        showToast('error', 'Action Failed', err.message);
    }
}

let currentJobFilter = '';

function filterJobStatus(btn, status) {
    currentJobFilter = status;
    document.querySelectorAll('.filter-tab-job').forEach(b => {
        b.classList.remove('btn-primary', 'active');
        b.classList.add('btn-outline');
    });
    if (btn) {
        btn.classList.remove('btn-outline');
        btn.classList.add('btn-primary', 'active');
    }
    loadAllJobsTable(status);
}

// All Jobs Full Table & Pending Section
async function loadAllJobsTable(statusFilter = currentJobFilter) {
    const allTbody = document.getElementById('all-jobs-tbody');
    const pendingTbody = document.getElementById('pending-jobs-tbody');
    const pendingBadge = document.getElementById('pending-jobs-badge');

    try {
        const res = await apiRequest('admin/jobs.php');
        const allList = res.jobs || [];
        const counts = res.counts || { total: allList.length, pending: 0, approved: 0, rejected: 0, closed: 0 };

        // Update count badges
        const countAll = document.getElementById('job-count-all');
        const countPending = document.getElementById('job-count-pending');
        const countApproved = document.getElementById('job-count-approved');
        const countRejected = document.getElementById('job-count-rejected');
        if (countAll) countAll.textContent = counts.total;
        if (countPending) countPending.textContent = counts.pending;
        if (countApproved) countApproved.textContent = counts.approved;
        if (countRejected) countRejected.textContent = counts.rejected;
        if (pendingBadge) pendingBadge.textContent = `${counts.pending} Pending`;

        // 1. Render Dedicated Pending Table
        if (pendingTbody) {
            const pendingList = allList.filter(j => j.status === 'PENDING');
            if (pendingList.length === 0) {
                pendingTbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="empty-state" style="padding: 2rem;">
                            <i class="fas fa-check-circle" style="color: var(--success); font-size: 1.5rem; margin-bottom: 0.5rem;"></i>
                            <p style="margin: 0;">No job postings currently awaiting approval.</p>
                        </td>
                    </tr>
                `;
            } else {
                pendingTbody.innerHTML = pendingList.map(j => `
                    <tr>
                        <td>
                            <strong>${j.title}</strong>
                            <div style="font-size: 0.825rem; color: var(--text-secondary); margin-top: 0.25rem; max-width: 280px; white-space: normal; line-height: 1.35;">
                                ${j.description ? j.description.substring(0, 110) + '...' : 'No description'}
                            </div>
                        </td>
                        <td>
                            <strong>${j.company_name}</strong>
                            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">
                                <div><i class="fas fa-user-tie"></i> ${j.recruiter_name}</div>
                                <div><i class="fas fa-envelope"></i> ${j.recruiter_email || 'N/A'}</div>
                                ${j.recruiter_phone ? `<div><i class="fas fa-phone"></i> ${j.recruiter_phone}</div>` : ''}
                            </div>
                        </td>
                        <td>
                            <div><strong>Min CGPA:</strong> ${j.min_cgpa}</div>
                            <div style="font-size: 0.775rem; color: var(--text-secondary); margin-top: 0.2rem; max-width: 180px;">
                                ${j.eligible_branches || 'All Branches Eligible'}
                            </div>
                        </td>
                        <td>
                            <div style="display: flex; flex-wrap: wrap; gap: 0.25rem; max-width: 220px;">
                                ${j.required_skills ? j.required_skills.split(', ').map(s => `<span class="badge badge-neutral" style="font-size: 0.725rem;">${s}</span>`).join('') : '<span style="color: var(--text-muted); font-size: 0.8rem;">None specified</span>'}
                            </div>
                        </td>
                        <td>
                            <strong style="color: var(--success);">${formatCurrency(j.salary_stipend)}</strong>
                            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem;">
                                ${j.location} (${j.work_mode}) &bull; ${j.job_type}
                            </div>
                        </td>
                        <td>
                            <span style="font-size: 0.85rem; font-weight: 600;">${formatDate(j.deadline)}</span>
                        </td>
                        <td>
                            <div style="display: flex; gap: 0.35rem; flex-direction: column;">
                                <button class="btn btn-success btn-sm" onclick="handleJobApproval(${j.job_id}, 'approve')" style="white-space: nowrap;">
                                    <i class="fas fa-check"></i> Approve Job
                                </button>
                                <button class="btn btn-outline btn-sm" onclick="handleJobApproval(${j.job_id}, 'reject')" style="color: var(--danger); border-color: var(--danger); white-space: nowrap;">
                                    <i class="fas fa-times"></i> Reject Job
                                </button>
                            </div>
                        </td>
                    </tr>
                `).join('');
            }
        }

        // 2. Render All Jobs Table (Filtered)
        if (allTbody) {
            let filteredList = allList;
            if (statusFilter === 'APPROVED') {
                filteredList = allList.filter(j => j.status === 'APPROVED' || j.status === 'LIVE');
            } else if (statusFilter) {
                filteredList = allList.filter(j => j.status === statusFilter);
            }

            if (filteredList.length === 0) {
                allTbody.innerHTML = `<tr><td colspan="8" class="empty-state">No jobs found for selected filter.</td></tr>`;
                return;
            }

            allTbody.innerHTML = filteredList.map(j => {
                let statusBadge = '<span class="status-pill status-pending"><i class="fas fa-clock"></i> Pending Approval</span>';
                if (j.status === 'APPROVED' || j.status === 'LIVE') {
                    statusBadge = '<span class="status-pill status-selected"><i class="fas fa-check-circle"></i> Approved</span>';
                } else if (j.status === 'REJECTED') {
                    statusBadge = '<span class="status-pill status-rejected"><i class="fas fa-times-circle"></i> Rejected</span>';
                } else if (j.status === 'CLOSED') {
                    statusBadge = '<span class="status-pill status-closed"><i class="fas fa-ban"></i> Closed</span>';
                }

                let actionHtml = '';
                if (j.status === 'PENDING') {
                    actionHtml = `
                        <div style="display: flex; gap: 0.35rem;">
                            <button class="btn btn-success btn-sm" onclick="handleJobApproval(${j.job_id}, 'approve')">Approve</button>
                            <button class="btn btn-outline btn-sm" onclick="handleJobApproval(${j.job_id}, 'reject')" style="color: var(--danger); border-color: var(--danger);">Reject</button>
                        </div>
                    `;
                } else if (j.status === 'APPROVED' || j.status === 'LIVE') {
                    actionHtml = `
                        <button class="btn btn-outline btn-sm" onclick="handleJobApproval(${j.job_id}, 'close')" style="color: var(--text-muted);" title="Close Listing">
                            <i class="fas fa-ban"></i> Close
                        </button>
                    `;
                } else if (j.status === 'REJECTED') {
                    actionHtml = `
                        <button class="btn btn-outline btn-sm" onclick="handleJobApproval(${j.job_id}, 'approve')" style="color: var(--success); border-color: var(--success);" title="Re-Approve Job">
                            <i class="fas fa-redo"></i> Re-Approve
                        </button>
                    `;
                }

                return `
                    <tr>
                        <td><strong>${j.title}</strong><br><small style="color: var(--text-muted);">${j.company_name}</small></td>
                        <td><span class="badge badge-primary">${j.job_type}</span></td>
                        <td>${j.location}</td>
                        <td>${formatCurrency(j.salary_stipend)}</td>
                        <td>Min ${j.min_cgpa}</td>
                        <td>${statusBadge}</td>
                        <td>${j.applicant_count || 0}</td>
                        <td>${actionHtml}</td>
                    </tr>
                `;
            }).join('');
        }

    } catch (err) {
        if (allTbody) allTbody.innerHTML = `<tr><td colspan="8">Error loading jobs: ${err.message}</td></tr>`;
        showToast('error', 'Jobs Error', err.message);
    }
}

// Students Directory Table
async function loadAdminStudentsTable() {
    const tbody = document.getElementById('all-students-tbody');
    if (!tbody) return;

    const deptId = document.getElementById('filter-dept')?.value || '';
    const status = document.getElementById('filter-placement-status')?.value || '';
    const query = document.getElementById('search-student')?.value.trim() || '';

    try {
        const res = await apiRequest(`admin/students.php?dept_id=${deptId}&status=${status}&q=${encodeURIComponent(query)}`);
        
        // Populate departments filter if needed
        const deptSelect = document.getElementById('filter-dept');
        if (deptSelect && deptSelect.options.length <= 1 && res.departments) {
            res.departments.forEach(d => {
                const opt = document.createElement('option');
                opt.value = d.dept_id;
                opt.textContent = d.dept_name;
                deptSelect.appendChild(opt);
            });
        }

        if (res.students.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="empty-state">No students found matching filters.</td></tr>';
            return;
        }

        tbody.innerHTML = res.students.map(s => `
            <tr>
                <td><strong>${s.full_name}</strong><br><small style="color: var(--text-muted);">${s.email}</small></td>
                <td>${s.roll_number}</td>
                <td>${s.dept_code} - ${s.branch_code}</td>
                <td><strong>${s.cgpa}</strong></td>
                <td>${s.total_skills} skills</td>
                <td>${s.total_applications} applied</td>
                <td>
                    <span class="status-pill ${s.placement_status === 'PLACED' ? 'status-selected' : 'status-withdrawn'}">
                        ${s.placement_status}
                    </span>
                </td>
                <td>
                    <button class="btn btn-outline btn-sm" onclick="viewStudentModal(${s.student_id})">
                        <i class="fas fa-id-badge"></i> Profile
                    </button>
                </td>
            </tr>
        `).join('');

    } catch (err) {
        showToast('error', 'Students Error', err.message);
    }
}

// Student Modal Inspector
async function viewStudentModal(studentId) {
    try {
        const res = await apiRequest(`admin/students.php?id=${studentId}`);
        const s = res.student;

        document.getElementById('admin-modal-student-name').textContent = s.full_name;
        document.getElementById('admin-modal-roll').textContent = s.roll_number;
        document.getElementById('admin-modal-dept').textContent = `${s.dept_name} (${s.branch_code})`;
        document.getElementById('admin-modal-cgpa').textContent = s.cgpa;
        document.getElementById('admin-modal-email').textContent = s.email;
        document.getElementById('admin-modal-phone').textContent = s.phone || 'N/A';
        document.getElementById('admin-modal-status').textContent = s.placement_status;

        // Skills
        const skillsBox = document.getElementById('admin-modal-skills');
        skillsBox.innerHTML = (s.skills && s.skills.length > 0) ?
            s.skills.map(sk => `<span class="tag-chip">${sk.skill_name}</span>`).join('') :
            '<span style="color: var(--text-muted);">None recorded</span>';

        openModal('student-details-modal');
    } catch (err) {
        showToast('error', 'Modal Error', err.message);
    }
}

// Dynamic CSV Reports Export Trigger
async function downloadCsvReport(type) {
    const cleanType = (type || 'placements').toLowerCase();
    const token = localStorage.getItem('careerbridge_token_ADMIN') || localStorage.getItem('careerbridge_token');
    const baseUrl = getApiBaseUrl();

    if (typeof showToast === 'function') {
        showToast('info', 'Exporting Report', `Generating ${cleanType} CSV report...`);
    }

    try {
        const headers = {};
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }

        const res = await fetch(`${baseUrl}admin/reports.php?type=${encodeURIComponent(cleanType)}&format=csv`, {
            method: 'GET',
            headers: headers,
            credentials: 'include'
        });

        if (!res.ok) {
            let errorMsg = 'Failed to generate report.';
            try {
                const errData = await res.json();
                if (errData && errData.error) errorMsg = errData.error;
            } catch (e) {}
            if (typeof showToast === 'function') {
                showToast('error', 'Export Failed', errorMsg);
            } else {
                alert(errorMsg);
            }
            return;
        }

        const blob = await res.blob();
        const blobUrl = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = blobUrl;
        const dateStr = new Date().toISOString().slice(0, 10);
        a.download = `${cleanType}_report_${dateStr}.csv`;
        document.body.appendChild(a);
        a.click();
        a.remove();
        setTimeout(() => window.URL.revokeObjectURL(blobUrl), 1000);

        if (typeof showToast === 'function') {
            showToast('success', 'Export Complete', `${cleanType.toUpperCase()} CSV report downloaded successfully.`);
        }
    } catch (err) {
        console.error('Report export fetch failed, attempting token URL fallback:', err);
        let fallbackUrl = `${baseUrl}admin/reports.php?type=${encodeURIComponent(cleanType)}&format=csv`;
        if (token) {
            fallbackUrl += `&token=${encodeURIComponent(token)}`;
        }
        window.location.href = fallbackUrl;
    }
}
