/**
 * Recruiter Portal Client Controller
 * Job Postings, Candidate Review, Interview Scheduling, Selection Workflow
 */

async function initRecruiterView() {
    let currentUser = {
        name: 'Google Recruiter',
        email: 'recruiter.google@campus.com',
        role: 'RECRUITER',
        entity_id: 1,
        company_id: 1
    };

    try {
        let auth = await apiRequest('auth/me.php');
        if (auth && auth.authenticated && auth.user?.role === 'RECRUITER') {
            currentUser = auth.user;
            localStorage.setItem('careerbridge_user', JSON.stringify(currentUser));
        } else {
            const auto = await apiRequest('auth/auto_login.php?role=RECRUITER');
            if (auto && auto.success && auto.user) {
                currentUser = auto.user;
                if (auto.token) {
                    localStorage.setItem('careerbridge_token', auto.token);
                    localStorage.setItem('careerbridge_token_RECRUITER', auto.token);
                    localStorage.setItem('careerbridge_user', JSON.stringify(auto.user));
                }
            }
        }
    } catch (e) {
        console.warn('Recruiter auth auto-sync (defaulting to Google Recruiter):', e);
    }

    const nameEls = document.querySelectorAll('.recruiter-display-name');
    nameEls.forEach(el => el.textContent = currentUser.name);

    renderRecruiterVerificationStatus(currentUser);

    try {
        loadNotificationCount();
    } catch (notifErr) {
        // ignore
    }
    return true;
}

