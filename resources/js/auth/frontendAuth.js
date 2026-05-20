import { createSimpbApiClient, userHasAnyRole } from '../api/simpbApi';
import { normalizeApiError } from '../api/apiErrors';

const api = createSimpbApiClient({
    baseUrl: '/api',
    tokenStorage: window.sessionStorage,
    onUnauthenticated: () => {
        api.clearAuth();
        redirectToLogin();
    },
});

const body = document.body;
const requiredRoles = parseRequiredRoles(body?.dataset?.requiredRoles || '');
const logoutButtons = document.querySelectorAll('[data-logout-button]');
const authStatusElements = document.querySelectorAll('[data-auth-status]');
const userNameElements = document.querySelectorAll('[data-auth-user-name]');
const userRoleElements = document.querySelectorAll('[data-auth-user-roles]');

function parseRequiredRoles(rawRoles) {
    return String(rawRoles)
        .split(',')
        .map((role) => role.trim())
        .filter(Boolean);
}

function currentUrlForRedirect() {
    return `${window.location.pathname}${window.location.search}`;
}

function redirectToLogin() {
    const redirect = encodeURIComponent(currentUrlForRedirect());

    window.location.assign(`/login?redirect=${redirect}`);
}

function redirectToForbidden() {
    window.location.assign('/forbidden');
}

function setAuthStatus(message) {
    authStatusElements.forEach((element) => {
        element.textContent = message;
    });
}

function renderUser(user) {
    userNameElements.forEach((element) => {
        element.textContent = user?.name || 'User';
    });

    userRoleElements.forEach((element) => {
        element.textContent = Array.isArray(user?.roles)
            ? user.roles.join(', ')
            : '-';
    });
}

function allowPage(user) {
    if (requiredRoles.length === 0) {
        return true;
    }

    return userHasAnyRole(user, requiredRoles);
}

async function guardPage() {
    if (!body?.dataset?.authGuard) {
        return;
    }

    const token = api.getToken();

    if (!token) {
        redirectToLogin();
        return;
    }

    setAuthStatus('Memeriksa sesi...');

    try {
        const response = await api.me();
        const user = response.data;

        if (!allowPage(user)) {
            redirectToForbidden();
            return;
        }

        renderUser(user);
        setAuthStatus('Sesi aktif');

        body.classList.remove('auth-checking');
        body.classList.add('auth-ready');
    } catch (error) {
        const apiError = normalizeApiError(error);

        if (apiError.isUnauthenticated()) {
            redirectToLogin();
            return;
        }

        api.clearAuth();
        redirectToLogin();
    }
}

async function handleLogout(event) {
    event.preventDefault();

    const button = event.currentTarget;
    const originalText = button.textContent;

    button.disabled = true;
    button.textContent = 'Logout...';

    try {
        await api.logout();
    } catch {
        api.clearAuth();
    } finally {
        button.textContent = originalText;
        window.location.assign('/login');
    }
}

logoutButtons.forEach((button) => {
    button.addEventListener('click', handleLogout);
});

guardPage();