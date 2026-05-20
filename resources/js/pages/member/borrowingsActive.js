import { createSimpbApiClient } from '../../api/simpbApi';
import { normalizeApiError } from '../../api/apiErrors';

const api = createSimpbApiClient({
    baseUrl: '/api',
    tokenStorage: window.sessionStorage,
    onUnauthenticated: () => {
        api.clearAuth();
        window.location.assign('/login?redirect=/member/borrowings');
    },
});

const loading = document.querySelector('#active-borrowing-loading');
const content = document.querySelector('#active-borrowing-content');
const alertBox = document.querySelector('#active-borrowing-alert');
const summary = document.querySelector('#active-borrowing-summary');
const list = document.querySelector('#active-borrowing-list');
const refreshButton = document.querySelector('#active-borrowing-refresh-button');

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

function normalizeBorrowingResponse(response) {
    if (Array.isArray(response?.data)) {
        return response.data;
    }

    if (Array.isArray(response?.data?.data)) {
        return response.data.data;
    }

    if (Array.isArray(response?.borrowings)) {
        return response.borrowings;
    }

    return [];
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
            <h2>Tidak ada pinjaman aktif</h2>
            <p>
                Anda belum memiliki pinjaman aktif saat ini.
            </p>
            <p>
                <a href="/catalog">Buka katalog</a>
            </p>
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
                <span class="badge">Jatuh tempo: ${escapeHtml(formatDate(borrowing.due_date))}</span>
            </div>

            <div class="borrowing-detail-grid">
                ${detailRow('Tanggal pinjam', formatDate(borrowing.borrowed_at))}
                ${detailRow('Tanggal jatuh tempo', formatDate(borrowing.due_date))}
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

function renderSummary(items) {
    if (!summary) {
        return;
    }

    const total = items.length;
    const overdue = items.filter(isOverdue).length;
    const fineTotal = items.reduce((sum, item) => sum + Math.max(fineDue(item), 0), 0);

    summary.textContent = `Total ${total} pinjaman aktif, ${overdue} terlambat, sisa denda ${money(fineTotal)}.`;
}

async function loadActiveBorrowings() {
    showLoading();

    try {
        const response = await api.get('/member/borrowings/active');
        const items = normalizeBorrowingResponse(response);

        renderSummary(items);
        renderList(items);
        showContent();
    } catch (error) {
        const apiError = normalizeApiError(error);

        if (apiError.isUnauthenticated()) {
            window.location.assign('/login?redirect=/member/borrowings');
            return;
        }

        if (apiError.isForbidden()) {
            window.location.assign('/forbidden');
            return;
        }

        if (loading) {
            loading.style.display = 'none';
        }

        showAlert('Pinjaman aktif gagal dimuat', apiError.message || 'Terjadi kesalahan saat memuat pinjaman aktif.');
    }
}

if (refreshButton) {
    refreshButton.addEventListener('click', loadActiveBorrowings);
}

loadActiveBorrowings();