// Render Recruiter Verification Status Alerts & Guard Controls
function renderRecruiterVerificationStatus(user) {
    const status = (user.approval_status || 'APPROVED').toUpperCase();
    const alertContainer = document.getElementById('recruiter-status-alert');
    const postJobAlert = document.getElementById('post-job-approval-alert');
    const topbarPostBtn = document.getElementById('recruiter-topbar-post-btn');

    if (status === 'PENDING') {
        if (alertContainer) {
            alertContainer.innerHTML = `
                <div class="alert alert-warning" style="background: #fffbeb; border: 1px solid #fde68a; border-left: 4px solid #f59e0b; padding: 1.25rem 1.5rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
                    <div style="display: flex; align-items: flex-start; gap: 1rem;">
                        <div style="font-size: 1.75rem; color: #d97706; line-height: 1;"><i class="fas fa-hourglass-half"></i></div>
                        <div>
                            <h4 style="margin: 0 0 0.25rem 0; color: #92400e; font-weight: 700; font-size: 1.05rem;">
                                Your recruiter account is waiting for admin approval.
                            </h4>
                            <p style="margin: 0; color: #b45309; font-size: 0.925rem;">
                                Your company profile is currently under review by the Training & Placement Cell. You can view and update your <a href="profile.html" style="font-weight: 600; text-decoration: underline;">Company Profile</a>, but job publishing features remain locked until verified.
                            </p>
                        </div>
                    </div>
                    <span class="badge" style="background: #fef3c7; color: #92400e; font-weight: 700; padding: 0.45rem 0.85rem; border: 1px solid #fde68a;">
                        <i class="fas fa-clock"></i> PENDING APPROVAL
                    </span>
                </div>
            `;
        }

        if (postJobAlert) {
            postJobAlert.innerHTML = `
                <div class="alert alert-warning" style="background: #fffbeb; border: 1px solid #fde68a; border-left: 4px solid #f59e0b; padding: 1.25rem 1.5rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; display: flex; align-items: flex-start; gap: 1rem;">
                    <div style="font-size: 1.75rem; color: #d97706; line-height: 1;"><i class="fas fa-lock"></i></div>
                    <div>
                        <h4 style="margin: 0 0 0.25rem 0; color: #92400e; font-weight: 700; font-size: 1.05rem;">
                            Your recruiter account is waiting for admin approval.
                        </h4>
                        <p style="margin: 0; color: #b45309; font-size: 0.925rem;">
                            You cannot post jobs until your recruiter profile and company have been reviewed and approved by the Training & Placement Cell.
                        </p>
                    </div>
                </div>
            `;
            const postForm = document.getElementById('post-job-form');
            if (postForm) {
                const elements = postForm.querySelectorAll('input, select, textarea, button');
                elements.forEach(el => el.disabled = true);
                const submitBtn = document.getElementById('submit-job-btn');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-lock"></i> Waiting for Admin Approval';
                }
            }
        }

        if (topbarPostBtn) {
            topbarPostBtn.classList.remove('btn-primary');
            topbarPostBtn.classList.add('btn-outline');
            topbarPostBtn.style.opacity = '0.65';
            topbarPostBtn.style.cursor = 'not-allowed';
            topbarPostBtn.innerHTML = '<i class="fas fa-lock"></i> Post Opportunity (Pending)';
            topbarPostBtn.removeAttribute('href');
            topbarPostBtn.onclick = (e) => {
                e.preventDefault();
                showToast('warning', 'Approval Required', 'Your recruiter account is waiting for admin approval before you can post jobs.');
            };
        }

        const otherPostLinks = document.querySelectorAll('a[href="post-job.html"]');
        otherPostLinks.forEach(link => {
            if (link !== topbarPostBtn) {
                link.style.opacity = '0.65';
                link.innerHTML = '<i class="fas fa-lock"></i> Post New Job (Pending)';
                link.onclick = (e) => {
                    e.preventDefault();
                    showToast('warning', 'Approval Required', 'Your recruiter account is waiting for admin approval before you can post jobs.');
                };
            }
        });
    } else if (status === 'REJECTED') {
        if (alertContainer) {
            alertContainer.innerHTML = `
                <div class="alert alert-danger" style="background: #fef2f2; border: 1px solid #fecaca; border-left: 4px solid #ef4444; padding: 1.25rem 1.5rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; display: flex; align-items: flex-start; gap: 1rem;">
                    <div style="font-size: 1.75rem; color: #dc2626; line-height: 1;"><i class="fas fa-times-circle"></i></div>
                    <div>
                        <h4 style="margin: 0 0 0.25rem 0; color: #991b1b; font-weight: 700; font-size: 1.05rem;">
                            Registration Not Approved
                        </h4>
                        <p style="margin: 0; color: #b91c1c; font-size: 0.925rem;">
                            Your recruiter registration was not approved by the Training & Placement Cell. Please verify your company credentials or contact the T&P Cell for assistance.
                        </p>
                    </div>
                </div>
            `;
        }

        if (postJobAlert) {
            postJobAlert.innerHTML = `
                <div class="alert alert-danger" style="background: #fef2f2; border: 1px solid #fecaca; border-left: 4px solid #ef4444; padding: 1.25rem 1.5rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; display: flex; align-items: flex-start; gap: 1rem;">
                    <div style="font-size: 1.75rem; color: #dc2626; line-height: 1;"><i class="fas fa-ban"></i></div>
                    <div>
                        <h4 style="margin: 0 0 0.25rem 0; color: #991b1b; font-weight: 700; font-size: 1.05rem;">
                            Account Not Authorized
                        </h4>
                        <p style="margin: 0; color: #b91c1c; font-size: 0.925rem;">
                            This recruiter account was rejected by the Training & Placement Cell. You cannot create job listings.
                        </p>
                    </div>
                </div>
            `;
            const postForm = document.getElementById('post-job-form');
            if (postForm) {
                const elements = postForm.querySelectorAll('input, select, textarea, button');
                elements.forEach(el => el.disabled = true);
            }
        }

        if (topbarPostBtn) {
            topbarPostBtn.style.display = 'none';
        }

        const otherPostLinks = document.querySelectorAll('a[href="post-job.html"]');
        otherPostLinks.forEach(link => {
            link.style.display = 'none';
        });
    }
}

// Notification Badge Counter for Recruiter
async function loadNotificationCount() {
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
        console.error('Failed to load recruiter notifications count', e);
    }
}

// Recruiter Dashboard Loader
async function loadRecruiterDashboard() {
    try {
        const stats = await apiRequest('analytics/dashboard_stats.php');
        const rStats = stats.role_specific || {};

        document.getElementById('kpi-active-jobs').textContent = rStats.active_jobs || 0;
        document.getElementById('kpi-total-applicants').textContent = rStats.total_applicants || 0;
        document.getElementById('kpi-shortlisted-candidates').textContent = rStats.shortlisted || 0;
        document.getElementById('kpi-scheduled-interviews').textContent = rStats.interviews || 0;
        document.getElementById('kpi-selected-hires').textContent = rStats.selected_hires || 0;

        // Load active jobs preview
        loadRecruiterJobsPreview();
    } catch (err) {
        showToast('error', 'Dashboard Error', err.message);
    }
}

