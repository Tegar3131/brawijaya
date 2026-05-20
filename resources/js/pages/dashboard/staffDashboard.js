import { createSimpbApiClient } from '../../api/simpbApi';
import { normalizeApiError } from '../../api/apiErrors';

const api = createSimpbApiClient({
    baseUrl: '/api',
    tokenStorage: window.sessionStorage,
    onUnauthenticated: () => {
        api.clearAuth();
        window.location.assign('/login?redirect=/staff/dashboard');
    },
});

const loading = document.querySelector('#staff-dashboard-loading');
const content = document.querySelector('#staff-dashboard-content');
const alertBox = document.querySelector('#staff-dashboard-alert');

function setText(field, value) {
    const element = document.querySelector(`[data-field="${field}"]`);

    if (!element) {
        return;
    }

    element.textContent = value ?? '-';
}

function showBlock(selector) {
    const element = document.querySelector(selector);

    if (element) {
        element.style.display = '';
    }
}

function hideBlock(selector) {
    const element = document.querySelector(selector);

    if (element) {
        element.style.display = 'none';
    }
}

function formatDateTime(value) {
    if (!value) {
        return '-';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleString('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function showLoading() {
    if (loading) {
        loading.style.display = '';
    }

    if (content) {
        content.style.display = 'none';
    }

    if (alertBox) {
        alertBox.style.display = 'none';
        alertBox.innerHTML = '';
    }
}

function showContent() {
    if (loading) {
        loading.style.display = 'none';
    }

    if (content) {
        content.style.display = '';
    }
}

function showError(message) {
    if (loading) {
        loading.style.display = 'none';
    }

    if (content) {
        content.style.display = 'none';
    }

    if (alertBox) {
        alertBox.style.display = '';
        alertBox.innerHTML = `
            <h2>Dashboard gagal dimuat</h2>
            <p>${escapeHtml(message)}</p>
        `;
    }
}

function renderLatest(items, type) {
    const container = document.querySelector('#staff-latest-list');

    if (!container) {
        return;
    }

    if (!Array.isArray(items) || items.length === 0) {
        container.innerHTML = '<p>Belum ada aktivitas terbaru.</p>';
        return;
    }

    if (type === 'borrowings') {
        container.innerHTML = `
            <ul>
                ${items.map((item) => `
                    <li>
                        <strong>${escapeHtml(item.collection_title || 'Tanpa judul')}</strong>
                        — ${escapeHtml(item.transaction_code || '-')}
                        — ${escapeHtml(item.status || '-')}
                        — jatuh tempo ${escapeHtml(formatDateTime(item.due_date))}
                    </li>
                `).join('')}
            </ul>
        `;
        return;
    }

    if (type === 'condition_reports') {
        container.innerHTML = `
            <ul>
                ${items.map((item) => `
                    <li>
                        <strong>${escapeHtml(item.collection_title || 'Tanpa judul')}</strong>
                        — kondisi ${escapeHtml(item.condition_grade || '-')}
                        — prioritas ${escapeHtml(item.priority || '-')}
                    </li>
                `).join('')}
            </ul>
        `;
        return;
    }

    container.innerHTML = `
        <ul>
            ${items.map((item) => `
                <li>
                    <strong>${escapeHtml(item.title || 'Tanpa judul')}</strong>
                    — ${escapeHtml(item.record_code || '-')}
                    — ${escapeHtml(item.publication_status || '-')}
                </li>
            `).join('')}
        </ul>
    `;
}

function renderAdmin(data) {
    showBlock('#admin-summary');

    const collections = data.collections || {};
    const users = data.users || {};
    const circulation = data.circulation || {};

    setText('admin_collections_total_active', collections.total_active ?? 0);
    setText('admin_collections_total_with_archived', collections.total_with_archived ?? 0);
    setText('admin_collections_library', collections.library ?? 0);
    setText('admin_collections_museum', collections.museum ?? 0);
    setText('admin_collections_published', collections.published ?? 0);
    setText('admin_collections_draft', collections.draft ?? 0);
    setText('admin_collections_archived', collections.archived ?? 0);

    setText('admin_users_total', users.total ?? 0);
    setText('admin_users_members', users.members ?? 0);
    setText('admin_users_active_members', users.active_members ?? 0);
    setText('admin_circulation_active', circulation.active_borrowings ?? 0);
    setText('admin_circulation_overdue', circulation.overdue_borrowings ?? 0);
    setText('admin_circulation_reservations', circulation.active_reservations ?? 0);

    renderLatest(data.activity?.latest_searches || [], 'searches');
}

function renderLibrary(data) {
    showBlock('#library-summary');

    const collections = data.collections || {};
    const copies = data.copies || {};
    const circulation = data.circulation || {};
    const reservations = data.reservations || {};

    setText('library_total', collections.library_total ?? 0);
    setText('library_published', collections.published ?? 0);
    setText('library_draft', collections.draft ?? 0);
    setText('library_archived', collections.archived ?? 0);

    setText('copy_total', copies.total ?? 0);
    setText('copy_available', copies.available ?? 0);
    setText('copy_borrowed', copies.borrowed ?? 0);
    setText('copy_reserved', copies.reserved ?? 0);

    setText('library_active_borrowings', circulation.active_borrowings ?? 0);
    setText('library_overdue_borrowings', circulation.overdue_borrowings ?? 0);

    setText('reservation_active', reservations.active ?? 0);
    setText('reservation_notified', reservations.notified ?? 0);
    setText('reservation_expired', reservations.expired ?? 0);
    setText('reservation_cancelled', reservations.cancelled ?? 0);

    renderLatest(data.latest?.borrowings || [], 'borrowings');
}

function renderMuseum(data) {
    showBlock('#museum-summary');

    const collections = data.collections || {};
    const museumItems = data.museum_items || {};
    const conditionReports = data.condition_reports || {};
    const digitalAssets = data.digital_assets || {};

    setText('museum_total', collections.museum_total ?? 0);
    setText('museum_published', collections.published ?? 0);
    setText('museum_draft', collections.draft ?? 0);
    setText('museum_archived', collections.archived ?? 0);

    setText('museum_items_total', museumItems.total ?? 0);
    setText('museum_items_sensitive', museumItems.sensitive ?? 0);

    setText('condition_total', conditionReports.total ?? 0);
    setText('condition_urgent', conditionReports.urgent ?? 0);
    setText('condition_maintenance', conditionReports.maintenance ?? 0);
    setText('condition_review_due', conditionReports.review_due ?? 0);

    setText('museum_assets_total', digitalAssets.total_museum_assets ?? 0);
    setText('museum_assets_photos', digitalAssets.photos ?? 0);
    setText('museum_assets_documents', digitalAssets.documents ?? 0);

    renderLatest(data.latest?.condition_reports || [], 'condition_reports');
}

function renderStaffDashboard(data) {
    hideBlock('#admin-summary');
    hideBlock('#library-summary');
    hideBlock('#museum-summary');

    setText('dashboard_role', data.role);
    setText('generated_at', formatDateTime(data.generated_at));

    if (data.role === 'admin') {
        renderAdmin(data);
        return;
    }

    if (data.role === 'pustakawan') {
        renderLibrary(data);
        return;
    }

    if (data.role === 'kurator') {
        renderMuseum(data);
        return;
    }

    window.location.assign('/forbidden');
}

async function loadDashboard() {
    showLoading();

    try {
        const response = await api.get('/dashboard');
        const dashboard = response.data;

        renderStaffDashboard(dashboard);
        showContent();
    } catch (error) {
        const apiError = normalizeApiError(error);

        if (apiError.isUnauthenticated()) {
            window.location.assign('/login?redirect=/staff/dashboard');
            return;
        }

        if (apiError.isForbidden()) {
            window.location.assign('/forbidden');
            return;
        }

        showError(apiError.message || 'Terjadi kesalahan saat memuat dashboard.');
    }
}

loadDashboard();