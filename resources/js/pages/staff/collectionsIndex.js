import { createSimpbApiClient, userHasAnyRole, userHasRole } from '../../api/simpbApi';
import { normalizeApiError } from '../../api/apiErrors';

const api = createSimpbApiClient({
    baseUrl: '/api',
    tokenStorage: window.sessionStorage,
    onUnauthenticated: () => {
        api.clearAuth();
        window.location.assign('/login?redirect=/staff/collections');
    },
});

const form = document.querySelector('#staff-collection-filter-form');
const resetButton = document.querySelector('#staff-collection-reset-button');
const loading = document.querySelector('#staff-collection-loading');
const content = document.querySelector('#staff-collection-content');
const alertBox = document.querySelector('#staff-collection-alert');
const summary = document.querySelector('#staff-collection-summary');
const list = document.querySelector('#staff-collection-list');
const pagination = document.querySelector('#staff-collection-pagination');
const scopeText = document.querySelector('#staff-collection-scope-text');

const fields = [
    'q',
    'unit_type',
    'collection_type',
    'publication_status',
    'visibility',
    'category_id',
    'sort',
    'per_page',
    'include_trashed',
];

let currentUser = null;
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

function showAlert(title, message, type = 'error') {
    if (!alertBox) {
        return;
    }

    alertBox.style.display = '';
    alertBox.className = `card staff-collection-alert ${type}`;
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
    alertBox.className = 'card staff-collection-alert';
    alertBox.innerHTML = '';
}

function currentParams() {
    return new URLSearchParams(window.location.search);
}

function userScopeUnit() {
    if (!currentUser) {
        return null;
    }

    if (userHasRole(currentUser, 'admin')) {
        return null;
    }

    if (userHasRole(currentUser, 'pustakawan')) {
        return 'library';
    }

    if (userHasRole(currentUser, 'kurator')) {
        return 'museum';
    }

    return null;
}

function applyRoleScopeToForm() {
    const unitSelect = form?.elements?.unit_type;
    const scopeUnit = userScopeUnit();

    if (!unitSelect) {
        return;
    }

    if (scopeUnit) {
        unitSelect.value = scopeUnit;
        unitSelect.disabled = true;

        if (scopeText) {
            scopeText.textContent = scopeUnit === 'library'
                ? 'Anda login sebagai pustakawan. Daftar koleksi dibatasi pada unit perpustakaan.'
                : 'Anda login sebagai kurator. Daftar koleksi dibatasi pada unit museum.';
        }

        return;
    }

    unitSelect.disabled = false;

    if (scopeText) {
        scopeText.textContent = 'Anda login sebagai admin. Anda dapat melihat koleksi perpustakaan dan museum.';
    }
}

function setFieldValuesFromUrl() {
    const params = currentParams();

    fields.forEach((field) => {
        const element = form?.elements?.[field];

        if (!element) {
            return;
        }

        if (field === 'include_trashed') {
            element.checked = params.get(field) === '1' || params.get(field) === 'true';
            return;
        }

        const value = params.get(field);

        if (value !== null) {
            element.value = value;
        }
    });

    if (!params.get('sort') && form?.elements?.sort) {
        form.elements.sort.value = 'latest';
    }

    if (!params.get('per_page') && form?.elements?.per_page) {
        form.elements.per_page.value = '10';
    }

    applyRoleScopeToForm();
}

function buildQueryFromForm(page = 1) {
    const formData = new FormData(form);
    const query = {};

    fields.forEach((field) => {
        const element = form?.elements?.[field];

        if (!element) {
            return;
        }

        if (field === 'include_trashed') {
            if (element.checked) {
                query[field] = 1;
            }
            return;
        }

        const value = String(formData.get(field) ?? '').trim();

        if (value !== '') {
            query[field] = value;
        }
    });

    const scopeUnit = userScopeUnit();

    if (scopeUnit) {
        query.unit_type = scopeUnit;
    }

    query.page = page;

    return query;
}

function queryFromUrl() {
    const params = currentParams();
    const query = {};

    fields.forEach((field) => {
        if (field === 'include_trashed') {
            const value = params.get(field);

            if (value === '1' || value === 'true') {
                query[field] = 1;
            }

            return;
        }

        const value = params.get(field);

        if (value !== null && value !== '') {
            query[field] = value;
        }
    });

    if (!query.sort) {
        query.sort = 'latest';
    }

    if (!query.per_page) {
        query.per_page = 10;
    }

    query.page = params.get('page') || 1;

    const scopeUnit = userScopeUnit();

    if (scopeUnit) {
        query.unit_type = scopeUnit;
    }

    return query;
}

function queryToUrl(query) {
    const params = new URLSearchParams();

    Object.entries(query).forEach(([key, value]) => {
        if (value === null || value === undefined || value === '') {
            return;
        }

        params.set(key, value);
    });

    return `${window.location.pathname}?${params.toString()}`;
}

function normalizeCollectionResponse(response) {
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

    return {
        items: [],
        meta: response?.meta || null,
        links: response?.links || null,
    };
}

function displayTitle(item) {
    return item.display_title || item.title || 'Tanpa judul';
}

function itemDescription(item) {
    const text = item.description || '';

    if (text.length <= 220) {
        return text;
    }

    return `${text.substring(0, 220)}...`;
}

function categoryName(item) {
    return item.category?.name
        || item.category?.slug
        || item.category_name
        || '-';
}

function locationName(item) {
    return item.current_location?.name
        || item.currentLocation?.name
        || item.location?.name
        || item.location_name
        || '-';
}

function collectionIdentifier(item) {
    return item.record_code || item.ulid || item.id;
}

function detailUrl(item) {
    return `/staff/collections/${encodeURIComponent(collectionIdentifier(item))}`;
}

