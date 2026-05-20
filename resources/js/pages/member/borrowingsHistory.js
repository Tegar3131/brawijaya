import { createSimpbApiClient } from '../../api/simpbApi';
import { normalizeApiError } from '../../api/apiErrors';

const api = createSimpbApiClient({
    baseUrl: '/api',
    tokenStorage: window.sessionStorage,
    onUnauthenticated: () => {
        api.clearAuth();
        window.location.assign('/login?redirect=/member/borrowings/history');
    },
});

const loading = document.querySelector('#history-borrowing-loading');
const content = document.querySelector('#history-borrowing-content');
const alertBox = document.querySelector('#history-borrowing-alert');
const summary = document.querySelector('#history-borrowing-summary');
const list = document.querySelector('#history-borrowing-list');
const pagination = document.querySelector('#history-borrowing-pagination');
const refreshButton = document.querySelector('#history-borrowing-refresh-button');

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
    alertBox.className = 'card borrowing-alert error';
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
    alertBox.className = 'card borrowing-alert';
    alertBox.innerHTML = '';
}

function normalizeHistoryResponse(response) {
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

function copyFromBorrowing(borrowing) {
    return borrowing.library_copy
        || borrowing.libraryCopy
        || borrowing.copy
        || {};
}

function collectionFromBorrowing(borrowing) {
    const copy = copyFromBorrowing(borrowing);

    return borrowing.collection
        || borrowing.collection_data
        || copy.collection
        || copy.collection_data
        || {};
}

function collectionTitle(borrowing) {
    const collection = collectionFromBorrowing(borrowing);

    return borrowing.collection_title
        || collection.display_title
        || collection.title
        || 'Tanpa judul';
}

function collectionIdentifier(borrowing) {
    const collection = collectionFromBorrowing(borrowing);

    return collection.record_code
        || borrowing.record_code
        || borrowing.collection_record_code
        || collection.ulid
        || collection.id
        || borrowing.collection_id
        || '#';
}

function collectionRecordCode(borrowing) {
    const collection = collectionFromBorrowing(borrowing);

    return collection.record_code
        || borrowing.record_code
        || borrowing.collection_record_code
        || '-';
}

function copyBarcode(borrowing) {
    const copy = copyFromBorrowing(borrowing);

    return copy.barcode
        || borrowing.barcode
        || copy.copy_number
        || '-';
}

function detailUrl(borrowing) {
    return `/catalog/collections/${encodeURIComponent(collectionIdentifier(borrowing))}`;
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

function money(value) {
    const number = Number(value || 0);

    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(number);
}

function fineDue(borrowing) {
    return Number(borrowing.fine_amount || 0) - Number(borrowing.fine_paid_amount || 0);
}

function isOverdue(borrowing) {
    const status = String(borrowing.status || '').toLowerCase();

    if (status === 'overdue') {
        return true;
    }

    if (!borrowing.due_date || ['returned', 'cancelled'].includes(status)) {
        return false;
    }

    const dueDate = new Date(borrowing.due_date);

    if (Number.isNaN(dueDate.getTime())) {
        return false;
    }

    return dueDate < new Date();
}

function detailRow(label, value) {
    return `
        <div class="borrowing-label">${escapeHtml(label)}</div>
        <div class="borrowing-value">${escapeHtml(value ?? '-')}</div>
    `;
}

function renderEmpty() {
    if (!list) {
        return;
    }

    list.innerHTML = `
        <section class="card">
            <h2>Belum ada riwayat pinjaman</h2>
            <p>Riwayat pinjaman Anda akan muncul di halaman ini.</p>
            <p><a href="/catalog">Buka katalog</a></p>
        </section>
    `;
}

function renderBorrowingItem(borrowing) {
    const overdue = isOverdue(borrowing);
    const status = String(borrowing.status || '-').toLowerCase();
    const remainingFine = fineDue(borrowing);

    return `
        <article class="borrowing-item ${overdue ? 'overdue' : ''}">
            <h2>${escapeHtml(collectionTitle(borrowing))}</h2>

            <div class="borrowing-meta">
                <span class="badge">${escapeHtml(borrowing.transaction_code || borrowing.code || `BRW-${borrowing.id || '-'}`)}</span>
                <span class="badge">${escapeHtml(collectionRecordCode(borrowing))}</span>
                <span class="badge ${escapeHtml(overdue ? 'overdue' : status)}">Status: ${escapeHtml(borrowing.status || '-')}</span>
                <span class="badge">Barcode: ${escapeHtml(copyBarcode(borrowing))}</span>
                <span class="badge">Pinjam: ${escapeHtml(formatDate(borrowing.borrowed_at))}</span>
                <span class="badge">Kembali: ${escapeHtml(formatDate(borrowing.returned_at))}</span>
            </div>

            <div class="borrowing-detail-grid">
                ${detailRow('Tanggal pinjam', formatDate(borrowing.borrowed_at))}
                ${detailRow('Tanggal jatuh tempo', formatDate(borrowing.due_date))}
                ${detailRow('Tanggal kembali', formatDate(borrowing.returned_at))}
                ${detailRow('Jumlah perpanjangan', borrowing.renewal_count ?? 0)}
                ${detailRow('Denda', money(borrowing.fine_amount || 0))}
                ${detailRow('Denda dibayar', money(borrowing.fine_paid_amount || 0))}
                ${detailRow('Sisa denda', money(remainingFine > 0 ? remainingFine : 0))}
            </div>

            <div class="borrowing-actions">
                <a class="button-link secondary" href="${escapeHtml(detailUrl(borrowing))}">
                    Lihat Detail Koleksi
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

    list.innerHTML = items.map(renderBorrowingItem).join('');
}

function renderSummary(meta, items) {
    if (!summary) {
        return;
    }

    if (meta) {
        const from = meta.from ?? 0;
        const to = meta.to ?? items.length;
        const total = meta.total ?? items.length;

        summary.textContent = `Menampilkan ${from}-${to} dari ${total} riwayat pinjaman.`;
        return;
    }

    summary.textContent = `Menampilkan ${items.length} riwayat pinjaman.`;
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
            loadHistory(targetPage);
        });
    });
}

async function loadHistory(page = 1) {
    currentPage = page;
    showLoading();

    try {
        const response = await api.get('/member/borrowings/history', {
            query: {
                page,
            },
        });

        const normalized = normalizeHistoryResponse(response);

        renderSummary(normalized.meta, normalized.items);
        renderList(normalized.items);
        renderPagination(normalized.meta);

        showContent();
    } catch (error) {
        const apiError = normalizeApiError(error);

        if (apiError.isUnauthenticated()) {
            window.location.assign('/login?redirect=/member/borrowings/history');
            return;
        }

        if (apiError.isForbidden()) {
            window.location.assign('/forbidden');
            return;
        }

        if (loading) {
            loading.style.display = 'none';
        }

        showAlert('Riwayat pinjaman gagal dimuat', apiError.message || 'Terjadi kesalahan saat memuat riwayat pinjaman.');
    }
}

if (refreshButton) {
    refreshButton.addEventListener('click', () => {
        loadHistory(currentPage);
    });
}

loadHistory(1);