// Jobs list preview
async function loadRecruiterJobsPreview() {
    const tbody = document.getElementById('dash-recruiter-jobs-tbody');
    if (!tbody) return;

    try {
        const res = await apiRequest('recruiter/jobs.php');
        const jobs = res.jobs.slice(0, 5);

        if (jobs.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="empty-state">No job postings created yet.</td></tr>';
            return;
        }

        tbody.innerHTML = jobs.map(j => `
            <tr>
                <td><strong>${j.title}</strong></td>
                <td><span class="badge badge-primary">${j.job_type}</span></td>
                <td>${formatCurrency(j.salary_stipend)}</td>
                <td><span class="status-pill status-${j.status.toLowerCase()}">${j.status}</span></td>
                <td>${j.total_applicants || 0} applicants</td>
            </tr>
        `).join('');
    } catch (e) {
        tbody.innerHTML = '<tr><td colspan="5">Error loading jobs.</td></tr>';
    }
}

// Post Job Page Loader (Branches and Skills Checkboxes)
async function loadPostJobFormData() {
    const skillsContainer = document.getElementById('skills-checkboxes');
    const branchesContainer = document.getElementById('branches-checkboxes');
    if (!skillsContainer || !branchesContainer) return;

    try {
        let res = null;
        try {
            res = await apiRequest('recruiter/jobs.php?meta_only=1');
        } catch (e) {
            console.warn('Could not load meta via recruiter/jobs.php, falling back to master_data.php', e);
        }

        if (!res || !res.skills || !res.branches) {
            res = await apiRequest('api/master_data.php');
        }

        if (res && res.skills && res.branches) {
            // Populate Skills
            skillsContainer.innerHTML = res.skills.map(s => `
                <label style="display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.85rem; margin-right: 1rem; margin-bottom: 0.5rem; cursor: pointer;">
                    <input type="checkbox" name="skills[]" value="${s.skill_id}">
                    ${s.skill_name}
                </label>
            `).join('');

            // Populate Branches
            branchesContainer.innerHTML = res.branches.map(b => `
                <label style="display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.85rem; margin-right: 1rem; margin-bottom: 0.5rem; cursor: pointer;">
                    <input type="checkbox" name="branches[]" value="${b.branch_id}">
                    ${b.branch_code}
                </label>
            `).join('');
        }
    } catch (err) {
        showToast('error', 'Form Error', err.message);
    }
}

async function handlePostJobSubmit(e) {
    e.preventDefault();
    const btn = document.getElementById('submit-job-btn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';

    const selectedSkills = Array.from(document.querySelectorAll('input[name="skills[]"]:checked')).map(el => parseInt(el.value));
    const selectedBranches = Array.from(document.querySelectorAll('input[name="branches[]"]:checked')).map(el => parseInt(el.value));

    const payload = {
        action: 'create',
        title: document.getElementById('job-title').value.trim(),
        description: document.getElementById('job-desc').value.trim(),
        job_type: document.getElementById('job-type').value,
        work_mode: document.getElementById('job-mode').value,
        location: document.getElementById('job-location').value.trim(),
        min_cgpa: document.getElementById('job-min-cgpa').value,
        salary_stipend: document.getElementById('job-salary').value,
        experience_req: document.getElementById('job-exp').value.trim(),
        deadline: document.getElementById('job-deadline').value,
        openings_count: document.getElementById('job-openings').value,
        skills: selectedSkills,
        branches: selectedBranches
    };

    try {
        const res = await apiRequest('recruiter/jobs.php', {
            method: 'POST',
            body: JSON.stringify(payload)
        });
        showToast('success', 'Job Created', res.message);
        setTimeout(() => window.location.href = 'manage-jobs.html', 1200);
    } catch (err) {
        showToast('error', 'Submission Failed', err.message);
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane"></i> Post Opportunity';
    }
}

// Manage Jobs Full Table
async function loadManageJobsTable() {
    const tbody = document.getElementById('manage-jobs-tbody');
    if (!tbody) return;

    try {
        const res = await apiRequest('recruiter/jobs.php');
        if (res.jobs.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="empty-state">No job postings created. Click "Post New Job".</td></tr>';
            return;
        }

        tbody.innerHTML = res.jobs.map(j => `
            <tr>
                <td><strong>${j.title}</strong></td>
                <td><span class="badge badge-primary">${j.job_type}</span></td>
                <td>${j.location} (${j.work_mode})</td>
                <td>${formatCurrency(j.salary_stipend)}</td>
                <td>${formatDate(j.deadline)}</td>
                <td><span class="status-pill status-${j.status.toLowerCase()}">${j.status}</span></td>
                <td>
                    <div style="display: flex; gap: 0.5rem;">
                        <a href="applicants.html?job_id=${j.job_id}" class="btn btn-primary btn-sm" title="View Applicants">
                            <i class="fas fa-users"></i> ${j.total_applicants || 0}
                        </a>
                        ${j.status === 'LIVE' ? `
                            <button class="btn btn-outline btn-sm" onclick="closeJob(${j.job_id})" title="Close Listing" style="color: var(--danger);">
                                <i class="fas fa-ban"></i>
                            </button>
                        ` : ''}
                    </div>
                </td>
            </tr>
        `).join('');
    } catch (err) {
        showToast('error', 'Jobs Error', err.message);
    }
}

async function closeJob(jobId) {
    if (!confirm('Are you sure you want to close this job listing? No new applications will be permitted.')) return;
    try {
        await apiRequest('recruiter/jobs.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'close', job_id: jobId })
        });
        showToast('success', 'Job Closed', 'Job listing marked as CLOSED.');
        loadManageJobsTable();
    } catch (err) {
        showToast('error', 'Failed', err.message);
    }
}

