import { createSimpbApiClient } from '../../api/simpbApi';
import { normalizeApiError } from '../../api/apiErrors';

const api = createSimpbApiClient({
    baseUrl: '/api',
    tokenStorage: window.sessionStorage,
    onUnauthenticated: () => {
        api.clearAuth();
        window.location.assign('/login?redirect=' + encodeURIComponent(window.location.pathname));
    },
});

const root = document.querySelector('#staff-version-root');
const identifier = root?.dataset?.identifier;

const loading = document.querySelector('#staff-version-loading');
const content = document.querySelector('#staff-version-content');
const alertBox = document.querySelector('#staff-version-alert');
const summary = document.querySelector('#staff-version-summary');
const list = document.querySelector('#staff-version-list');
const pagination = document.querySelector('#staff-version-pagination');
const refreshButton = document.querySelector('#staff-version-refresh-button');

let currentPage = 1;

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

    hideAlert();
}

function showContent() {
    if (loading) {
        loading.style.display = 'none';
    }

    if (content) {
        content.style.display = '';
    }
}

function showAlert(title, message) {
    if (!alertBox) {
        return;
    }

    alertBox.style.display = '';
    alertBox.className = 'card staff-version-alert error';
    alertBox.innerHTML = `
        <h2>${escapeHtml(title)}</h2>
        <p>${escapeHtml(message)}</p>
    `;
}

function hideAlert() {
    if (!alertBox) {
        return;
    }

    alertBox.style.display = 'none';
    alertBox.className = 'card staff-version-alert';
    alertBox.innerHTML = '';
}