function isArchivedOrDeleted(item) {
    return Boolean(item.deleted_at)
        || Boolean(item.archived_at)
        || String(item.publication_status || '').toLowerCase() === 'archived';
}

function renderEmpty() {
    if (!list) {
        return;
    }

    list.innerHTML = `
        <section class="card">
            <h2>Tidak ada koleksi</h2>
            <p>
                Tidak ada koleksi yang cocok dengan filter saat ini.
            </p>
        </section>
    `;
}

function renderCollectionItem(item) {
    const unit = String(item.unit_type || '-').toLowerCase();
    const status = String(item.publication_status || '-').toLowerCase();
    const archivedClass = isArchivedOrDeleted(item) ? 'archived' : '';

    return `
        <article class="collection-item ${archivedClass}">
            <h2>${escapeHtml(displayTitle(item))}</h2>

            <div class="collection-meta">
                <span class="badge">${escapeHtml(item.record_code || '-')}</span>
                <span class="badge ${escapeHtml(unit)}">${escapeHtml(item.unit_type || '-')}</span>
                <span class="badge">${escapeHtml(item.collection_type || '-')}</span>
                <span class="badge ${escapeHtml(status)}">Status: ${escapeHtml(item.publication_status || '-')}</span>
                <span class="badge">Visibility: ${escapeHtml(item.visibility || '-')}</span>
                <span class="badge">Kategori: ${escapeHtml(categoryName(item))}</span>
                <span class="badge">Lokasi: ${escapeHtml(locationName(item))}</span>
                <span class="badge">Tahun: ${escapeHtml(item.date_display || item.year_start || '-')}</span>
            </div>

            <p class="collection-description">
                ${escapeHtml(itemDescription(item) || 'Tidak ada deskripsi ringkas.')}
            </p>

            <div class="collection-actions">
                <a class="button-link" href="${escapeHtml(detailUrl(item))}">
                    Detail Internal
                </a>

                <a class="button-link secondary" href="/catalog/collections/${encodeURIComponent(collectionIdentifier(item))}">
                    Lihat Publik
                </a>
            </div>
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

    list.innerHTML = items.map(renderCollectionItem).join('');
}

function renderSummary(meta, count) {
    if (!summary) {
        return;
    }

    if (meta) {
        const from = meta.from ?? 0;
        const to = meta.to ?? count;
        const total = meta.total ?? count;

        summary.textContent = `Menampilkan ${from}-${to} dari ${total} koleksi internal.`;
        return;
    }

    summary.textContent = `Menampilkan ${count} koleksi internal.`;
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
            loadCollections(targetPage, true);
        });
    });
}

async function loadCurrentUser() {
    const response = await api.me();

    currentUser = response.data;

    if (!userHasAnyRole(currentUser, ['admin', 'pustakawan', 'kurator'])) {
        window.location.assign('/forbidden');
        return false;
    }

    applyRoleScopeToForm();

    return true;
}

async function loadCollections(page = null, updateUrl = false) {
    showLoading();

    const query = page
        ? buildQueryFromForm(page)
        : queryFromUrl();

    currentPage = Number(query.page || 1);

    try {
        const response = await api.get('/staff/collections', {
            query,
        });

        const normalized = normalizeCollectionResponse(response);

        renderSummary(normalized.meta, normalized.items.length);
        renderList(normalized.items);
        renderPagination(normalized.meta);

        showContent();

        if (updateUrl) {
            window.history.pushState({}, '', queryToUrl(query));
        }
    } catch (error) {
        const apiError = normalizeApiError(error);

        if (apiError.isUnauthenticated()) {
            window.location.assign('/login?redirect=/staff/collections');
            return;
        }

        if (apiError.isForbidden()) {
            window.location.assign('/forbidden');
            return;
        }

        if (apiError.isValidationError()) {
            showAlert('Filter tidak valid', apiError.message || 'Parameter filter tidak valid.', 'warning');
            if (loading) {
                loading.style.display = 'none';
            }
            return;
        }

        if (loading) {
            loading.style.display = 'none';
        }

        showAlert('Koleksi internal gagal dimuat', apiError.message || 'Terjadi kesalahan saat memuat koleksi internal.');
    }
}

function handleSubmit(event) {
    event.preventDefault();

    loadCollections(1, true);
}

function handleReset() {
    fields.forEach((field) => {
        const element = form?.elements?.[field];

        if (!element) {
            return;
        }

        if (field === 'include_trashed') {
            element.checked = false;
            return;
        }

        element.value = '';
    });

    if (form?.elements?.sort) {
        form.elements.sort.value = 'latest';
    }

    if (form?.elements?.per_page) {
        form.elements.per_page.value = '10';
    }

    applyRoleScopeToForm();

    const query = buildQueryFromForm(1);

    window.history.pushState({}, '', queryToUrl(query));
    loadCollections(1, false);
}

window.addEventListener('popstate', () => {
    setFieldValuesFromUrl();
    loadCollections(null, false);
});

async function init() {
    showLoading();

    try {
        setFieldValuesFromUrl();

        const allowed = await loadCurrentUser();

        if (!allowed) {
            return;
        }

        await loadCollections(null, false);
    } catch (error) {
        const apiError = normalizeApiError(error);

        if (apiError.isUnauthenticated()) {
            window.location.assign('/login?redirect=/staff/collections');
            return;
        }

        if (apiError.isForbidden()) {
            window.location.assign('/forbidden');
            return;
        }

        if (loading) {
            loading.style.display = 'none';
        }

        showAlert('Halaman gagal dimuat', apiError.message || 'Terjadi kesalahan saat memuat halaman koleksi internal.');
    }
}

if (form) {
    form.addEventListener('submit', handleSubmit);
}

if (resetButton) {
    resetButton.addEventListener('click', handleReset);
}

init();