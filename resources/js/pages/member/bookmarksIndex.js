import { createSimpbApiClient } from '../../api/simpbApi';
import { normalizeApiError } from '../../api/apiErrors';

const api = createSimpbApiClient({
    baseUrl: '/api',
    tokenStorage: window.sessionStorage,
    onUnauthenticated: () => {
        api.clearAuth();
        window.location.assign('/login?redirect=/member/bookmarks');
    },
});

const loading = document.querySelector('#bookmark-loading');
const content = document.querySelector('#bookmark-content');
const alertBox = document.querySelector('#bookmark-alert');
const summary = document.querySelector('#bookmark-summary');
const list = document.querySelector('#bookmark-list');
const pagination = document.querySelector('#bookmark-pagination');
const refreshButton = document.querySelector('#bookmark-refresh-button');
const folderList = document.querySelector('#bookmark-folder-list');
const activeFolderBox = document.querySelector('#bookmark-active-folder');

let allBookmarks = [];
let activeFolderKey = '__all__';
let busyBookmarkIdentifier = null;

const ALL_FOLDER = '__all__';
const NO_FOLDER = '__no_folder__';

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
    alertBox.className = `card bookmark-alert ${type}`;
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
    alertBox.className = 'card bookmark-alert';
    alertBox.innerHTML = '';
}

