import { createSimpbApiClient } from '../../api/simpbApi';
import { normalizeApiError } from '../../api/apiErrors';

const api = createSimpbApiClient({
    baseUrl: '/api',
    tokenStorage: window.sessionStorage,
});

const root = document.querySelector('#collection-detail-root');
const loading = document.querySelector('#collection-detail-loading');
const content = document.querySelector('#collection-detail-content');
const alertBox = document.querySelector('#collection-detail-alert');
const pageTitle = document.querySelector('#collection-page-title');

const hero = document.querySelector('#collection-hero');
const creatorsContainer = document.querySelector('#collection-creators');
const subjectsContainer = document.querySelector('#collection-subjects');
const metadataContainer = document.querySelector('#collection-metadata');
const assetsContainer = document.querySelector('#collection-assets');

const librarySection = document.querySelector('#library-section');
const libraryDetail = document.querySelector('#library-detail');

const museumSection = document.querySelector('#museum-section');
const museumDetail = document.querySelector('#museum-detail');
const bookmarkWidget = document.querySelector('#bookmark-widget');
const bookmarkStatus = document.querySelector('#bookmark-status');
const bookmarkButton = document.querySelector('#bookmark-toggle-button');
const bookmarkFolder = document.querySelector('#bookmark-folder');
const bookmarkNotes = document.querySelector('#bookmark-notes');

