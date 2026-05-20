import { createSimpbApiClient } from '../../api/simpbApi';
import { normalizeApiError } from '../../api/apiErrors';

const api = createSimpbApiClient({
    baseUrl: '/api',
    tokenStorage: window.sessionStorage,
    onUnauthenticated: () => {
        api.clearAuth();
        window.location.assign('/login?redirect=/member/dashboard');
    },
});

const loading = document.querySelector('#member-dashboard-loading');
const content = document.querySelector('#member-dashboard-content');
const alertBox = document.querySelector('#member-dashboard-alert');

function setText(field, value) {
    const element = document.querySelector(`[data-field="${field}"]`);

    if (!element) {
        return;
    }

    element.textContent = value ?? '-';
}

function money(value) {
    const number = Number(value || 0);

    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(number);
}

function yesNo(value) {
    return value ? 'Ya' : 'Tidak';
}

function formatDate(value) {
    if (!value) {
        return '-';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
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

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function renderList(containerId, items, renderer, emptyText) {
    const container = document.querySelector(containerId);

    if (!container) {
        return;
    }

    if (!Array.isArray(items) || items.length === 0) {
        container.innerHTML = `<p>${escapeHtml(emptyText)}</p>`;
        return;
    }

    container.innerHTML = `
        <ul>
            ${items.map(renderer).join('')}
        </ul>
    `;
}

function renderMemberDashboard(data) {
    const membership = data.membership || {};
    const borrowings = data.borrowings || {};
    const fines = data.fines || {};
    const reservations = data.reservations || {};
    const bookmarks = data.bookmarks || {};

    setText('member_number', membership.member_number);
    setText('membership_status', membership.membership_status);
    setText('member_active_until', formatDate(membership.member_active_until));
    setText('can_borrow', yesNo(membership.can_borrow));
    setText('max_borrow_items', membership.max_borrow_items);
    setText('borrow_duration_days', membership.borrow_duration_days);

    setText('active_borrowings', borrowings.active_count ?? 0);
    setText('overdue_borrowings', borrowings.overdue_count ?? 0);
    setText('history_borrowings', borrowings.history_count ?? 0);

    setText('unpaid_fine_count', fines.unpaid_borrowing_count ?? 0);
    setText('unpaid_fine_total', money(fines.unpaid_total ?? 0));

    setText('active_reservations', reservations.active ?? 0);
    setText('notified_reservations', reservations.notified ?? 0);
    setText('bookmark_total', bookmarks.total ?? 0);

    renderList(
        '#active-borrowing-list',
        borrowings.active_items || [],
        (item) => `
            <li>
                <strong>${escapeHtml(item.collection?.title || 'Tanpa judul')}</strong>
                — jatuh tempo ${escapeHtml(formatDate(item.due_date))}
                — status ${escapeHtml(item.status)}
            </li>
        `,
        'Belum ada pinjaman aktif.'
    );

    renderList(
        '#reservation-list',
        reservations.latest || [],
        (item) => `
            <li>
                <strong>${escapeHtml(item.collection_title || 'Tanpa judul')}</strong>
                — ${escapeHtml(item.status)}
                — antrean ${escapeHtml(item.queue_position ?? '-')}
            </li>
        `,
        'Belum ada reservasi.'
    );

    renderList(
        '#bookmark-list',
        bookmarks.latest || [],
        (item) => `
            <li>
                <strong>${escapeHtml(item.title || 'Tanpa judul')}</strong>
                — ${escapeHtml(item.record_code || '-')}
                ${item.folder_name ? `— folder ${escapeHtml(item.folder_name)}` : ''}
            </li>
        `,
        'Belum ada bookmark.'
    );
}

async function loadDashboard() {
    showLoading();

    try {
        const response = await api.get('/dashboard');
        const dashboard = response.data;

        if (dashboard.role !== 'member') {
            window.location.assign('/forbidden');
            return;
        }

        renderMemberDashboard(dashboard);
        showContent();
    } catch (error) {
        const apiError = normalizeApiError(error);

        if (apiError.isUnauthenticated()) {
            window.location.assign('/login?redirect=/member/dashboard');
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