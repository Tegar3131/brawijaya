import { createSimpbApiClient } from '../../api/simpbApi';
import { normalizeApiError, validationErrorsToObject } from '../../api/apiErrors';

const api = createSimpbApiClient({
    baseUrl: '/api',
    tokenStorage: window.sessionStorage,
    onUnauthenticated: () => {
        api.clearAuth();
    },
});

const form = document.querySelector('[data-login-form]');
const emailInput = document.querySelector('#email');
const passwordInput = document.querySelector('#password');
const loginButton = document.querySelector('#login-button');
const alertBox = document.querySelector('#login-alert');

function getUserRoles(user) {
    if (!user || !Array.isArray(user.roles)) {
        return [];
    }

    return user.roles;
}

function redirectByRole(user) {
    const roles = getUserRoles(user);

    if (
        roles.includes('admin')
        || roles.includes('pustakawan')
        || roles.includes('kurator')
    ) {
        window.location.assign('/staff/dashboard');
        return;
    }

    if (roles.includes('member')) {
        window.location.assign('/member/dashboard');
        return;
    }

    window.location.assign('/');
}

function safeRedirectFromQuery() {
    const params = new URLSearchParams(window.location.search);
    const redirect = params.get('redirect');

    if (!redirect) {
        return null;
    }

    if (!redirect.startsWith('/') || redirect.startsWith('//')) {
        return null;
    }

    if (redirect === '/login') {
        return null;
    }

    return redirect;
}

function setLoading(isLoading) {
    if (!loginButton) {
        return;
    }

    loginButton.disabled = isLoading;
    loginButton.textContent = isLoading ? 'Memproses...' : 'Login';
}

function clearAlert() {
    if (!alertBox) {
        return;
    }

    alertBox.className = 'alert';
    alertBox.textContent = '';
}

function showAlert(message, type = 'error') {
    if (!alertBox) {
        return;
    }

    alertBox.className = `alert ${type} is-visible`;
    alertBox.textContent = message;
}

function clearFieldErrors() {
    document.querySelectorAll('[data-error-for]').forEach((element) => {
        element.textContent = '';
    });

    document.querySelectorAll('input[aria-invalid="true"]').forEach((input) => {
        input.removeAttribute('aria-invalid');
    });
}

function showFieldErrors(details) {
    const errors = validationErrorsToObject(details);

    Object.entries(errors).forEach(([field, message]) => {
        const errorElement = document.querySelector(`[data-error-for="${field}"]`);
        const inputElement = document.querySelector(`[name="${field}"]`);

        if (errorElement) {
            errorElement.textContent = message;
        }

        if (inputElement) {
            inputElement.setAttribute('aria-invalid', 'true');
        }
    });
}

async function redirectIfAlreadyAuthenticated() {
    const token = api.getToken();

    if (!token) {
        return;
    }

    try {
        const response = await api.me();
        const redirect = safeRedirectFromQuery();

        if (redirect) {
            window.location.assign(redirect);
            return;
        }

        redirectByRole(response.data);
    } catch {
        api.clearAuth();
    }
}

async function handleLoginSubmit(event) {
    event.preventDefault();

    clearAlert();
    clearFieldErrors();
    setLoading(true);

    try {
        const email = emailInput.value.trim();
        const password = passwordInput.value;

        await api.login({
            email,
            password,
            device_name: 'simpb-frontend',
        });

        const me = await api.me();

        showAlert('Login berhasil. Mengalihkan halaman...', 'success');

        const redirect = safeRedirectFromQuery();

        if (redirect) {
            window.location.assign(redirect);
            return;
        }

        redirectByRole(me.data);
    } catch (error) {
        const apiError = normalizeApiError(error);

        if (apiError.isValidationError()) {
            showFieldErrors(apiError.details);
            showAlert(apiError.message || 'Data login tidak valid.', 'error');
            return;
        }

        if (apiError.isRateLimited()) {
            showAlert('Terlalu banyak percobaan login. Silakan coba lagi nanti.', 'warning');
            return;
        }

        if (apiError.isUnauthenticated()) {
            showAlert('Sesi tidak valid. Silakan login ulang.', 'error');
            return;
        }

        showAlert(apiError.message || 'Login gagal. Silakan coba lagi.', 'error');
    } finally {
        setLoading(false);
    }
}

redirectIfAlreadyAuthenticated();

if (form) {
    form.addEventListener('submit', handleLoginSubmit);
}