// Applicants Management Table
async function loadRecruiterApplicants() {
    const tbody = document.getElementById('applicants-tbody');
    if (!tbody) return;

    const urlParams = new URLSearchParams(window.location.search);
    const jobId = document.getElementById('filter-job')?.value || urlParams.get('job_id') || '';
    const status = document.getElementById('filter-status')?.value || urlParams.get('status') || '';
    const minCgpa = document.getElementById('filter-cgpa')?.value || '';
    const query = document.getElementById('search-applicant')?.value.trim() || '';

    try {
        const res = await apiRequest(`recruiter/applicants.php?job_id=${jobId}&status=${status}&min_cgpa=${minCgpa}&q=${encodeURIComponent(query)}`);
        
        // Populate filter job dropdown if empty
        const jobSelect = document.getElementById('filter-job');
        if (jobSelect && jobSelect.options.length <= 1 && res.jobs) {
            res.jobs.forEach(j => {
                const opt = document.createElement('option');
                opt.value = j.job_id;
                opt.textContent = j.title;
                if (j.job_id == jobId) opt.selected = true;
                jobSelect.appendChild(opt);
            });
        }

        if (res.applicants.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="empty-state">No matching applicants found.</td></tr>';
            return;
        }

        tbody.innerHTML = res.applicants.map(a => `
            <tr>
                <td><strong>${a.student_name}</strong><br><small style="color: var(--text-muted);">${a.roll_number}</small></td>
                <td>${a.job_title}</td>
                <td>${a.branch_code}</td>
                <td><strong>${a.cgpa}</strong></td>
                <td><span class="status-pill status-${a.application_status.toLowerCase()}">${a.application_status.replace('_', ' ')}</span></td>
                <td>${formatDate(a.applied_at)}</td>
                <td>
                    <div style="display: flex; gap: 0.4rem;">
                        <button class="btn btn-outline btn-sm" onclick="viewApplicantModal(${a.student_id})" title="View Profile">
                            <i class="fas fa-eye"></i> View
                        </button>
                        ${(a.application_status !== 'SELECTED' && a.application_status !== 'REJECTED' && a.application_status !== 'WITHDRAWN') ? `
                            <button class="btn btn-primary btn-sm" onclick="openScheduleModal(${a.application_id}, '${a.student_name.replace(/'/g, "\\'")}')" title="Schedule Interview">
                                <i class="fas fa-calendar-plus"></i>
                            </button>
                            <button class="btn btn-success btn-sm" onclick="openSelectModal(${a.application_id}, '${a.student_name.replace(/'/g, "\\'")}', ${parseFloat(a.salary_stipend || 0)})" title="Select / Offer">
                                <i class="fas fa-check"></i>
                            </button>
                            <button class="btn btn-outline btn-sm" onclick="updateAppStatus(${a.application_id}, 'REJECTED')" title="Reject" style="color: var(--danger);">
                                <i class="fas fa-times"></i>
                            </button>
                        ` : ''}
                    </div>
                </td>
            </tr>
        `).join('');

    } catch (err) {
        showToast('error', 'Applicants Error', err.message);
    }
}

