/**
 * Core Application Utilities & API Connector
 * CareerBridge - Internship & Placement Management System
 */

/// Dynamic Base URL Auto-Detector
function getApiBaseUrl() {
    // If running under VS Code Live Server or static file server (port 5500, 5501, etc.)
    const staticPorts = ['5500', '5501', '5502', '3000', '5173'];
    if (staticPorts.includes(window.location.port) || window.location.protocol === 'file:') {
        return 'http://localhost:8000/backend/';
    }
    const path = window.location.pathname;
    const match = path.match(/^(\/[^\/]+)?\/(student|recruiter|admin|css|js|database|assets)/);
    if (match && match[1]) {
        return match[1] + '/backend/';
    }
    // Check if hosted under a subdirectory or root
    const segments = path.split('/').filter(Boolean);
    if (segments.length > 0 && !['student', 'recruiter', 'admin', 'index.html', 'login.html', 'register.html', 'about.html', 'opportunities.html', 'companies.html', 'how-it-works.html', 'contact.html', 'forgot-password.html'].includes(segments[0])) {
        return '/' + segments[0] + '/backend/';
    }
    return '/backend/';
}

// Global API Request Helper
async function apiRequest(endpoint, options = {}) {
    const baseUrl = getApiBaseUrl();
    const cleanEndpoint = endpoint.startsWith('/') ? endpoint.slice(1) : endpoint;
    const url = baseUrl + cleanEndpoint;

    const defaultHeaders = {
        'Accept': 'application/json'
    };

    if (!(options.body instanceof FormData)) {
        defaultHeaders['Content-Type'] = 'application/json';
    }

    // Attach Bearer auth token if present (ensures authentication across ports/hosts)
    let token = localStorage.getItem('careerbridge_token');
    const path = window.location.pathname.toLowerCase();
    if (path.includes('/recruiter/') || cleanEndpoint.startsWith('recruiter/')) {
        token = localStorage.getItem('careerbridge_token_RECRUITER') || token;
    } else if (path.includes('/student/') || cleanEndpoint.startsWith('student/')) {
        token = localStorage.getItem('careerbridge_token_STUDENT') || token;
    } else if (path.includes('/admin/') || cleanEndpoint.startsWith('admin/')) {
        token = localStorage.getItem('careerbridge_token_ADMIN') || token;
    }
    if (token) {
        defaultHeaders['Authorization'] = `Bearer ${token}`;
    }

    const isCrossPort = (['5500', '5501', '5502', '3000', '5173'].includes(window.location.port) || window.location.protocol === 'file:');
    const config = {
        credentials: isCrossPort ? 'include' : 'same-origin',
        ...options,
        headers: {
            ...defaultHeaders,
            ...(options.headers || {})
        }
    };

    try {
        const response = await fetch(url, config);
        const text = await response.text();
        let data = {};
        try {
            const jsonStart = text.indexOf('{');
            const arrayStart = text.indexOf('[');
            let validJsonText = text;
            if (jsonStart !== -1 && (arrayStart === -1 || jsonStart < arrayStart)) {
                validJsonText = text.substring(jsonStart);
            } else if (arrayStart !== -1) {
                validJsonText = text.substring(arrayStart);
            }
            data = validJsonText ? JSON.parse(validJsonText) : {};
        } catch (jsonErr) {
            data = { success: false, error: text || `HTTP ${response.status} ${response.statusText}` };
        }

        if (!response.ok) {
            if (response.status === 405) {
                throw new Error("Port " + window.location.port + " cannot execute PHP scripts. Please open http://localhost:8000 (run start_server.bat).");
            }

            const isProtectedPage = window.location.pathname.includes('/student/') || 
                                    window.location.pathname.includes('/recruiter/') || 
                                    window.location.pathname.includes('/admin/');

            if (response.status === 401 && isProtectedPage && !options._retry) {
                let role = 'STUDENT';
                if (window.location.pathname.includes('/recruiter/')) role = 'RECRUITER';
                else if (window.location.pathname.includes('/admin/')) role = 'ADMIN';

                try {
                    const autoRes = await fetch(getApiBaseUrl() + 'auth/auto_login.php?role=' + role);
                    if (autoRes.ok) {
                        return await apiRequest(endpoint, { ...options, _retry: true });
                    }
                } catch (retryErr) {
                    console.warn('Auto-login retry error:', retryErr);
                }
            }
            throw new Error(data.error || `Request failed with status ${response.status}`);
        }

        return data;
    } catch (err) {
        if (err.message && (err.message.includes('Failed to fetch') || err.message.includes('NetworkError'))) {
            throw new Error("Cannot connect to PHP backend. Please run start_server.bat to start the server on http://localhost:8000");
        }
        console.error(`API Error [${endpoint}]:`, err);
        throw err;
    }
}

// Toast Notification Engine
function showToast(type, title, message) {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;

    let iconClass = 'fa-check-circle';
    if (type === 'error') iconClass = 'fa-times-circle';
    if (type === 'warning') iconClass = 'fa-exclamation-triangle';

    toast.innerHTML = `
        <div class="toast-icon"><i class="fas ${iconClass}"></i></div>
        <div class="toast-content">
            <div class="toast-title">${title}</div>
            <div class="toast-msg">${message}</div>
        </div>
    `;

    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 4500);
}

// Modal Helpers
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// Formatting Utilities
function formatCurrency(val, isSalary = true) {
    const num = parseFloat(val);
    if (isNaN(num)) return '₹0';
    if (num >= 100000) {
        return `₹${(num / 100000).toFixed(2)} LPA`;
    } else if (num > 0 && num <= 100) {
        return `₹${num.toFixed(2)} LPA`;
    }
    return `₹${num.toLocaleString('en-IN')}${!isSalary ? '/mo' : ''}`;
}

function formatDate(dateStr) {
    if (!dateStr) return 'N/A';
    const d = new Date(dateStr);
    return d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
}

// Global Logout Handler
async function handleLogout() {
    try {
        localStorage.removeItem('careerbridge_token');
        localStorage.removeItem('careerbridge_user');
        await apiRequest('auth/logout.php');
        showToast('success', 'Logged Out', 'You have been successfully logged out.');
        setTimeout(() => {
            const loginUrl = window.location.pathname.includes('/student/') || 
                             window.location.pathname.includes('/recruiter/') || 
                             window.location.pathname.includes('/admin/') ? '../login.html' : 'login.html';
            window.location.href = loginUrl;
        }, 800);
    } catch (e) {
        localStorage.removeItem('careerbridge_token');
        localStorage.removeItem('careerbridge_user');
        window.location.href = '../login.html';
    }
}

// Check session on page load
document.addEventListener('DOMContentLoaded', async () => {
    // Setup modal backdrop clicks
    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal(modal.id);
            }
        });
    });
});
