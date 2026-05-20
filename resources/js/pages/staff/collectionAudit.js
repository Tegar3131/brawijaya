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

const root = document.querySelector('#staff-audit-root');
const identifier = root?.dataset?.identifier;

const loading = document.querySelector('#staff-audit-loading');
const content = document.querySelector('#staff-audit-content');
const alertBox = document.querySelector('#staff-audit-alert');
const summary = document.querySelector('#staff-audit-summary');
const list = document.querySelector('#staff-audit-list');
const pagination = document.querySelector('#staff-audit-pagination');
const refreshButton = document.querySelector('#staff-audit-refresh-button');

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
    alertBox.className = 'card staff-audit-alert error';
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
    alertBox.className = 'card staff-audit-alert';
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

    if (Array.isArray(response?.audit_logs)) {
        return {
            items: response.audit_logs,
            meta: response.meta || null,
            links: response.links || null,
        };
    }

    if (Array.isArray(response?.data?.audit_logs)) {
        return {
            items: response.data.audit_logs,
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

function actorName(log) {
    return log.user?.name
        || log.actor?.name
        || log.causer?.name
        || log.created_by_user?.name
        || log.user_name
        || log.actor_name
        || '-';
}

function actorEmail(log) {
    return log.user?.email
        || log.actor?.email
        || log.causer?.email
        || log.created_by_user?.email
        || log.user_email
        || log.actor_email
        || '';
}

function eventName(log) {
    return log.event
        || log.description
        || log.action
        || 'audit.event';
}

function actionName(log) {
    return log.action
        || log.event_type
        || '-';
}

function moduleName(log) {
    return log.module
        || log.subject_type
        || log.auditable_type
        || 'collection';
}

function metadataObject(log) {
    return log.metadata
        || log.properties
        || log.changes
        || log.old_values
        || log.new_values
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
            <h2>Belum ada audit log</h2>
            <p>Belum ada riwayat audit untuk koleksi ini.</p>
        </section>
    `;
}

function renderAuditItem(log) {
    const event = eventName(log);
    const action = actionName(log);
    const actor = actorName(log);
    const email = actorEmail(log);
    const metadata = metadataObject(log);

    return `
        <article class="audit-item">
            <h2>${escapeHtml(event)}</h2>

            <div class="audit-meta">
                <span class="badge ${escapeHtml(badgeClass(action))}">Action: ${escapeHtml(action)}</span>
                <span class="badge">Module: ${escapeHtml(moduleName(log))}</span>
                <span class="badge">Actor: ${escapeHtml(actor)}</span>
                <span class="badge">Waktu: ${escapeHtml(formatDateTime(log.created_at))}</span>
            </div>

            <div class="detail-grid">
                ${detailRow('ID', log.id)}
                ${detailRow('Event', event)}
                ${detailRow('Action', action)}
                ${detailRow('Module', moduleName(log))}
                ${detailRow('Actor', email ? `${actor} <${email}>` : actor)}
                ${detailRow('IP Address', log.ip_address || log.ip)}
                ${detailRow('User Agent', log.user_agent)}
                ${detailRow('Created At', formatDateTime(log.created_at))}
            </div>

            <details>
                <summary>Metadata / Payload</summary>
                <pre>${escapeHtml(stringifyJson(metadata))}</pre>
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

    list.innerHTML = items.map(renderAuditItem).join('');
}

function renderSummary(meta, count) {
    if (!summary) {
        return;
    }

    if (meta) {
        const from = meta.from ?? 0;
        const to = meta.to ?? count;
        const total = meta.total ?? count;

        summary.textContent = `Menampilkan ${from}-${to} dari ${total} audit log.`;
        return;
    }

    summary.textContent = `Menampilkan ${count} audit log.`;
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
            loadAuditLogs(targetPage);
        });
    });
}

async function loadAuditLogs(page = 1) {
    currentPage = page;

    if (!identifier) {
        showAlert('Identifier kosong', 'Halaman tidak memiliki identifier koleksi.');
        return;
    }

    showLoading();

    try {
        const response = await api.get(`/staff/collections/${encodeURIComponent(identifier)}/audit-logs`, {
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

        showAlert('Audit log gagal dimuat', apiError.message || 'Terjadi kesalahan saat memuat audit log.');
    }
}

if (refreshButton) {
    refreshButton.addEventListener('click', () => {
        loadAuditLogs(currentPage);
    });
}

loadAuditLogs(1);