let currentCollection = null;
let currentUser = null;
let isBookmarked = false;
let bookmarkBusy = false;
const identifier = root?.dataset?.identifier;

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function formatValue(value) {
    if (value === null || value === undefined || value === '') {
        return '-';
    }

    if (typeof value === 'boolean') {
        return value ? 'Ya' : 'Tidak';
    }

    return String(value);
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

function showError(title, message) {
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
            <h2>${escapeHtml(title)}</h2>
            <p>${escapeHtml(message)}</p>
            <p><a href="/catalog">Kembali ke katalog</a></p>
        `;
    }

    if (pageTitle) {
        pageTitle.textContent = title;
    }
}

function displayTitle(item) {
    return item.display_title || item.title || 'Tanpa judul';
}

function detailRow(label, value) {
    return `
        <div class="detail-label">${escapeHtml(label)}</div>
        <div class="detail-value">${escapeHtml(formatValue(value))}</div>
    `;
}

function renderHero(item) {
    if (!hero) {
        return;
    }

    const title = displayTitle(item);
    const category = item.category?.name || item.category?.slug || '-';
    const location = item.current_location?.name || item.currentLocation?.name || '-';

    if (pageTitle) {
        pageTitle.textContent = title;
    }

    document.title = `${title} - SIMPB`;

    hero.innerHTML = `
        <h2>${escapeHtml(title)}</h2>

        <p class="muted">
            ${escapeHtml(item.description || 'Tidak ada deskripsi.')}
        </p>

        <div class="detail-grid">
            ${detailRow('Record Code', item.record_code)}
            ${detailRow('Unit', item.unit_type)}
            ${detailRow('Tipe Koleksi', item.collection_type)}
            ${detailRow('Kategori', category)}
            ${detailRow('Lokasi', location)}
            ${detailRow('Bahasa', item.language_code)}
            ${detailRow('Hak/Rights', item.rights_status)}
            ${detailRow('Tanggal/Tahun', item.date_display || item.year_start)}
            ${detailRow('Status Publikasi', item.publication_status)}
            ${detailRow('Visibility', item.visibility)}
        </div>
    `;
}

function renderBadgeList(container, items, renderer, emptyText) {
    if (!container) {
        return;
    }

    if (!Array.isArray(items) || items.length === 0) {
        container.innerHTML = `<p>${escapeHtml(emptyText)}</p>`;
        return;
    }

    container.innerHTML = `
        <div class="badge-list">
            ${items.map(renderer).join('')}
        </div>
    `;
}

function renderCreators(item) {
    renderBadgeList(
        creatorsContainer,
        item.creators || [],
        (creator) => {
            const role = creator.role || creator.pivot?.role || 'creator';
            const primary = creator.is_primary || creator.pivot?.is_primary ? ' — utama' : '';

            return `
                <span class="badge">
                    ${escapeHtml(creator.name || '-')}
                    (${escapeHtml(role)}${escapeHtml(primary)})
                </span>
            `;
        },
        'Belum ada creator.'
    );
}

function renderSubjects(item) {
    renderBadgeList(
        subjectsContainer,
        item.subjects || [],
        (subject) => {
            const type = subject.subject_type || subject.pivot?.subject_type || subject.type || 'subject';

            return `
                <span class="badge">
                    ${escapeHtml(subject.term || '-')}
                    (${escapeHtml(type)})
                </span>
            `;
        },
        'Belum ada subject.'
    );
}

function groupMetadata(metadata) {
    const groups = {};

    (metadata || []).forEach((row) => {
        const standard = row.standard || row.metadata_element?.standard || 'metadata';

        if (!groups[standard]) {
            groups[standard] = [];
        }

        groups[standard].push(row);
    });

    return groups;
}

function metadataLabel(row) {
    return row.label
        || row.metadata_element?.label
        || row.element_key
        || row.metadata_element?.element_key
        || '-';
}

function metadataKey(row) {
    return row.element_key
        || row.metadata_element?.element_key
        || '';
}

function metadataValue(row) {
    if (row.value !== undefined && row.value !== null) {
        return row.value;
    }

    if (row.display_value !== undefined && row.display_value !== null) {
        return row.display_value;
    }

    return '-';
}

function renderMetadata(item) {
    if (!metadataContainer) {
        return;
    }

    const metadata = item.metadata || [];

    if (!Array.isArray(metadata) || metadata.length === 0) {
        metadataContainer.innerHTML = '<p>Belum ada metadata.</p>';
        return;
    }

    const groups = groupMetadata(metadata);

    metadataContainer.innerHTML = Object.entries(groups)
        .map(([standard, rows]) => `
            <div class="metadata-group">
                <h3>${escapeHtml(String(standard).toUpperCase())}</h3>

                ${rows.map((row) => `
                    <div class="metadata-row">
                        <div class="metadata-label">
                            ${escapeHtml(metadataLabel(row))}
                            ${metadataKey(row) ? `<br><small>${escapeHtml(metadataKey(row))}</small>` : ''}
                        </div>
                        <div class="metadata-value">${escapeHtml(metadataValue(row))}</div>
                    </div>
                `).join('')}
            </div>
        `)
        .join('');
}

function publicStorageUrl(path) {
    if (!path) {
        return null;
    }

    if (String(path).startsWith('http://') || String(path).startsWith('https://')) {
        return path;
    }

    return `/storage/${String(path).replace(/^\/+/, '')}`;
}

function isPublicAsset(asset) {
    if (asset.access_level && asset.access_level !== 'public') {
        return false;
    }

    if (asset.is_public === false) {
        return false;
    }

    return true;
}

function assetPreviewUrl(asset) {
    if (!isPublicAsset(asset)) {
        return null;
    }

    return asset.public_url
        || publicStorageUrl(asset.path)
        || publicStorageUrl(asset.thumbnail_path)
        || publicStorageUrl(asset.watermarked_path);
}

function renderAssets(item) {
    if (!assetsContainer) {
        return;
    }

    const assets = item.digital_assets || item.digitalAssets || [];

    if (!Array.isArray(assets) || assets.length === 0) {
        assetsContainer.innerHTML = '<p>Belum ada digital asset publik.</p>';
        return;
    }

    assetsContainer.innerHTML = `
        <div class="asset-list">
            ${assets.map((asset) => {
        const preview = assetPreviewUrl(asset);
        const isImage = String(asset.mime_type || '').startsWith('image/');

        return `
                    <div class="asset-item">
                        ${preview && isImage ? `
                            <div class="asset-preview">
                                <img src="${escapeHtml(preview)}" alt="${escapeHtml(asset.caption || asset.filename || 'Digital asset')}">
                            </div>
                        ` : ''}

                        <div class="detail-grid">
                            ${detailRow('Asset Type', asset.asset_type)}
                            ${detailRow('File Role', asset.file_role)}
                            ${detailRow('MIME', asset.mime_type)}
                            ${detailRow('Caption', asset.caption)}
                            ${detailRow('Access Level', asset.access_level)}
                        </div>
                    </div>
                `;
    }).join('')}
        </div>
    `;
}

function renderLibrary(item) {
    const library = item.library || item.library_item || item.libraryItem;

    if (!library || !librarySection || !libraryDetail) {
        return;
    }

    librarySection.style.display = '';

    const copies = library.copies || item.library_copies || item.libraryCopies || [];

    libraryDetail.innerHTML = `
        <div class="detail-grid">
            ${detailRow('Bibliographic Level', library.bibliographic_level)}
            ${detailRow('ISBN-13', library.isbn13)}
            ${detailRow('ISBN-10', library.isbn10)}
            ${detailRow('ISSN', library.issn)}
            ${detailRow('DOI', library.doi)}
            ${detailRow('Publisher', library.publisher_name)}
            ${detailRow('Publisher Place', library.publisher_place)}
            ${detailRow('Publication Year', library.publication_year)}
            ${detailRow('DDC', library.ddc_classification)}
            ${detailRow('Call Number', library.call_number)}
            ${detailRow('Physical Extent', library.physical_extent)}
            ${detailRow('Pages', library.pages)}
        </div>

        <h3>Copy</h3>
        ${Array.isArray(copies) && copies.length > 0 ? `
            <ul>
                ${copies.map((copy) => `
                    <li>
                        <strong>${escapeHtml(copy.barcode || copy.copy_number || '-')}</strong>
                        — ${escapeHtml(copy.status || '-')}
                        — kondisi ${escapeHtml(copy.condition_grade || '-')}
                    </li>
                `).join('')}
            </ul>
        ` : '<p>Data copy tidak tersedia untuk tampilan publik.</p>'}
    `;
}

function renderMuseum(item) {
    const museum = item.museum || item.museum_item || item.museumItem;

    if (!museum || !museumSection || !museumDetail) {
        return;
    }

    museumSection.style.display = '';

    const materials = museum.materials || [];
    const reports = museum.condition_reports || museum.conditionReports || [];

    museumDetail.innerHTML = `
        <div class="detail-grid">
            ${detailRow('Inventory Number', museum.inventory_number)}
            ${detailRow('Object Name', museum.object_name)}
            ${detailRow('Object Type', museum.object_type_label)}
            ${detailRow('Classification', museum.classification)}
            ${detailRow('Maker', museum.maker_name)}
            ${detailRow('Culture', museum.culture)}
            ${detailRow('Period', museum.period_display)}
            ${detailRow('Material Summary', museum.material_summary)}
            ${detailRow('Technique Summary', museum.technique_summary)}
            ${detailRow('Condition Current', museum.condition_current)}
            ${detailRow('Condition Checked At', formatDate(museum.condition_checked_at))}
            ${detailRow('Provenance', museum.provenance_history)}
        </div>

        <h3>Material / Technique</h3>
        ${Array.isArray(materials) && materials.length > 0 ? `
            <div class="badge-list">
                ${materials.map((material) => `
                    <span class="badge">
                        ${escapeHtml(material.name || '-')}
                        ${material.type ? `(${escapeHtml(material.type)})` : ''}
                    </span>
                `).join('')}
            </div>
        ` : '<p>Belum ada material.</p>'}

        <h3>Condition Report Ringkas</h3>
        ${Array.isArray(reports) && reports.length > 0 ? `
            <ul>
                ${reports.map((report) => `
                    <li>
                        Kondisi <strong>${escapeHtml(report.condition_grade || '-')}</strong>
                        — inspeksi ${escapeHtml(formatDate(report.inspected_at))}
                        ${report.priority ? `— prioritas ${escapeHtml(report.priority)}` : ''}
                    </li>
                `).join('')}
            </ul>
        ` : '<p>Belum ada condition report publik.</p>'}
    `;
}

function resetSections() {
    if (librarySection) {
        librarySection.style.display = 'none';
    }

    if (museumSection) {
        museumSection.style.display = 'none';
    }
}

function userIsMember(user) {
    return Array.isArray(user?.roles) && user.roles.includes('member');
}

function setBookmarkBusy(isBusy) {
    bookmarkBusy = isBusy;

    if (bookmarkButton) {
        bookmarkButton.disabled = isBusy;
        bookmarkButton.textContent = isBusy
            ? 'Memproses...'
            : isBookmarked
                ? 'Hapus Bookmark'
                : 'Tambah Bookmark';
    }
}

function setBookmarkStatus(message) {
    if (bookmarkStatus) {
        bookmarkStatus.textContent = message;
    }
}

function updateBookmarkButton() {
    if (!bookmarkButton) {
        return;
    }

    bookmarkButton.classList.toggle('danger', isBookmarked);
    bookmarkButton.textContent = isBookmarked
        ? 'Hapus Bookmark'
        : 'Tambah Bookmark';
}

function normalizeBookmarkItems(response) {
    if (Array.isArray(response?.data)) {
        return response.data;
    }

    if (Array.isArray(response?.data?.data)) {
        return response.data.data;
    }

    return [];
}

function bookmarkMatchesCollection(bookmark, collection) {
    const bookmarkRecordCode =
        bookmark.record_code
        || bookmark.collection?.record_code
        || bookmark.collection_record_code;

    const bookmarkCollectionId =
        bookmark.collection_id
        || bookmark.collection?.id
        || bookmark.id;

    if (bookmarkRecordCode && collection.record_code) {
        return String(bookmarkRecordCode) === String(collection.record_code);
    }

    if (bookmarkCollectionId && collection.id) {
        return Number(bookmarkCollectionId) === Number(collection.id);
    }

    return false;
}

async function loadBookmarkState(collection) {
    const token = api.getToken();

    if (!token || !bookmarkWidget) {
        return;
    }

    try {
        const me = await api.me();
        currentUser = me.data;

        if (!userIsMember(currentUser)) {
            bookmarkWidget.style.display = 'none';
            return;
        }

        bookmarkWidget.style.display = '';

        setBookmarkStatus('Memeriksa status bookmark...');
        setBookmarkBusy(true);

        const response = await api.get('/member/bookmarks');
        const bookmarks = normalizeBookmarkItems(response);

        isBookmarked = bookmarks.some((bookmark) => bookmarkMatchesCollection(bookmark, collection));

        updateBookmarkButton();

        setBookmarkStatus(
            isBookmarked
                ? 'Koleksi ini sudah ada di bookmark Anda.'
                : 'Koleksi ini belum ada di bookmark Anda.'
        );
    } catch {
        bookmarkWidget.style.display = 'none';
    } finally {
        setBookmarkBusy(false);
    }
}

async function addBookmark() {
    if (!currentCollection) {
        return;
    }

    setBookmarkBusy(true);
    setBookmarkStatus('Menambahkan bookmark...');

    try {
        await api.post('/member/bookmarks', {
            record_code: currentCollection.record_code,
            folder_name: bookmarkFolder?.value?.trim() || 'Koleksi favorit',
            notes: bookmarkNotes?.value?.trim() || null,
        });

        isBookmarked = true;
        updateBookmarkButton();
        setBookmarkStatus('Bookmark berhasil ditambahkan.');
    } catch (error) {
        const apiError = normalizeApiError(error);
        setBookmarkStatus(apiError.message || 'Bookmark gagal ditambahkan.');
    } finally {
        setBookmarkBusy(false);
    }
}

async function removeBookmark() {
    if (!currentCollection) {
        return;
    }

    setBookmarkBusy(true);
    setBookmarkStatus('Menghapus bookmark...');

    try {
        const identifier = currentCollection.record_code || currentCollection.id;

        await api.delete(`/member/bookmarks/${encodeURIComponent(identifier)}`);

        isBookmarked = false;
        updateBookmarkButton();
        setBookmarkStatus('Bookmark berhasil dihapus.');
    } catch (error) {
        const apiError = normalizeApiError(error);
        setBookmarkStatus(apiError.message || 'Bookmark gagal dihapus.');
    } finally {
        setBookmarkBusy(false);
    }
}

async function toggleBookmark() {
    if (bookmarkBusy) {
        return;
    }

    if (isBookmarked) {
        await removeBookmark();
        return;
    }

    await addBookmark();
}

function renderCollection(item) {
    currentCollection = item;

    resetSections();

    renderHero(item);
    renderCreators(item);
    renderSubjects(item);
    renderLibrary(item);
    renderMuseum(item);
    renderMetadata(item);
    renderAssets(item);
    loadBookmarkState(item);
}

async function loadCollection() {
    if (!identifier) {
        showError('Identifier kosong', 'Halaman detail tidak memiliki identifier koleksi.');
        return;
    }

    showLoading();

    try {
        const response = await api.get(`/catalog/collections/${encodeURIComponent(identifier)}`, {
            auth: false,
        });

        renderCollection(response.data);
        showContent();
    } catch (error) {
        const apiError = normalizeApiError(error);

        if (apiError.isNotFound()) {
            showError('Koleksi tidak ditemukan', 'Koleksi yang Anda buka tidak ditemukan atau belum dipublikasikan.');
            return;
        }

        if (apiError.isForbidden()) {
            showError('Akses ditolak', 'Anda tidak memiliki akses ke koleksi ini.');
            return;
        }

        showError('Detail koleksi gagal dimuat', apiError.message || 'Terjadi kesalahan saat memuat detail koleksi.');
    }
}
if (bookmarkButton) {
    bookmarkButton.addEventListener('click', toggleBookmark);
}

loadCollection();