function normalizeBookmarkResponse(response) {
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

function collectionFromBookmark(bookmark) {
    return bookmark.collection || bookmark;
}

function bookmarkIdentifier(bookmark) {
    const collection = collectionFromBookmark(bookmark);

    return collection.record_code
        || bookmark.record_code
        || bookmark.collection_record_code
        || collection.ulid
        || bookmark.collection_id
        || collection.id
        || bookmark.id;
}

function detailUrl(bookmark) {
    const collection = collectionFromBookmark(bookmark);
    const identifier = collection.record_code
        || bookmark.record_code
        || collection.ulid
        || collection.id;

    return `/catalog/collections/${encodeURIComponent(identifier)}`;
}

function displayTitle(bookmark) {
    const collection = collectionFromBookmark(bookmark);

    return collection.display_title
        || collection.title
        || bookmark.title
        || 'Tanpa judul';
}

function recordCode(bookmark) {
    const collection = collectionFromBookmark(bookmark);

    return collection.record_code
        || bookmark.record_code
        || bookmark.collection_record_code
        || '-';
}

function collectionType(bookmark) {
    const collection = collectionFromBookmark(bookmark);

    return collection.collection_type || bookmark.collection_type || '-';
}

function unitType(bookmark) {
    const collection = collectionFromBookmark(bookmark);

    return collection.unit_type || bookmark.unit_type || '-';
}

function folderName(bookmark) {
    return bookmark.folder_name
        || bookmark.pivot?.folder_name
        || bookmark.bookmark?.folder_name
        || '';
}

function normalizedFolderName(bookmark) {
    return String(folderName(bookmark) || '').trim();
}

function folderKeyFromName(name) {
    const trimmed = String(name || '').trim();

    return trimmed === '' ? NO_FOLDER : trimmed;
}

function folderLabelFromKey(key) {
    if (key === ALL_FOLDER) {
        return 'Semua Bookmark';
    }

    if (key === NO_FOLDER) {
        return 'Tanpa Folder';
    }

    return key;
}

function notes(bookmark) {
    return bookmark.notes
        || bookmark.pivot?.notes
        || bookmark.bookmark?.notes
        || '';
}

function bookmarkedAt(bookmark) {
    return bookmark.bookmarked_at
        || bookmark.created_at
        || bookmark.pivot?.created_at
        || bookmark.bookmark?.created_at
        || null;
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

function folderStats(items) {
    const map = new Map();

    items.forEach((bookmark) => {
        const key = folderKeyFromName(normalizedFolderName(bookmark));
        const current = map.get(key) || 0;

        map.set(key, current + 1);
    });

    const folders = Array.from(map.entries())
        .map(([key, count]) => ({
            key,
            label: folderLabelFromKey(key),
            count,
        }))
        .sort((a, b) => {
            if (a.key === NO_FOLDER) {
                return 1;
            }

            if (b.key === NO_FOLDER) {
                return -1;
            }

            return a.label.localeCompare(b.label, 'id-ID');
        });

    return [
        {
            key: ALL_FOLDER,
            label: 'Semua Bookmark',
            count: items.length,
        },
        ...folders,
    ];
}

function filteredBookmarks() {
    if (activeFolderKey === ALL_FOLDER) {
        return allBookmarks;
    }

    return allBookmarks.filter((bookmark) => {
        const key = folderKeyFromName(normalizedFolderName(bookmark));

        return key === activeFolderKey;
    });
}

function renderSummary() {
    if (!summary) {
        return;
    }

    const filtered = filteredBookmarks();

    if (activeFolderKey === ALL_FOLDER) {
        summary.textContent = `Total ${allBookmarks.length} bookmark.`;
        return;
    }

    summary.textContent = `Menampilkan ${filtered.length} bookmark dalam folder "${folderLabelFromKey(activeFolderKey)}".`;
}

function renderActiveFolderBox() {
    if (!activeFolderBox) {
        return;
    }

    const filtered = filteredBookmarks();
    const label = folderLabelFromKey(activeFolderKey);

    activeFolderBox.innerHTML = `
        <h2>${escapeHtml(label)}</h2>
        <p>
            ${activeFolderKey === ALL_FOLDER
                ? `Menampilkan semua ${allBookmarks.length} bookmark.`
                : `Menampilkan ${filtered.length} bookmark dalam folder ini.`
            }
        </p>
    `;
}

function renderFolderList() {
    if (!folderList) {
        return;
    }

    const stats = folderStats(allBookmarks);

    folderList.innerHTML = stats.map((folder) => `
        <button
            type="button"
            class="folder-button ${folder.key === activeFolderKey ? 'active' : ''}"
            data-folder-key="${escapeHtml(folder.key)}"
        >
            <span>${escapeHtml(folder.label)}</span>
            <span class="folder-count">${folder.count}</span>
        </button>
    `).join('');

    folderList.querySelectorAll('[data-folder-key]').forEach((button) => {
        button.addEventListener('click', () => {
            activeFolderKey = button.dataset.folderKey || ALL_FOLDER;
            renderBookmarkScreen();
        });
    });
}

function renderEmpty() {
    if (!list) {
        return;
    }

    const message = activeFolderKey === ALL_FOLDER
        ? 'Belum ada bookmark.'
        : `Belum ada bookmark dalam folder "${folderLabelFromKey(activeFolderKey)}".`;

    list.innerHTML = `
        <section class="card">
            <h2>${escapeHtml(message)}</h2>
            <p>
                Buka halaman detail koleksi, lalu klik tombol
                <strong>Tambah Bookmark</strong>.
            </p>
            <p>
                <a href="/catalog">Buka katalog</a>
            </p>
        </section>
    `;
}

function renderBookmarkItem(bookmark) {
    const identifier = bookmarkIdentifier(bookmark);
    const folder = folderName(bookmark);
    const note = notes(bookmark);

    return `
        <article class="bookmark-item" data-bookmark-item data-identifier="${escapeHtml(identifier)}">
            <h2>${escapeHtml(displayTitle(bookmark))}</h2>

            <div class="bookmark-meta">
                <span class="badge">${escapeHtml(recordCode(bookmark))}</span>
                <span class="badge">${escapeHtml(unitType(bookmark))}</span>
                <span class="badge">${escapeHtml(collectionType(bookmark))}</span>
                <span class="badge">Folder: ${escapeHtml(folder || 'Tanpa Folder')}</span>
                <span class="badge">Ditambahkan: ${escapeHtml(formatDate(bookmarkedAt(bookmark)))}</span>
            </div>

            <div class="bookmark-form">
                <div>
                    <label>Folder</label>
                    <input
                        type="text"
                        data-bookmark-folder
                        value="${escapeHtml(folder)}"
                        placeholder="Contoh: Favorit, Riset, Bacaan nanti"
                    >
                </div>

                <div>
                    <label>Catatan</label>
                    <textarea
                        rows="3"
                        data-bookmark-notes
                        placeholder="Catatan pribadi"
                    >${escapeHtml(note)}</textarea>
                </div>
            </div>

            <div class="bookmark-actions">
                <a class="button-link secondary" href="${escapeHtml(detailUrl(bookmark))}">
                    Lihat Detail
                </a>

                <button type="button" data-bookmark-update>
                    Simpan Perubahan
                </button>

                <button type="button" class="danger" data-bookmark-delete>
                    Hapus Bookmark
                </button>
            </div>
        </article>
    `;
}

function renderList() {
    if (!list) {
        return;
    }

    const items = filteredBookmarks();

    if (!Array.isArray(items) || items.length === 0) {
        renderEmpty();
        return;
    }

    list.innerHTML = items.map(renderBookmarkItem).join('');

    list.querySelectorAll('[data-bookmark-update]').forEach((button) => {
        button.addEventListener('click', handleUpdateClick);
    });

    list.querySelectorAll('[data-bookmark-delete]').forEach((button) => {
        button.addEventListener('click', handleDeleteClick);
    });
}

function renderPaginationNotice() {
    if (!pagination) {
        return;
    }

    pagination.style.display = '';
    pagination.innerHTML = `
        <div>
            Folder bookmark saat ini dihitung dari data yang dimuat di halaman ini.
        </div>
        <div class="muted">
            Untuk tahap ini frontend memuat hingga 100 bookmark.
        </div>
    `;
}

function renderBookmarkScreen() {
    renderSummary();
    renderActiveFolderBox();
    renderFolderList();
    renderList();
    renderPaginationNotice();
}

function setItemBusy(identifier, isBusy) {
    busyBookmarkIdentifier = isBusy ? identifier : null;

    const item = Array.from(list?.querySelectorAll('[data-bookmark-item]') || [])
        .find((element) => String(element.dataset.identifier) === String(identifier));

    if (!item) {
        return;
    }

    item.querySelectorAll('button').forEach((button) => {
        button.disabled = isBusy;
    });
}

function getItemFromButton(button) {
    return button.closest('[data-bookmark-item]');
}

function getItemPayload(item) {
    return {
        folder_name: item.querySelector('[data-bookmark-folder]')?.value?.trim() || null,
        notes: item.querySelector('[data-bookmark-notes]')?.value?.trim() || null,
    };
}

async function handleUpdateClick(event) {
    const item = getItemFromButton(event.currentTarget);
    const identifier = item?.dataset?.identifier;

    if (!identifier || busyBookmarkIdentifier) {
        return;
    }

    setItemBusy(identifier, true);
    hideAlert();

    try {
        await api.patch(`/member/bookmarks/${encodeURIComponent(identifier)}`, getItemPayload(item));

        showAlert('Bookmark diperbarui', 'Folder dan catatan bookmark berhasil disimpan.', 'success');
        await loadBookmarks(false);
    } catch (error) {
        const apiError = normalizeApiError(error);

        if (apiError.isUnauthenticated()) {
            window.location.assign('/login?redirect=/member/bookmarks');
            return;
        }

        showAlert('Gagal memperbarui bookmark', apiError.message || 'Terjadi kesalahan saat memperbarui bookmark.');
    } finally {
        setItemBusy(identifier, false);
    }
}

async function handleDeleteClick(event) {
    const item = getItemFromButton(event.currentTarget);
    const identifier = item?.dataset?.identifier;

    if (!identifier || busyBookmarkIdentifier) {
        return;
    }

    const confirmed = window.confirm('Hapus bookmark ini?');

    if (!confirmed) {
        return;
    }

    setItemBusy(identifier, true);
    hideAlert();

    try {
        await api.delete(`/member/bookmarks/${encodeURIComponent(identifier)}`);

        showAlert('Bookmark dihapus', 'Bookmark berhasil dihapus.', 'success');

        await loadBookmarks(false);
    } catch (error) {
        const apiError = normalizeApiError(error);

        if (apiError.isUnauthenticated()) {
            window.location.assign('/login?redirect=/member/bookmarks');
            return;
        }

        showAlert('Gagal menghapus bookmark', apiError.message || 'Terjadi kesalahan saat menghapus bookmark.');
    } finally {
        setItemBusy(identifier, false);
    }
}

async function loadBookmarks(showMainLoading = true) {
    if (showMainLoading) {
        showLoading();
    } else {
        hideAlert();
    }

    try {
        const response = await api.get('/member/bookmarks', {
    query: {
        page: 1,
        per_page: 10,
    },
});

        const normalized = normalizeBookmarkResponse(response);

        allBookmarks = normalized.items || [];

        const validFolderKeys = folderStats(allBookmarks).map((folder) => folder.key);

        if (!validFolderKeys.includes(activeFolderKey)) {
            activeFolderKey = ALL_FOLDER;
        }

        renderBookmarkScreen();
        showContent();
    } catch (error) {
        const apiError = normalizeApiError(error);

        if (apiError.isUnauthenticated()) {
            window.location.assign('/login?redirect=/member/bookmarks');
            return;
        }

        if (apiError.isForbidden()) {
            window.location.assign('/forbidden');
            return;
        }

        if (loading) {
            loading.style.display = 'none';
        }

        showAlert('Bookmark gagal dimuat', apiError.message || 'Terjadi kesalahan saat memuat bookmark.');
    }
}

if (refreshButton) {
    refreshButton.addEventListener('click', () => {
        loadBookmarks(true);
    });
}

loadBookmarks(true);