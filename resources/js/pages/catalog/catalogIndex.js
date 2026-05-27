import { createSimpbApiClient } from '../../api/simpbApi';
import { normalizeApiError } from '../../api/apiErrors';

const api = createSimpbApiClient({
    baseUrl: '/api',
    tokenStorage: window.sessionStorage,
});

const form = document.querySelector('#catalog-search-form');
const resetButton = document.querySelector('#catalog-reset-button');
const loading = document.querySelector('#catalog-loading');
const content = document.querySelector('#catalog-content');
const alertBox = document.querySelector('#catalog-alert');
const resultsContainer = document.querySelector('#catalog-results');
const summary = document.querySelector('#catalog-summary');
const pagination = document.querySelector('#catalog-pagination');

const fields = [
    'q',
    'unit_type',
    'collection_type',
    'category_slug',
    'year_from',
    'year_to',
    'sort',
    'per_page',
];

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function currentParams() {
    return new URLSearchParams(window.location.search);
}

function setFieldValuesFromUrl() {
    const params = currentParams();

    fields.forEach((field) => {
        const element = form?.elements?.[field];

        if (!element) {
            return;
        }

        const value = params.get(field);

        if (value !== null) {
            element.value = value;
        }
    });

    if (!params.get('sort') && form?.elements?.sort) {
        form.elements.sort.value = 'relevance';
    }

    if (!params.get('per_page') && form?.elements?.per_page) {
        form.elements.per_page.value = '10';
    }
}

function buildQueryFromForm(page = 1) {
    const formData = new FormData(form);
    const query = {};

    fields.forEach((field) => {
        const value = String(formData.get(field) ?? '').trim();

        if (value !== '') {
            query[field] = value;
        }
    });

    query.page = page;

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

function queryFromUrl() {
    const params = currentParams();
    const query = {};

    fields.forEach((field) => {
        const value = params.get(field);

        if (value !== null && value !== '') {
            query[field] = value;
        }
    });

    query.page = params.get('page') || 1;

    if (!query.sort) {
        query.sort = 'relevance';
    }

    if (!query.per_page) {
        query.per_page = 10;
    }

    return query;
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
        alertBox.className = 'card';
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
        alertBox.className = 'card alert-error';
        alertBox.innerHTML = `
            <h2>Katalog gagal dimuat</h2>
            <p>${escapeHtml(message)}</p>
        `;
    }
}

function collectionUrl(item) {
    const identifier = item.record_code || item.ulid || item.id;

    return `/catalog/collections/${encodeURIComponent(identifier)}`;
}

function displayTitle(item) {
    return item.display_title || item.title || 'Tanpa judul';
}

function itemDescription(item) {
    const description = item.description || '';

    if (description.length <= 220) {
        return description;
    }

    return `${description.substring(0, 220)}...`;
}

function renderCreators(item) {
    if (!Array.isArray(item.creators) || item.creators.length === 0) {
        return '';
    }

    const names = item.creators
        .map((creator) => creator.name)
        .filter(Boolean)
        .slice(0, 3)
        .join(', ');

    return names ? `<span class="badge">Creator: ${escapeHtml(names)}</span>` : '';
}

function renderSubjects(item) {
    if (!Array.isArray(item.subjects) || item.subjects.length === 0) {
        return '';
    }

    const terms = item.subjects
        .map((subject) => subject.term)
        .filter(Boolean)
        .slice(0, 3)
        .join(', ');

    return terms ? `<span class="badge">Subject: ${escapeHtml(terms)}</span>` : '';
}

function renderItem(item) {
    const category = item.category?.name || item.category?.slug || '-';
    const title = displayTitle(item);
    const detailUrl = collectionUrl(item);

    return `
        <article class="catalog-item">
            <h2>${escapeHtml(title)}</h2>

            <div class="catalog-meta">
                <span class="badge">${escapeHtml(item.record_code || '-')}</span>
                <span class="badge">${escapeHtml(item.unit_type || '-')}</span>
                <span class="badge">${escapeHtml(item.collection_type || '-')}</span>
                <span class="badge">Kategori: ${escapeHtml(category)}</span>
                <span class="badge">Tahun: ${escapeHtml(item.date_display || item.year_start || '-')}</span>
                ${renderCreators(item)}
                ${renderSubjects(item)}
            </div>

            <p class="catalog-description">
                ${escapeHtml(itemDescription(item) || 'Tidak ada deskripsi ringkas.')}
            </p>

            <a class="button-link" href="${detailUrl}">
                Lihat Detail
            </a>
        </article>
    `;
}

