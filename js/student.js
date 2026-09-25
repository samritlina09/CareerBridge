/**
 * Student Portal Client Controller
 * Manages Profile, Skills, Projects, Certifications, Applications, and AI Recommendations
 */

// Initialize Student Session & Navigation
async function initStudentView() {
    let currentUser = {
        name: 'Aarav Sharma',
        email: 'aarav.sharma@student.campus.edu',
        role: 'STUDENT',
        entity_id: 1
    };

    try {
        let auth = await apiRequest('auth/me.php');
        if (auth && auth.authenticated && auth.user?.role === 'STUDENT') {
            currentUser = auth.user;
            localStorage.setItem('careerbridge_user', JSON.stringify(currentUser));
        } else {
            const auto = await apiRequest('auth/auto_login.php?role=STUDENT');
            if (auto && auto.success && auto.user) {
                currentUser = auto.user;
                if (auto.token) {
                    localStorage.setItem('careerbridge_token', auto.token);
                    localStorage.setItem('careerbridge_token_STUDENT', auto.token);
                    localStorage.setItem('careerbridge_user', JSON.stringify(auto.user));
                }
            }
        }
    } catch (e) {
        console.warn('Student auth auto-sync (defaulting to Aarav Sharma):', e);
    }

    // Set display name in sidebar & topbar
    const nameEls = document.querySelectorAll('.student-display-name');
    nameEls.forEach(el => el.textContent = currentUser.name);

    const emailEls = document.querySelectorAll('.student-display-email');
    emailEls.forEach(el => el.textContent = currentUser.email);

    try {
        loadNotificationCount();
    } catch (notifErr) {
        // ignore
    }
    return true;
}

// Notification Badge Counter
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
        console.error('Failed to load notifications count', e);
    }
}

// Student Dashboard Loader
async function loadStudentDashboard() {
    try {
        const stats = await apiRequest('analytics/dashboard_stats.php');
        const roleStats = stats.role_specific || {};

        document.getElementById('kpi-total-apps').textContent = roleStats.total_apps || 0;
        document.getElementById('kpi-under-review').textContent = roleStats.under_review || 0;
        document.getElementById('kpi-shortlisted').textContent = roleStats.shortlisted || 0;
        document.getElementById('kpi-interviews').textContent = roleStats.interviews || 0;
        document.getElementById('kpi-offers').textContent = roleStats.offers || 0;

        // Load profile completion
        const profRes = await apiRequest('student/profile.php');
        const completion = profRes.completion_percent || 50;
        const compVal = document.getElementById('profile-completion-val');
        const compBar = document.getElementById('profile-completion-bar');
        if (compVal) compVal.textContent = `${completion}%`;
        if (compBar) compBar.style.width = `${completion}%`;

        // Load recent recommendations preview
        loadRecommendationsPreview();

        // Load recent applications preview
        loadApplicationsPreview();

    } catch (err) {
        showToast('error', 'Dashboard Error', err.message);
    }
}

// Recommendations Preview on Dashboard
async function loadRecommendationsPreview() {
    const container = document.getElementById('dash-recommended-jobs');
    if (!container) return;

    try {
        const res = await apiRequest('student/recommendations.php');
        const recs = res.recommendations.slice(0, 3);

        if (recs.length === 0) {
            container.innerHTML = '<div class="empty-state"><p>No job recommendations available at this time.</p></div>';
            return;
        }

        container.innerHTML = recs.map(job => `
            <div class="card" style="margin-bottom: 1rem;">
                <div class="card-body" style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h4 style="font-weight: 700; margin-bottom: 0.25rem;">${job.job_title || job.title}</h4>
                        <div style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 0.5rem;">
                            <i class="fas fa-building"></i> ${job.company_name} &bull; <i class="fas fa-map-marker-alt"></i> ${job.location} &bull; ${formatCurrency(job.salary_stipend)}
                        </div>
                        <div>
                            <span class="badge badge-primary">${job.job_type}</span>
                            <span class="match-score-badge ${job.match_score >= 75 ? 'match-high' : 'match-medium'}">
                                <i class="fas fa-bullseye"></i> ${job.match_score}% Match
                            </span>
                        </div>
                    </div>
                    <div>
                        <a href="job-details.html?id=${job.job_id}" class="btn btn-outline btn-sm">View Details</a>
                    </div>
                </div>
            </div>
        `).join('');
    } catch (e) {
        container.innerHTML = '<div class="empty-state"><p>Complete your profile & skills to view recommendations.</p></div>';
    }
}

