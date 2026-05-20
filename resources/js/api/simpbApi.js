import { SimpbApiError } from './apiErrors';

const DEFAULT_BASE_URL = '/api';
const TOKEN_STORAGE_KEY = 'simpb_access_token';
const USER_STORAGE_KEY = 'simpb_user';

export function createSimpbApiClient({
    baseUrl = DEFAULT_BASE_URL,
    tokenStorage = window.sessionStorage,
    onUnauthenticated = null,
} = {}) {
    function getToken() {
        return tokenStorage.getItem(TOKEN_STORAGE_KEY);
    }

    function setToken(token) {
        if (!token) {
            tokenStorage.removeItem(TOKEN_STORAGE_KEY);
            return;
        }

        tokenStorage.setItem(TOKEN_STORAGE_KEY, token);
    }

    function getStoredUser() {
        const raw = tokenStorage.getItem(USER_STORAGE_KEY);

        if (!raw) {
            return null;
        }

        try {
            return JSON.parse(raw);
        } catch {
            return null;
        }
    }

    function setStoredUser(user) {
        if (!user) {
            tokenStorage.removeItem(USER_STORAGE_KEY);
            return;
        }

        tokenStorage.setItem(USER_STORAGE_KEY, JSON.stringify(user));
    }

    function clearAuth() {
        tokenStorage.removeItem(TOKEN_STORAGE_KEY);
        tokenStorage.removeItem(USER_STORAGE_KEY);
    }

    function buildUrl(path, query = null) {
        const cleanBase = baseUrl.replace(/\/+$/, '');
        const cleanPath = String(path).startsWith('/')
            ? String(path)
            : `/${path}`;

        const url = new URL(`${cleanBase}${cleanPath}`, window.location.origin);

        if (query) {
            Object.entries(query).forEach(([key, value]) => {
                if (value === null || value === undefined || value === '') {
                    return;
                }

                if (Array.isArray(value)) {
                    value.forEach((item) => {
                        url.searchParams.append(`${key}[]`, item);
                    });
                    return;
                }

                url.searchParams.set(key, value);
            });
        }

        return url.toString();
    }

    async function parseResponse(response) {
        const contentType = response.headers.get('content-type') || '';
        const isJson = contentType.includes('application/json');

        const payload = isJson
            ? await response.json()
            : await response.text();

        if (response.ok) {
            return payload;
        }

        if (isJson && payload?.error) {
            const apiError = new SimpbApiError(
                payload.error.message || 'Request API gagal.',
                {
                    status: response.status,
                    code: payload.error.code || 'API_ERROR',
                    details: payload.error.details || null,
                    meta: payload.meta || null,
                    raw: payload,
                }
            );

            if (apiError.isUnauthenticated()) {
                clearAuth();

                if (typeof onUnauthenticated === 'function') {
                    onUnauthenticated(apiError);
                }
            }

            throw apiError;
        }

        throw new SimpbApiError('Request API gagal.', {
            status: response.status,
            code: 'NON_JSON_ERROR',
            raw: payload,
        });
    }

    async function request(path, {
        method = 'GET',
        query = null,
        body = null,
        headers = {},
        auth = true,
    } = {}) {
        const requestHeaders = {
            Accept: 'application/json',
            ...headers,
        };

        if (auth) {
            const token = getToken();

            if (token) {
                requestHeaders.Authorization = `Bearer ${token}`;
            }
        }

        let requestBody = body;

        if (body && !(body instanceof FormData)) {
            requestHeaders['Content-Type'] = 'application/json';
            requestBody = JSON.stringify(body);
        }

        const response = await fetch(buildUrl(path, query), {
            method,
            headers: requestHeaders,
            body: requestBody,
        });

        return parseResponse(response);
    }

    return {
        getToken,
        setToken,
        getStoredUser,
        setStoredUser,
        clearAuth,

        get(path, options = {}) {
            return request(path, {
                ...options,
                method: 'GET',
            });
        },

        post(path, body = null, options = {}) {
            return request(path, {
                ...options,
                method: 'POST',
                body,
            });
        },

        patch(path, body = null, options = {}) {
            return request(path, {
                ...options,
                method: 'PATCH',
                body,
            });
        },

        delete(path, options = {}) {
            return request(path, {
                ...options,
                method: 'DELETE',
            });
        },

        async login({ email, password, device_name = 'frontend' }) {
            const response = await request('/auth/login', {
                method: 'POST',
                auth: false,
                body: {
                    email,
                    password,
                    device_name,
                },
            });

            setToken(response.access_token);
            setStoredUser(response.user);

            return response;
        },

        async me() {
            const response = await request('/auth/me', {
                method: 'GET',
                auth: true,
            });

            setStoredUser(response.data);

            return response;
        },

        async logout() {
            try {
                return await request('/auth/logout', {
                    method: 'POST',
                    auth: true,
                });
            } finally {
                clearAuth();
            }
        },

        uploadDigitalAsset(identifier, formData) {
            return request(`/staff/collections/${encodeURIComponent(identifier)}/digital-assets/upload`, {
                method: 'POST',
                auth: true,
                body: formData,
            });
        },
    };
}

export function userHasRole(user, role) {
    return Array.isArray(user?.roles) && user.roles.includes(role);
}

export function userHasAnyRole(user, roles = []) {
    return Array.isArray(user?.roles)
        && roles.some((role) => user.roles.includes(role));
}

export function isStaff(user) {
    return userHasAnyRole(user, ['admin', 'pustakawan', 'kurator']);
}

export function isMember(user) {
    return userHasRole(user, 'member');
}