// View Detailed Applicant Modal
async function viewApplicantModal(studentId) {
    try {
        const res = await apiRequest(`recruiter/applicants.php?student_id=${studentId}`);
        const s = res.student;

        document.getElementById('modal-applicant-name').textContent = `${s.first_name} ${s.last_name}`;
        document.getElementById('modal-applicant-email').textContent = s.email;
        document.getElementById('modal-applicant-phone').textContent = s.phone || 'N/A';
        document.getElementById('modal-applicant-roll').textContent = s.roll_number;
        document.getElementById('modal-applicant-branch').textContent = `${s.branch_name} (${s.branch_code})`;
        document.getElementById('modal-applicant-cgpa').textContent = s.cgpa;

        // Resume button
        const resumeBtn = document.getElementById('modal-applicant-resume');
        if (s.resume_path) {
            resumeBtn.href = `../${s.resume_path}`;
            resumeBtn.style.display = 'inline-flex';
        } else {
            resumeBtn.style.display = 'none';
        }

        // Skills
        const skillsBox = document.getElementById('modal-applicant-skills');
        skillsBox.innerHTML = (s.skills && s.skills.length > 0) ?
            s.skills.map(sk => `<span class="tag-chip">${sk.skill_name} (${sk.proficiency_level})</span>`).join('') :
            '<p style="color: var(--text-muted); font-size: 0.85rem;">No skills listed.</p>';

        // Projects
        const projBox = document.getElementById('modal-applicant-projects');
        projBox.innerHTML = (s.projects && s.projects.length > 0) ?
            s.projects.map(p => `
                <div style="margin-bottom: 0.75rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.5rem;">
                    <strong>${p.title}</strong> - <small style="color: var(--text-muted);">${p.technologies}</small>
                    <p style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 0.2rem;">${p.description || ''}</p>
                </div>
            `).join('') :
            '<p style="color: var(--text-muted); font-size: 0.85rem;">No projects listed.</p>';

        openModal('applicant-details-modal');
    } catch (err) {
        showToast('error', 'Profile Error', err.message);
    }
}

// Quick Status Update
async function updateAppStatus(appId, newStatus) {
    if (!confirm(`Are you sure you want to change this applicant's status to ${newStatus}?`)) return;

    try {
        const res = await apiRequest('recruiter/status_update.php', {
            method: 'POST',
            body: JSON.stringify({ application_id: appId, status: newStatus })
        });
        showToast('success', 'Status Updated', res.message);
        loadRecruiterApplicants();
    } catch (err) {
        showToast('error', 'Update Failed', err.message);
    }
}

// Candidate Selection Modal & Procedure Transaction
function openSelectModal(appId, studentName, salaryStipend) {
    document.getElementById('select-app-id').value = appId;
    document.getElementById('select-student-name').textContent = studentName;
    const lpa = (salaryStipend >= 100000) ? (salaryStipend / 100000).toFixed(2) : (salaryStipend > 0 ? salaryStipend : 8.50);
    document.getElementById('select-package-lpa').value = lpa;
    openModal('select-candidate-modal');
}

async function confirmCandidateSelection(e) {
    e.preventDefault();
    const appId = document.getElementById('select-app-id').value;
    const packageLpa = document.getElementById('select-package-lpa').value;

    try {
        const res = await apiRequest('recruiter/status_update.php', {
            method: 'POST',
            body: JSON.stringify({
                application_id: appId,
                status: 'SELECTED',
                package_lpa: packageLpa
            })
        });

        showToast('success', 'Candidate Selected', res.message);
        closeModal('select-candidate-modal');
        loadRecruiterApplicants();
    } catch (err) {
        showToast('error', 'Selection Failed', err.message);
    }
}

// Schedule Interview Modal
function openScheduleModal(appId, studentName) {
    document.getElementById('sched-app-id').value = appId;
    document.getElementById('sched-student-name').textContent = studentName;
    openModal('schedule-interview-modal');
}

async function handleScheduleInterview(e) {
    e.preventDefault();
    const payload = {
        action: 'schedule',
        application_id: document.getElementById('sched-app-id').value,
        round_name: document.getElementById('sched-round-name').value.trim(),
        scheduled_at: document.getElementById('sched-datetime').value,
        meeting_link_location: document.getElementById('sched-link').value.trim()
    };

    try {
        const res = await apiRequest('recruiter/interviews.php', {
            method: 'POST',
            body: JSON.stringify(payload)
        });
        showToast('success', 'Interview Scheduled', res.message);
        closeModal('schedule-interview-modal');
        loadRecruiterApplicants();
    } catch (err) {
        showToast('error', 'Scheduling Failed', err.message);
    }
}
