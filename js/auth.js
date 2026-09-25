/**
 * Authentication Client Handlers
 * Login, Registration, Password Reset, and Demo Credential Fillers
 */

document.addEventListener('DOMContentLoaded', () => {
    // Quick Demo Account Filler
    window.quickFill = (email, password) => {
        const emailInput = document.getElementById('email');
        const passInput = document.getElementById('password');
        if (emailInput && passInput) {
            emailInput.value = email;
            passInput.value = password;
            showToast('info', 'Credentials Loaded', `Pre-filled ${email}`);
        }
    };

    // Role Toggle on Registration Page
    const roleBtns = document.querySelectorAll('.role-btn');
    roleBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            roleBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            const role = btn.getAttribute('data-role');
            const roleInput = document.getElementById('register-role');
            if (roleInput) roleInput.value = role;

            const studentFields = document.getElementById('student-fields');
            const recruiterFields = document.getElementById('recruiter-fields');

            if (role === 'STUDENT') {
                if (studentFields) studentFields.style.display = 'block';
                if (recruiterFields) recruiterFields.style.display = 'none';
            } else {
                if (studentFields) studentFields.style.display = 'none';
                if (recruiterFields) recruiterFields.style.display = 'block';
            }
        });
    });

    // Login Form Submit
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = loginForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing In...';

            const roleEl = document.getElementById('role');
            const role = roleEl ? roleEl.value : '';
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;

            try {
                const res = await apiRequest('auth/login.php', {
                    method: 'POST',
                    body: JSON.stringify({ role, email, password })
                });

                if (res.token) {
                    localStorage.setItem('careerbridge_token', res.token);
                    if (res.user && res.user.role) {
                        localStorage.setItem(`careerbridge_token_${res.user.role}`, res.token);
                    }
                }
                if (res.user) {
                    localStorage.setItem('careerbridge_user', JSON.stringify(res.user));
                }

                showToast('success', 'Login Successful', `Welcome back, ${res.user.name}!`);
                setTimeout(() => {
                    window.location.href = res.redirect;
                }, 800);
            } catch (err) {
                showToast('error', 'Login Failed', err.message);
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        });
    }

    // Register Form Submit
    const registerForm = document.getElementById('register-form');
    if (registerForm) {
        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = registerForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Account...';

            const role = document.getElementById('register-role').value;
            const email = document.getElementById('reg-email').value.trim();
            const password = document.getElementById('reg-password').value;

            const payload = { role, email, password };

            if (role === 'STUDENT') {
                payload.first_name = document.getElementById('reg-fname').value.trim();
                payload.last_name = document.getElementById('reg-lname').value.trim();
                payload.roll_number = document.getElementById('reg-roll').value.trim();
                payload.phone = document.getElementById('reg-phone').value.trim();
                payload.branch_id = document.getElementById('reg-branch').value;
                payload.cgpa = document.getElementById('reg-cgpa').value;
            } else {
                payload.recruiter_name = document.getElementById('reg-rec-name')?.value.trim() || '';
                payload.company_name = document.getElementById('reg-company').value.trim();
                payload.designation = document.getElementById('reg-designation').value.trim();
                payload.phone = document.getElementById('reg-rec-phone').value.trim();
                payload.website = document.getElementById('reg-website').value.trim();
                payload.location = document.getElementById('reg-location').value.trim();
            }

            try {
                const res = await apiRequest('auth/register.php', {
                    method: 'POST',
                    body: JSON.stringify(payload)
                });

                showToast('success', 'Account Registered', res.message);
                setTimeout(() => {
                    window.location.href = res.redirect;
                }, 1200);
            } catch (err) {
                showToast('error', 'Registration Failed', err.message);
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        });
    }

    // Forgot Password Form Submit
    const forgotForm = document.getElementById('forgot-password-form');
    if (forgotForm) {
        forgotForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = forgotForm.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating Password...';

            const email = document.getElementById('forgot-email').value.trim();
            const newPassword = document.getElementById('forgot-new-password').value;

            try {
                const res = await apiRequest('auth/forgot_password.php', {
                    method: 'POST',
                    body: JSON.stringify({ email, new_password: newPassword })
                });

                showToast('success', 'Password Updated', res.message);
                setTimeout(() => {
                    window.location.href = res.redirect;
                }, 1200);
            } catch (err) {
                showToast('error', 'Reset Failed', err.message);
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Reset Password';
            }
        });
    }
});