// Applications Preview on Dashboard
async function loadApplicationsPreview() {
    const tbody = document.getElementById('dash-recent-apps-tbody');
    if (!tbody) return;

    try {
        const res = await apiRequest('student/applications.php');
        const apps = res.applications.slice(0, 5);

        if (apps.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="empty-state">No job applications submitted yet.</td></tr>';
            return;
        }

        tbody.innerHTML = apps.map(app => `
            <tr>
                <td><strong>${app.job_title}</strong></td>
                <td>${app.company_name}</td>
                <td>${formatCurrency(app.salary_stipend)}</td>
                <td><span class="status-pill status-${app.status.toLowerCase()}">${app.status.replace('_', ' ')}</span></td>
                <td>${formatDate(app.applied_at)}</td>
            </tr>
        `).join('');
    } catch (e) {
        tbody.innerHTML = '<tr><td colspan="5">Error loading applications.</td></tr>';
    }
}

// Profile Page Handler
async function loadStudentProfile() {
    try {
        const res = await apiRequest('student/profile.php');
        const p = res.profile;

        document.getElementById('prof-fname').value = p.first_name || '';
        document.getElementById('prof-lname').value = p.last_name || '';
        document.getElementById('prof-roll').value = p.roll_number || '';
        document.getElementById('prof-email').value = p.email || '';
        document.getElementById('prof-phone').value = p.phone || '';
        document.getElementById('prof-dob').value = p.dob || '';
        document.getElementById('prof-gender').value = p.gender || '';
        document.getElementById('prof-address').value = p.address || '';
        document.getElementById('prof-year').value = p.current_year || 4;
        document.getElementById('prof-cgpa').value = p.cgpa || '';
        document.getElementById('prof-tenth').value = p.tenth_percent || '';
        document.getElementById('prof-twelfth').value = p.twelfth_percent || '';

        // Populate Branches
        const branchSelect = document.getElementById('prof-branch');
        if (branchSelect && res.branches) {
            branchSelect.innerHTML = res.branches.map(b => `
                <option value="${b.branch_id}" ${b.branch_id == p.branch_id ? 'selected' : ''}>
                    ${b.dept_name} - ${b.branch_name} (${b.branch_code})
                </option>
            `).join('');
        }
    } catch (err) {
        showToast('error', 'Profile Error', err.message);
    }
}

