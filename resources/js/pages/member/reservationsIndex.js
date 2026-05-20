import { createSimpbApiClient } from '../../api/simpbApi';
import { normalizeApiError, validationErrorsToObject } from '../../api/apiErrors';

const api = createSimpbApiClient({
    baseUrl: '/api',
    tokenStorage: window.sessionStorage,
    onUnauthenticated: () => {
        api.clearAuth();
        window.location.assign('/login?redirect=/member/reservations');
    },
});

const form = document.querySelector('#reservation-create-form');
const recordCodeInput = document.querySelector('#record_code');
const notesInput = document.querySelector('#notes');
const createButton = document.querySelector('#reservation-create-button');
const refreshButton = document.querySelector('#reservation-refresh-button');

const loading = document.querySelector('#reservation-loading');
const content = document.querySelector('#reservation-content');
const alertBox = document.querySelector('#reservation-alert');
const summary = document.querySelector('#reservation-summary');
const list = document.querySelector('#reservation-list');
const pagination = document.querySelector('#reservation-pagination');

let currentPage = 1;
let busyReservationId = null;

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
    alertBox.className = `card reservation-alert ${type}`;
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
    alertBox.className = 'card reservation-alert';
    alertBox.innerHTML = '';
}

function clearFieldErrors() {
    document.querySelectorAll('[data-error-for]').forEach((element) => {
        element.textContent = '';
    });

    document.querySelectorAll('[aria-invalid="true"]').forEach((element) => {
        element.removeAttribute('aria-invalid');
    });
}

function showFieldErrors(details) {
    const errors = validationErrorsToObject(details);

    Object.entries(errors).forEach(([field, message]) => {
        const errorElement = document.querySelector(`[data-error-for="${field}"]`);
        const inputElement = document.querySelector(`[name="${field}"]`);

        if (errorElement) {
            errorElement.textContent = message;
        }

        if (inputElement) {
            inputElement.setAttribute('aria-invalid', 'true');
        }
    });
}