function renderResults(items) {
    if (!resultsContainer) {
        return;
    }

    if (!Array.isArray(items) || items.length === 0) {
        resultsContainer.innerHTML = `
            <section class="card">
                <h2>Tidak ada hasil</h2>
                <p>Coba ubah keyword atau filter pencarian.</p>
            </section>
        `;
        return;
    }

    resultsContainer.innerHTML = items.map(renderItem).join('');
}

function renderSummary(meta) {
    if (!summary) {
        return;
    }

    if (!meta) {
        summary.textContent = '-';
        return;
    }

    const from = meta.from ?? 0;
    const to = meta.to ?? 0;
    const total = meta.total ?? 0;

    summary.textContent = `Menampilkan ${from}-${to} dari ${total} koleksi.`;
}

function renderPagination(meta) {
    if (!pagination) {
        return;
    }

    if (!meta || Number(meta.total || 0) === 0) {
        pagination.innerHTML = '';
        return;
    }

    const currentPage = Number(meta.current_page || 1);
    const lastPage = Number(meta.last_page || 1);

    pagination.innerHTML = `
        <div>
            Halaman <strong>${currentPage}</strong> dari <strong>${lastPage}</strong>
        </div>

        <div class="pagination-buttons">
            <button type="button" data-page="1" ${currentPage <= 1 ? 'disabled' : ''}>
                Awal
            </button>
            <button type="button" data-page="${currentPage - 1}" ${currentPage <= 1 ? 'disabled' : ''}>
                Sebelumnya
            </button>
            <button type="button" data-page="${currentPage + 1}" ${currentPage >= lastPage ? 'disabled' : ''}>
                Berikutnya
            </button>
            <button type="button" data-page="${lastPage}" ${currentPage >= lastPage ? 'disabled' : ''}>
                Akhir
            </button>
        </div>
    `;

    pagination.querySelectorAll('[data-page]').forEach((button) => {
        button.addEventListener('click', () => {
            const page = Number(button.dataset.page || 1);

            loadCatalog(page, true);
        });
    });
}

async function loadCatalog(page = null, updateUrl = false) {
    showLoading();

    const query = page
        ? buildQueryFromForm(page)
        : queryFromUrl();

    try {
        const response = await api.get('/catalog/search', {
            auth: false,
            query,
        });

        renderResults(response.data || []);
        renderSummary(response.meta || null);
        renderPagination(response.meta || null);
        showContent();

        if (updateUrl) {
            window.history.pushState({}, '', queryToUrl(query));
        }
    } catch (error) {
        const apiError = normalizeApiError(error);
        showError(apiError.message || 'Terjadi kesalahan saat mengambil katalog.');
    }
}

function handleSubmit(event) {
    event.preventDefault();

    loadCatalog(1, true);
}

function handleReset() {
    fields.forEach((field) => {
        const element = form?.elements?.[field];

        if (!element) {
            return;
        }

        element.value = '';
    });

    if (form?.elements?.sort) {
        form.elements.sort.value = 'relevance';
    }

    if (form?.elements?.per_page) {
        form.elements.per_page.value = '10';
    }

    window.history.pushState({}, '', window.location.pathname);
    loadCatalog(1, false);
}

window.addEventListener('popstate', () => {
    setFieldValuesFromUrl();
    loadCatalog(null, false);
});

const catalogTitles = {
    museum: {
        title: 'Katalog Museum',
        description: 'Telusuri koleksi artefak, foto, dan benda bersejarah dari Museum Brawijaya.',
    },
    library: {
        title: 'Katalog Perpustakaan',
        description: 'Cari koleksi buku, jurnal, dan literatur di Perpustakaan Brawijaya.',
    },
    default: {
        title: 'Katalog Museum dan Perpustakaan',
        description: 'Cari koleksi perpustakaan dan museum yang sudah dipublikasikan.',
    },
};

function updatePageTitle() {
    const unitType = form?.elements?.unit_type?.value || '';
    const config = catalogTitles[unitType] || catalogTitles.default;

    const titleEl = document.querySelector('#catalog-page-title');
    const descEl = document.querySelector('#catalog-page-description');

    if (titleEl) {
        titleEl.textContent = config.title;
    }

    if (descEl) {
        descEl.textContent = config.description;
    }

    document.title = `${config.title} - SIMPB`;
}

setFieldValuesFromUrl();
updatePageTitle();

if (form) {
    form.addEventListener('submit', (event) => {
        handleSubmit(event);
        updatePageTitle();
    });
}

if (resetButton) {
    resetButton.addEventListener('click', () => {
        handleReset();
        updatePageTitle();
    });
}

// Also update title when unit_type dropdown changes
const unitTypeSelect = form?.elements?.unit_type;
if (unitTypeSelect) {
    unitTypeSelect.addEventListener('change', updatePageTitle);
}

loadCatalog(null, false);