async function saveStudentProfile(e) {
    e.preventDefault();
    const btn = document.getElementById('save-profile-btn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

    const payload = {
        first_name: document.getElementById('prof-fname').value.trim(),
        last_name: document.getElementById('prof-lname').value.trim(),
        phone: document.getElementById('prof-phone').value.trim(),
        dob: document.getElementById('prof-dob').value,
        gender: document.getElementById('prof-gender').value,
        address: document.getElementById('prof-address').value.trim(),
        branch_id: document.getElementById('prof-branch').value,
        current_year: document.getElementById('prof-year').value,
        cgpa: document.getElementById('prof-cgpa').value,
        tenth_percent: document.getElementById('prof-tenth').value,
        twelfth_percent: document.getElementById('prof-twelfth').value
    };

    try {
        const res = await apiRequest('student/profile.php', {
            method: 'POST',
            body: JSON.stringify(payload)
        });
        showToast('success', 'Profile Updated', res.message);
    } catch (err) {
        showToast('error', 'Update Failed', err.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Save Profile';
    }
}

// Skills Management Page
async function loadStudentSkills() {
    const listContainer = document.getElementById('student-skills-list');
    const masterSelect = document.getElementById('master-skill-select');
    if (!listContainer) return;

    try {
        const res = await apiRequest('student/skills.php');
        
        // Render current skills
        if (res.skills.length === 0) {
            listContainer.innerHTML = '<div class="empty-state"><p>No skills added yet. Add your core technical skills below.</p></div>';
        } else {
            listContainer.innerHTML = res.skills.map(s => `
                <div class="tag-chip" style="font-size: 0.9rem; padding: 0.4rem 0.85rem;">
                    <span><strong>${s.skill_name}</strong> (${s.proficiency_level})</span>
                    <i class="fas fa-times tag-chip-remove" onclick="removeSkill(${s.skill_id})" title="Remove Skill"></i>
                </div>
            `).join('');
        }

        // Populate master skill dropdown
        if (masterSelect && res.all_skills) {
            masterSelect.innerHTML = '<option value="">-- Choose from Master Skills --</option>' +
                res.all_skills.map(s => `<option value="${s.skill_id}">${s.skill_name} (${s.category})</option>`).join('');
        }
    } catch (err) {
        showToast('error', 'Skills Error', err.message);
    }
}

async function addSkill() {
    const skillId = document.getElementById('master-skill-select').value;
    const customSkill = document.getElementById('custom-skill-input').value.trim();
    const proficiency = document.getElementById('skill-proficiency').value;

    if (!skillId && !customSkill) {
        showToast('warning', 'Input Required', 'Please select a skill or type a custom skill name.');
        return;
    }

    try {
        await apiRequest('student/skills.php', {
            method: 'POST',
            body: JSON.stringify({ skill_id: skillId, custom_skill: customSkill, proficiency_level: proficiency })
        });
        showToast('success', 'Skill Added', 'Your skill portfolio was updated.');
        document.getElementById('custom-skill-input').value = '';
        loadStudentSkills();
    } catch (err) {
        showToast('error', 'Failed', err.message);
    }
}

async function removeSkill(skillId) {
    if (!confirm('Are you sure you want to remove this skill?')) return;
    try {
        await apiRequest('student/skills.php', {
            method: 'DELETE',
            body: JSON.stringify({ skill_id: skillId })
        });
        showToast('success', 'Skill Removed', 'Skill removed from your profile.');
        loadStudentSkills();
    } catch (err) {
        showToast('error', 'Failed', err.message);
    }
}

// Projects Portfolio Page
async function loadStudentProjects() {
    const container = document.getElementById('projects-container');
    if (!container) return;

    try {
        const res = await apiRequest('student/projects.php');
        if (res.projects.length === 0) {
            container.innerHTML = '<div class="empty-state"><p>No projects added yet. Click "+ Add Project" to build your portfolio.</p></div>';
            return;
        }

        container.innerHTML = res.projects.map(p => `
            <div class="card" style="margin-bottom: 1.5rem;">
                <div class="card-header">
                    <h3 style="font-size: 1.1rem; font-weight: 700;">${p.title}</h3>
                    <button class="btn btn-outline btn-sm" onclick="deleteProject(${p.project_id})" style="color: var(--danger);"><i class="fas fa-trash"></i> Delete</button>
                </div>
                <div class="card-body">
                    <p style="color: var(--text-secondary); margin-bottom: 0.75rem;">${p.description || 'No description provided.'}</p>
                    <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.75rem;">
                        <strong>Technologies:</strong> ${p.technologies || 'N/A'}
                    </div>
                    <div style="display: flex; gap: 1rem;">
                        ${p.github_url ? `<a href="${p.github_url}" target="_blank" class="btn btn-outline btn-sm"><i class="fab fa-github"></i> GitHub</a>` : ''}
                        ${p.live_url ? `<a href="${p.live_url}" target="_blank" class="btn btn-primary btn-sm"><i class="fas fa-external-link-alt"></i> Live Demo</a>` : ''}
                    </div>
                </div>
            </div>
        `).join('');
    } catch (err) {
        showToast('error', 'Projects Error', err.message);
    }
}

async function saveProject(e) {
    e.preventDefault();
    const payload = {
        title: document.getElementById('proj-title').value.trim(),
        description: document.getElementById('proj-desc').value.trim(),
        technologies: document.getElementById('proj-tech').value.trim(),
        github_url: document.getElementById('proj-github').value.trim(),
        live_url: document.getElementById('proj-live').value.trim()
    };

    try {
        await apiRequest('student/projects.php', {
            method: 'POST',
            body: JSON.stringify(payload)
        });
        showToast('success', 'Project Added', 'Project added to your portfolio.');
        closeModal('add-project-modal');
        document.getElementById('project-form').reset();
        loadStudentProjects();
    } catch (err) {
        showToast('error', 'Failed', err.message);
    }
}

async function deleteProject(projId) {
    if (!confirm('Are you sure you want to delete this project?')) return;
    try {
        await apiRequest('student/projects.php', {
            method: 'DELETE',
            body: JSON.stringify({ project_id: projId })
        });
        showToast('success', 'Project Deleted', 'Project removed from portfolio.');
        loadStudentProjects();
    } catch (err) {
        showToast('error', 'Failed', err.message);
    }
}

// Certifications Page
async function loadStudentCertifications() {
    const container = document.getElementById('certifications-container');
    if (!container) return;

    try {
        const res = await apiRequest('student/certifications.php');
        if (res.certifications.length === 0) {
            container.innerHTML = '<div class="empty-state"><p>No certifications added yet.</p></div>';
            return;
        }

        container.innerHTML = res.certifications.map(c => `
            <div class="card" style="margin-bottom: 1.5rem;">
                <div class="card-body" style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h4 style="font-weight: 700; margin-bottom: 0.25rem;">${c.title}</h4>
                        <div style="font-size: 0.85rem; color: var(--text-secondary);">
                            <i class="fas fa-certificate"></i> Issuing Body: <strong>${c.issuing_org}</strong> &bull; Issued: ${formatDate(c.issue_date)}
                        </div>
                    </div>
                    <div style="display: flex; gap: 0.75rem;">
                        ${c.credential_url ? `<a href="${c.credential_url}" target="_blank" class="btn btn-outline btn-sm"><i class="fas fa-link"></i> Verify</a>` : ''}
                        <button class="btn btn-outline btn-sm" onclick="deleteCert(${c.cert_id})" style="color: var(--danger);"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
            </div>
        `).join('');
    } catch (err) {
        showToast('error', 'Certifications Error', err.message);
    }
}

async function saveCertification(e) {
    e.preventDefault();
    const payload = {
        title: document.getElementById('cert-title').value.trim(),
        issuing_org: document.getElementById('cert-org').value.trim(),
        issue_date: document.getElementById('cert-date').value,
        credential_url: document.getElementById('cert-url').value.trim()
    };

    try {
        await apiRequest('student/certifications.php', {
            method: 'POST',
            body: JSON.stringify(payload)
        });
        showToast('success', 'Certification Added', 'Certification added.');
        closeModal('add-cert-modal');
        document.getElementById('cert-form').reset();
        loadStudentCertifications();
    } catch (err) {
        showToast('error', 'Failed', err.message);
    }
}

async function deleteCert(certId) {
    if (!confirm('Are you sure you want to remove this certification?')) return;
    try {
        await apiRequest('student/certifications.php', {
            method: 'DELETE',
            body: JSON.stringify({ cert_id: certId })
        });
        showToast('success', 'Removed', 'Certification removed.');
        loadStudentCertifications();
    } catch (err) {
        showToast('error', 'Failed', err.message);
    }
}

// Resume Upload Page
async function loadResumeInfo() {
    try {
        const res = await apiRequest('student/resume.php');
        const container = document.getElementById('current-resume-display');
        if (container) {
            if (res.resume_path) {
                container.innerHTML = `
                    <div class="card" style="background: #f0fdf4; border-color: #bbf7d0;">
                        <div class="card-body" style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <h4 style="color: #166534; font-weight: 700;"><i class="fas fa-file-pdf"></i> Resume on File</h4>
                                <p style="font-size: 0.85rem; color: #15803d;">Your resume is actively linked to your job applications.</p>
                            </div>
                            <a href="../${res.resume_path}" target="_blank" class="btn btn-success btn-sm"><i class="fas fa-download"></i> View / Download</a>
                        </div>
                    </div>
                `;
            } else {
                container.innerHTML = '<div class="empty-state"><p>No resume uploaded yet. Upload your PDF resume below.</p></div>';
            }
        }
    } catch (err) {
        console.error(err);
    }
}

async function uploadResumeFile(e) {
    e.preventDefault();
    const fileInput = document.getElementById('resume-file-input');
    if (!fileInput.files || fileInput.files.length === 0) {
        showToast('warning', 'File Required', 'Please select a PDF or DOCX file to upload.');
        return;
    }

    const formData = new FormData();
    formData.append('resume', fileInput.files[0]);

    const btn = document.getElementById('upload-resume-btn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';

    try {
        const res = await apiRequest('student/resume.php', {
            method: 'POST',
            body: formData
        });
        showToast('success', 'Resume Uploaded', res.message);
        loadResumeInfo();
    } catch (err) {
        showToast('error', 'Upload Failed', err.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-cloud-upload-alt"></i> Upload Resume';
    }
}

// Recommendations Full Page
async function loadFullRecommendations() {
    const container = document.getElementById('recommendations-grid');
    if (!container) return;

    container.innerHTML = '<div class="empty-state"><i class="fas fa-spinner fa-spin"></i><p>Finding matching job opportunities...</p></div>';

    try {
        const res = await apiRequest('student/recommendations.php');
        const recs = res.recommendations || [];

        const engineInfo = document.getElementById('ai-engine-info');
        if (engineInfo && res.engine_used) {
            engineInfo.textContent = res.engine_used;
        }

        if (recs.length === 0) {
            container.innerHTML = '<div class="empty-state"><p>No active matching opportunities found.</p></div>';
            return;
        }

        container.innerHTML = recs.map(job => `
            <div class="card" style="margin-bottom: 1.5rem;">
                <div class="card-body">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                        <div>
                            <h3 style="font-weight: 700; font-size: 1.2rem; color: var(--text-primary);">${job.job_title || job.title}</h3>
                            <div style="font-size: 0.9rem; color: var(--text-secondary); margin-top: 0.25rem;">
                                <strong>${job.company_name}</strong> &bull; ${job.location} &bull; ${formatCurrency(job.salary_stipend)}
                            </div>
                        </div>
                        <div>
                            <div class="match-score-badge ${job.match_score >= 75 ? 'match-high' : 'match-medium'}" style="font-size: 1.1rem; padding: 0.5rem 1rem;">
                                <i class="fas fa-bolt"></i> ${job.match_score}% Match
                            </div>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 1.25rem; background: #f8fafc; padding: 1rem; border-radius: var(--radius-sm);">
                        <div>
                            <span style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">Matched Skills:</span>
                            <div style="display: flex; flex-wrap: wrap; gap: 0.35rem; margin-top: 0.25rem;">
                                ${(job.matched_skills && job.matched_skills.length > 0) ? 
                                    (Array.isArray(job.matched_skills) ? job.matched_skills : job.matched_skills.split(',')).map(s => `<span class="badge badge-success">${s}</span>`).join('') :
                                    '<span style="font-size: 0.8rem; color: var(--text-muted);">None matched yet</span>'}
                            </div>
                        </div>
                        <div>
                            <span style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">Missing Skills:</span>
                            <div style="display: flex; flex-wrap: wrap; gap: 0.35rem; margin-top: 0.25rem;">
                                ${(job.missing_skills && job.missing_skills.length > 0) ? 
                                    (Array.isArray(job.missing_skills) ? job.missing_skills : job.missing_skills.split(',')).map(s => `<span class="badge badge-warning">${s}</span>`).join('') :
                                    '<span style="font-size: 0.8rem; color: var(--success);"><i class="fas fa-check"></i> All required skills met!</span>'}
                            </div>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 0.85rem; color: var(--text-secondary);">
                            Deadline: <strong>${formatDate(job.deadline)}</strong> &bull; Min CGPA: <strong>${job.min_cgpa}</strong>
                        </span>
                        <div style="display: flex; gap: 0.75rem;">
                            <a href="job-details.html?id=${job.job_id}" class="btn btn-outline btn-sm">View Details</a>
                            <button class="btn btn-primary btn-sm" onclick="applyToJob(${job.job_id})">
                                ${job.application_status ? job.application_status : 'Apply Now'}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');

    } catch (err) {
        container.innerHTML = `<div class="empty-state"><p>Error calculating recommendations: ${err.message}</p></div>`;
    }
}

// Direct Apply to Job from button
async function applyToJob(jobId) {
    try {
        const res = await apiRequest('student/applications.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'apply', job_id: jobId })
        });
        showToast('success', 'Applied Successfully', res.message);
        setTimeout(() => window.location.href = 'applications.html', 1000);
    } catch (err) {
        showToast('error', 'Application Failed', err.message);
    }
}
