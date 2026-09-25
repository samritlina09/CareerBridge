/**
 * Job Marketplace Client Controller (Search, Filter, Card Render & Application)
 */

async function loadJobCatalog(isStudentView = true) {
    const container = document.getElementById('jobs-catalog-container');
    if (!container) return;

    const query = document.getElementById('job-search-input')?.value.trim() || '';
    const type = document.getElementById('filter-job-type')?.value || '';
    const mode = document.getElementById('filter-work-mode')?.value || '';
    const eligibleOnly = document.getElementById('filter-eligible-only')?.checked ? '1' : '';

    const endpoint = isStudentView ? 
        `student/jobs.php?q=${encodeURIComponent(query)}&type=${type}&mode=${mode}&eligible_only=${eligibleOnly}` :
        `student/jobs.php?q=${encodeURIComponent(query)}&type=${type}&mode=${mode}`;

    container.innerHTML = '<div class="empty-state"><i class="fas fa-spinner fa-spin"></i><p>Loading opportunities...</p></div>';

    try {
        const res = await apiRequest(endpoint);
        const jobs = res.jobs || [];

        const countBadge = document.getElementById('total-jobs-counter');
        if (countBadge) countBadge.textContent = `${jobs.length} Opportunities`;

        if (jobs.length === 0) {
            const hasFilter = query || type || mode || eligibleOnly;
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-briefcase"></i>
                    <h3>${hasFilter ? 'No Opportunities Found' : 'No jobs available yet.'}</h3>
                    <p>${hasFilter ? 'Try adjusting your search criteria or filters.' : 'Check back soon for new campus recruitment postings.'}</p>
                </div>`;
            return;
        }

        container.innerHTML = jobs.map(j => `
            <div class="card" style="margin-bottom: 1.5rem; transition: transform 0.2s, box-shadow 0.2s;">
                <div class="card-body">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem;">
                        <div>
                            <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--text-primary); margin-bottom: 0.25rem;">
                                ${j.title}
                            </h3>
                            <div style="font-size: 0.925rem; color: var(--text-secondary); display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                                <span><i class="fas fa-building" style="color: var(--primary);"></i> <strong>${j.company_name}</strong></span>
                                <span>&bull;</span>
                                <span><i class="fas fa-map-marker-alt"></i> ${j.location} (${j.work_mode})</span>
                                <span>&bull;</span>
                                <span style="font-weight: 700; color: var(--success);">${formatCurrency(j.salary_stipend)}</span>
                            </div>
                        </div>
                        <div>
                            <span class="badge badge-primary" style="font-size: 0.8rem; padding: 0.35rem 0.75rem;">${j.job_type}</span>
                            ${j.my_application_status ? `
                                <span class="status-pill status-${j.my_application_status.toLowerCase()}" style="margin-left: 0.5rem;">
                                    ${j.my_application_status.replace('_', ' ')}
                                </span>
                            ` : ''}
                        </div>
                    </div>

                    ${j.skills_list ? `
                        <div style="margin-bottom: 1rem;">
                            <span style="font-size: 0.775rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Key Skills:</span>
                            <div style="display: flex; flex-wrap: wrap; gap: 0.4rem; margin-top: 0.25rem;">
                                ${j.skills_list.split(', ').map(sk => `<span class="badge badge-neutral">${sk}</span>`).join('')}
                            </div>
                        </div>
                    ` : ''}

                    <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f1f5f9; padding-top: 1rem; margin-top: 0.5rem;">
                        <span style="font-size: 0.825rem; color: var(--text-muted);">
                            <i class="fas fa-calendar-alt"></i> Apply before: <strong>${formatDate(j.deadline)}</strong> &bull; Min CGPA: <strong>${j.min_cgpa}</strong>
                        </span>
                        <div style="display: flex; gap: 0.75rem;">
                            <a href="job-details.html?id=${j.job_id}" class="btn btn-outline btn-sm">View Details</a>
                            ${isStudentView ? `
                                <button class="btn btn-primary btn-sm" onclick="applyToJob(${j.job_id})" ${j.my_application_status ? 'disabled' : ''}>
                                    ${j.my_application_status ? 'Applied' : '<i class="fas fa-paper-plane"></i> Apply'}
                                </button>
                            ` : `
                                <a href="login.html" class="btn btn-primary btn-sm">Login to Apply</a>
                            `}
                        </div>
                    </div>
                </div>
            </div>
        `).join('');

    } catch (err) {
        container.innerHTML = `<div class="empty-state"><p>Error loading opportunities: ${err.message}</p></div>`;
    }
}

// Single Job Details Page Loader
async function loadJobDetailsPage() {
    const urlParams = new URLSearchParams(window.location.search);
    const jobId = urlParams.get('id');
    if (!jobId) {
        window.location.href = 'jobs.html';
        return;
    }

    try {
        const res = await apiRequest(`student/jobs.php?id=${jobId}`);
        const j = res.job;

        document.getElementById('job-detail-title').textContent = j.title;
        document.getElementById('job-detail-company').textContent = j.company_name;
        document.getElementById('job-detail-location').textContent = `${j.location} (${j.work_mode})`;
        document.getElementById('job-detail-salary').textContent = formatCurrency(j.salary_stipend);
        document.getElementById('job-detail-deadline').textContent = formatDate(j.deadline);
        document.getElementById('job-detail-type').textContent = j.job_type;
        document.getElementById('job-detail-min-cgpa').textContent = j.min_cgpa;
        document.getElementById('job-detail-openings').textContent = j.openings_count;
        document.getElementById('job-detail-desc').textContent = j.description;

        // Company Details Box
        const compDesc = document.getElementById('job-detail-company-desc');
        if (compDesc) compDesc.textContent = j.company_desc || 'Leading technology enterprise.';
        const compSite = document.getElementById('job-detail-company-website');
        if (compSite && j.website) {
            compSite.href = j.website;
            compSite.textContent = j.website;
        }

        // Skills Required
        const skillsContainer = document.getElementById('job-detail-skills');
        if (skillsContainer) {
            skillsContainer.innerHTML = (j.skills && j.skills.length > 0) ?
                j.skills.map(s => `
                    <span class="tag-chip ${s.student_has_skill ? 'badge-success' : ''}" style="${s.student_has_skill ? 'background: #dcfce7; color: #166534;' : ''}">
                        ${s.student_has_skill ? '<i class="fas fa-check"></i> ' : ''}${s.skill_name} ${s.is_required ? '(Required)' : '(Optional)'}
                    </span>
                `).join('') : '<p>No specific skill prerequisites.</p>';
        }

        // Eligible Branches
        const branchesContainer = document.getElementById('job-detail-branches');
        if (branchesContainer) {
            branchesContainer.innerHTML = (j.branches && j.branches.length > 0) ?
                j.branches.map(b => `<span class="badge badge-primary">${b.branch_code}</span>`).join(' ') :
                '<span class="badge badge-success">All Engineering / MCA Branches Eligible</span>';
        }

        // Eligibility Banner & Apply Button
        const applyBtn = document.getElementById('job-detail-apply-btn');
        const eligBanner = document.getElementById('job-eligibility-banner');

        if (j.user_application_status) {
            applyBtn.disabled = true;
            applyBtn.textContent = `Applied (${j.user_application_status})`;
            if (eligBanner) {
                eligBanner.className = 'card';
                eligBanner.style.background = '#eff6ff';
                eligBanner.innerHTML = `<div class="card-body" style="color: #1e40af;"><i class="fas fa-info-circle"></i> You submitted your application on ${formatDate(j.applied_at)}. Current Status: <strong>${j.user_application_status}</strong>.</div>`;
            }
        } else if (!j.is_eligible) {
            applyBtn.disabled = true;
            applyBtn.textContent = 'Not Eligible';
            if (eligBanner) {
                eligBanner.className = 'card';
                eligBanner.style.background = '#fef2f2';
                let reason = !j.cgpa_eligible ? `Your CGPA is below the required ${j.min_cgpa}.` : 'Your branch is not listed as eligible for this role.';
                eligBanner.innerHTML = `<div class="card-body" style="color: #991b1b;"><i class="fas fa-exclamation-circle"></i> <strong>Eligibility Notice:</strong> ${reason}</div>`;
            }
        } else {
            applyBtn.disabled = false;
            applyBtn.onclick = () => applyToJob(jobId);
            if (eligBanner) {
                eligBanner.className = 'card';
                eligBanner.style.background = '#f0fdf4';
                eligBanner.innerHTML = `<div class="card-body" style="color: #166534;"><i class="fas fa-check-circle"></i> <strong>You are eligible to apply!</strong> Your academic qualifications match all requirements.</div>`;
            }
        }

    } catch (err) {
        showToast('error', 'Job Detail Error', err.message);
    }
}