function normalizeReservationResponse(response) {
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

function reservationId(reservation) {
    return reservation.id || reservation.reservation_id;
}

function collectionFromReservation(reservation) {
    return reservation.collection || reservation.collection_data || {};
}

function collectionTitle(reservation) {
    const collection = collectionFromReservation(reservation);

    return reservation.collection_title
        || collection.display_title
        || collection.title
        || 'Tanpa judul';
}

function collectionRecordCode(reservation) {
    const collection = collectionFromReservation(reservation);

    return reservation.record_code
        || reservation.collection_record_code
        || collection.record_code
        || '-';
}

function collectionIdentifier(reservation) {
    const collection = collectionFromReservation(reservation);

    return collection.record_code
        || reservation.record_code
        || reservation.collection_record_code
        || collection.ulid
        || collection.id
        || reservation.collection_id;
}

function detailUrl(reservation) {
    return `/catalog/collections/${encodeURIComponent(collectionIdentifier(reservation))}`;
}

function reservationCode(reservation) {
    return reservation.code
        || reservation.reservation_code
        || reservation.transaction_code
        || `RSV-${reservationId(reservation) || '-'}`;
}

function statusLabel(status) {
    return status || '-';
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

function canCancel(reservation) {
    return ['active', 'notified'].includes(String(reservation.status || '').toLowerCase());
}

function renderSummary(meta, count) {
    if (!summary) {
        return;
    }

    if (meta) {
        const from = meta.from ?? 0;
        const to = meta.to ?? count;
        const total = meta.total ?? count;

        summary.textContent = `Menampilkan ${from}-${to} dari ${total} reservasi.`;
        return;
    }

    summary.textContent = `Menampilkan ${count} reservasi.`;
}

function renderEmpty() {
    if (!list) {
        return;
    }

    list.innerHTML = `
        <section class="card">
            <h2>Belum ada reservasi</h2>
            <p>
                Masukkan <code>record_code</code> koleksi perpustakaan pada form di atas
                untuk membuat reservasi.
            </p>
            <p>
                <a href="/catalog">Buka katalog</a>
            </p>
        </section>
    `;
}

function renderReservationItem(reservation) {
    const id = reservationId(reservation);
    const status = String(reservation.status || '').toLowerCase();
    const cancelable = canCancel(reservation);
    const notes = reservation.notes || reservation.member_notes || reservation.cancel_reason || '';

    return `
        <article class="reservation-item" data-reservation-item data-reservation-id="${escapeHtml(id)}">
            <h2>${escapeHtml(collectionTitle(reservation))}</h2>

            <div class="reservation-meta">
                <span class="badge">${escapeHtml(reservationCode(reservation))}</span>
                <span class="badge">${escapeHtml(collectionRecordCode(reservation))}</span>
                <span class="badge ${escapeHtml(status)}">Status: ${escapeHtml(statusLabel(reservation.status))}</span>
                <span class="badge">Antrean: ${escapeHtml(reservation.queue_position ?? '-')}</span>
                <span class="badge">Reserved: ${escapeHtml(formatDateTime(reservation.reserved_at || reservation.created_at))}</span>
                <span class="badge">Expired: ${escapeHtml(formatDateTime(reservation.expires_at))}</span>
            </div>

            ${notes ? `<p class="reservation-note">${escapeHtml(notes)}</p>` : ''}

            <div class="reservation-actions">
                <a class="button-link secondary" href="${escapeHtml(detailUrl(reservation))}">
                    Lihat Detail Koleksi
                </a>

                ${cancelable ? `
                    <button type="button" class="danger" data-reservation-cancel>
                        Batalkan Reservasi
                    </button>
                ` : ''}
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

    list.innerHTML = items.map(renderReservationItem).join('');

    list.querySelectorAll('[data-reservation-cancel]').forEach((button) => {
        button.addEventListener('click', handleCancelClick);
    });
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
            loadReservations(targetPage);
        });
    });
}

function setReservationBusy(id, isBusy) {
    busyReservationId = isBusy ? id : null;

    const item = Array.from(list?.querySelectorAll('[data-reservation-item]') || [])
        .find((element) => String(element.dataset.reservationId) === String(id));

    if (!item) {
        return;
    }

    item.querySelectorAll('button').forEach((button) => {
        button.disabled = isBusy;
    });
}

function setCreateBusy(isBusy) {
    if (!createButton) {
        return;
    }

    createButton.disabled = isBusy;
    createButton.textContent = isBusy ? 'Memproses...' : 'Buat Reservasi';
}

async function loadReservations(page = 1, mainLoading = true) {
    currentPage = page;

    if (mainLoading) {
        showLoading();
    } else {
        hideAlert();
    }

    try {
        const response = await api.get('/member/reservations', {
            query: {
                page,
            },
        });

        const normalized = normalizeReservationResponse(response);

        renderSummary(normalized.meta, normalized.items.length);
        renderList(normalized.items);
        renderPagination(normalized.meta);

        showContent();
    } catch (error) {
        const apiError = normalizeApiError(error);

        if (apiError.isUnauthenticated()) {
            window.location.assign('/login?redirect=/member/reservations');
            return;
        }

        if (apiError.isForbidden()) {
            window.location.assign('/forbidden');
            return;
        }

        if (loading) {
            loading.style.display = 'none';
        }

        showAlert('Reservasi gagal dimuat', apiError.message || 'Terjadi kesalahan saat memuat reservasi.');
    }
}

async function handleCreateSubmit(event) {
    event.preventDefault();

    clearFieldErrors();
    hideAlert();
    setCreateBusy(true);

    try {
        const recordCode = recordCodeInput?.value?.trim();
        const notes = notesInput?.value?.trim();

        await api.post('/member/reservations', {
            record_code: recordCode,
            notes: notes || null,
        });

        showAlert('Reservasi berhasil dibuat', 'Reservasi Anda berhasil dibuat.', 'success');

        if (form) {
            form.reset();
        }

        await loadReservations(1, false);
    } catch (error) {
        const apiError = normalizeApiError(error);

        if (apiError.isUnauthenticated()) {
            window.location.assign('/login?redirect=/member/reservations');
            return;
        }

        if (apiError.isValidationError()) {
            showFieldErrors(apiError.details);
            showAlert('Reservasi gagal dibuat', apiError.message || 'Data reservasi tidak valid.');
            return;
        }

        showAlert('Reservasi gagal dibuat', apiError.message || 'Reservasi melanggar aturan bisnis.');
    } finally {
        setCreateBusy(false);
    }
}

async function handleCancelClick(event) {
    const item = event.currentTarget.closest('[data-reservation-item]');
    const id = item?.dataset?.reservationId;

    if (!id || busyReservationId) {
        return;
    }

    const confirmed = window.confirm('Batalkan reservasi ini?');

    if (!confirmed) {
        return;
    }

    setReservationBusy(id, true);
    hideAlert();

    try {
        await api.post(`/member/reservations/${encodeURIComponent(id)}/cancel`, {
            reason: 'Dibatalkan oleh member dari halaman reservasi.',
        });

        showAlert('Reservasi dibatalkan', 'Reservasi berhasil dibatalkan.', 'success');

        await loadReservations(currentPage, false);
    } catch (error) {
        const apiError = normalizeApiError(error);

        if (apiError.isUnauthenticated()) {
            window.location.assign('/login?redirect=/member/reservations');
            return;
        }

        showAlert('Reservasi gagal dibatalkan', apiError.message || 'Terjadi kesalahan saat membatalkan reservasi.');
    } finally {
        setReservationBusy(id, false);
    }
}

if (form) {
    form.addEventListener('submit', handleCreateSubmit);
}

if (refreshButton) {
    refreshButton.addEventListener('click', () => {
        loadReservations(currentPage);
    });
}

loadReservations(1);