function normalizePaginatedResponse(response) {
    if (Array.isArray(response?.data)) {
        return {
            items: response.data,
            meta: response.meta || null,
            links: response.links || null,
        };
    }

    if (Array.isArray(response?.data?.data)) {
        return {
            items: response.data.data,
            meta: response.data.meta || response.meta || null,
            links: response.data.links || response.links || null,
        };
    }

    if (Array.isArray(response?.versions)) {
        return {
            items: response.versions,
            meta: response.meta || null,
            links: response.links || null,
        };
    }

    if (Array.isArray(response?.data?.versions)) {
        return {
            items: response.data.versions,
            meta: response.data.meta || response.meta || null,
            links: response.data.links || response.links || null,
        };
    }

    return {
        items: [],
        meta: response?.meta || null,
        links: response?.links || null,
    };
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

function actorName(version) {
    return version.user?.name
        || version.actor?.name
        || version.created_by_user?.name
        || version.updated_by_user?.name
        || version.user_name
        || version.actor_name
        || '-';
}

function eventName(version) {
    return version.event
        || version.change_type
        || version.action
        || version.version_type
        || 'snapshot';
}

function reasonText(version) {
    return version.reason
        || version.change_reason
        || version.notes
        || version.description
        || '-';
}

function versionNumber(version) {
    return version.version_number
        || version.version_no
        || version.revision
        || version.id
        || '-';
}

function snapshotObject(version) {
    return version.snapshot
        || version.snapshot_json
        || version.data
        || version.payload
        || version.collection_snapshot
        || null;
}

function changesObject(version) {
    return version.changes
        || version.diff
        || version.changed_fields
        || null;
}

function stringifyJson(value) {
    if (value === null || value === undefined || value === '') {
        return '-';
    }

    if (typeof value === 'string') {
        try {
            return JSON.stringify(JSON.parse(value), null, 2);
        } catch {
            return value;
        }
    }

    return JSON.stringify(value, null, 2);
}

function detailRow(label, value) {
    return `
        <div class="detail-label">${escapeHtml(label)}</div>
        <div class="detail-value">${escapeHtml(value ?? '-')}</div>
    `;
}

function badgeClass(value) {
    return String(value || '')
        .toLowerCase()
        .replaceAll('.', '-')
        .replaceAll('_', '-')
        .split('-')
        .pop();
}

function renderEmpty() {
    if (!list) {
        return;
    }

    list.innerHTML = `
        <section class="card">
            <h2>Belum ada version history</h2>
            <p>Belum ada riwayat versi untuk koleksi ini.</p>
        </section>
    `;
}

function renderVersionItem(version) {
    const event = eventName(version);
    const snapshot = snapshotObject(version);
    const changes = changesObject(version);

    return `
        <article class="version-item">
            <h2>Versi ${escapeHtml(versionNumber(version))}</h2>

            <div class="version-meta">
                <span class="badge ${escapeHtml(badgeClass(event))}">Event: ${escapeHtml(event)}</span>
                <span class="badge snapshot">Snapshot</span>
                <span class="badge">Actor: ${escapeHtml(actorName(version))}</span>
                <span class="badge">Waktu: ${escapeHtml(formatDateTime(version.created_at))}</span>
            </div>

            <div class="detail-grid">
                ${detailRow('ID', version.id)}
                ${detailRow('Version', versionNumber(version))}
                ${detailRow('Event', event)}
                ${detailRow('Reason', reasonText(version))}
                ${detailRow('Actor', actorName(version))}
                ${detailRow('Created At', formatDateTime(version.created_at))}
            </div>

            <details>
                <summary>Changes / Diff</summary>
                <pre>${escapeHtml(stringifyJson(changes))}</pre>
            </details>

            <details>
                <summary>Snapshot</summary>
                <pre>${escapeHtml(stringifyJson(snapshot))}</pre>
            </details>
        </article>
    `;
}

function renderList(items) {
    if (!list) {
        return;
    }

    if (!Array.isArray(items) || items.length === 0) {
        renderEmpty();
        return;
    }

    list.innerHTML = items.map(renderVersionItem).join('');
}

function renderSummary(meta, count) {
    if (!summary) {
        return;
    }

    if (meta) {
        const from = meta.from ?? 0;
        const to = meta.to ?? count;
        const total = meta.total ?? count;

        summary.textContent = `Menampilkan ${from}-${to} dari ${total} versi.`;
        return;
    }

    summary.textContent = `Menampilkan ${count} versi.`;
}

function renderPagination(meta) {
    if (!pagination) {
        return;
    }

    if (!meta || Number(meta.total || 0) === 0) {
        pagination.innerHTML = '';
        pagination.style.display = 'none';
        return;
    }

    pagination.style.display = '';

    const page = Number(meta.current_page || 1);
    const lastPage = Number(meta.last_page || 1);

    pagination.innerHTML = `
        <div>
            Halaman <strong>${page}</strong> dari <strong>${lastPage}</strong>
        </div>

        <div class="pagination-buttons">
            <button type="button" data-page="1" ${page <= 1 ? 'disabled' : ''}>
                Awal
            </button>

            <button type="button" data-page="${page - 1}" ${page <= 1 ? 'disabled' : ''}>
                Sebelumnya
            </button>

            <button type="button" data-page="${page + 1}" ${page >= lastPage ? 'disabled' : ''}>
                Berikutnya
            </button>

            <button type="button" data-page="${lastPage}" ${page >= lastPage ? 'disabled' : ''}>
                Akhir
            </button>
        </div>
    `;

    pagination.querySelectorAll('[data-page]').forEach((button) => {
        button.addEventListener('click', () => {
            const targetPage = Number(button.dataset.page || 1);
            loadVersions(targetPage);
        });
    });
}

async function loadVersions(page = 1) {
    currentPage = page;

    if (!identifier) {
        showAlert('Identifier kosong', 'Halaman tidak memiliki identifier koleksi.');
        return;
    }

    showLoading();

    try {
        const response = await api.get(`/staff/collections/${encodeURIComponent(identifier)}/versions`, {
            query: {
                page,
            },
        });

        const normalized = normalizePaginatedResponse(response);

        renderSummary(normalized.meta, normalized.items.length);
        renderList(normalized.items);
        renderPagination(normalized.meta);

        showContent();
    } catch (error) {
        const apiError = normalizeApiError(error);

        if (apiError.isUnauthenticated()) {
            window.location.assign('/login?redirect=' + encodeURIComponent(window.location.pathname));
            return;
        }

        if (apiError.isForbidden()) {
            window.location.assign('/forbidden');
            return;
        }

        if (loading) {
            loading.style.display = 'none';
        }

        if (apiError.isNotFound()) {
            showAlert('Koleksi tidak ditemukan', 'Koleksi tidak ditemukan atau tidak dapat diakses.');
            return;
        }

        showAlert('Version history gagal dimuat', apiError.message || 'Terjadi kesalahan saat memuat version history.');
    }
}

if (refreshButton) {
    refreshButton.addEventListener('click', () => {
        loadVersions(currentPage);
    });
}

loadVersions(1);