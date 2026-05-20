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

const root = document.querySelector('#staff-collection-root');
const identifier = root?.dataset?.identifier;

const loading = document.querySelector('#staff-collection-loading');
const content = document.querySelector('#staff-collection-content');
const alertBox = document.querySelector('#staff-collection-alert');

const pageTitle = document.querySelector('#staff-collection-page-title');
const hero = document.querySelector('#staff-collection-hero');
const creatorsContainer = document.querySelector('#staff-collection-creators');
const subjectsContainer = document.querySelector('#staff-collection-subjects');
const metadataContainer = document.querySelector('#staff-collection-metadata');
const assetsContainer = document.querySelector('#staff-collection-assets');

const librarySection = document.querySelector('#staff-library-section');
const libraryDetail = document.querySelector('#staff-library-detail');
const museumSection = document.querySelector('#staff-museum-section');
const museumDetail = document.querySelector('#staff-museum-detail');

const publishButton = document.querySelector('#publish-button');
const archiveButton = document.querySelector('#archive-button');
const restoreButton = document.querySelector('#restore-button');

const editCollectionLink = document.querySelector('#edit-collection-link');
const metadataLink = document.querySelector('#metadata-link');
const creatorsSubjectsLink = document.querySelector('#creators-subjects-link');
const assetsLink = document.querySelector('#assets-link');
const auditLink = document.querySelector('#audit-link');
const versionsLink = document.querySelector('#versions-link');

let currentCollection = null;
let actionBusy = false;

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
    alertBox.className = `card staff-detail-alert ${type}`;
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
    alertBox.className = 'card staff-detail-alert';
    alertBox.innerHTML = '';
}

function normalizeCollectionResponse(response) {
    return response?.data?.collection
        || response?.collection
        || response?.data
        || response;
}

function displayTitle(item) {
    return item?.display_title || item?.title || 'Tanpa judul';
}

function collectionIdentifier(item) {
    return item?.record_code || item?.ulid || item?.id || identifier;
}

function categoryName(item) {
    return item?.category?.name
        || item?.category?.slug
        || item?.category_name
        || '-';
}

function locationName(item) {
    return item?.current_location?.name
        || item?.currentLocation?.name
        || item?.location?.name
        || item?.location_name
        || '-';
}

function isArchivedOrDeleted(item) {
    return Boolean(item?.deleted_at)
        || Boolean(item?.archived_at)
        || String(item?.publication_status || '').toLowerCase() === 'archived';
}

function isDeleted(item) {
    return Boolean(item?.deleted_at);
}

function detailRow(label, value) {
    return `
        <div class="detail-label">${escapeHtml(label)}</div>
        <div class="detail-value">${escapeHtml(formatValue(value))}</div>
    `;
}

function setActionBusy(isBusy) {
    actionBusy = isBusy;

    [publishButton, archiveButton, restoreButton].forEach((button) => {
        if (button) {
            button.disabled = isBusy;
        }
    });
}

function updateActionButtons(item) {
    const status = String(item?.publication_status || '').toLowerCase();
    const archived = isArchivedOrDeleted(item);
    const deleted = isDeleted(item);

    if (publishButton) {
        publishButton.style.display = status === 'published' || archived ? 'none' : '';
    }

    if (archiveButton) {
        archiveButton.style.display = archived || deleted ? 'none' : '';
    }

    if (restoreButton) {
        restoreButton.style.display = archived || deleted ? '' : 'none';
    }
}

function updateActionLinks(item) {
    const id = encodeURIComponent(collectionIdentifier(item));

    if (editCollectionLink) {
        editCollectionLink.href = `/staff/collections/${id}/edit`;
    }

    if (metadataLink) {
        metadataLink.href = `/staff/collections/${id}/metadata`;
    }

if (creatorsSubjectsLink) {
    creatorsSubjectsLink.href = `/staff/collections/${id}/creators-subjects`;
}
    if (assetsLink) {
        assetsLink.href = `/staff/collections/${id}/assets`;
    }

    if (auditLink) {
        auditLink.href = `/staff/collections/${id}/audit`;
    }

    if (versionsLink) {
        versionsLink.href = `/staff/collections/${id}/versions`;
    }
}

function renderHero(item) {
    if (!hero) {
        return;
    }

    const title = displayTitle(item);
    const unit = String(item.unit_type || '-').toLowerCase();
    const status = String(item.publication_status || '-').toLowerCase();

    if (pageTitle) {
        pageTitle.textContent = title;
    }

    document.title = `${title} - Staff SIMPB`;

    hero.innerHTML = `
        <h2>${escapeHtml(title)}</h2>

        <div class="badge-list">
            <span class="badge">${escapeHtml(item.record_code || '-')}</span>
            <span class="badge ${escapeHtml(unit)}">${escapeHtml(item.unit_type || '-')}</span>
            <span class="badge">${escapeHtml(item.collection_type || '-')}</span>
            <span class="badge ${escapeHtml(status)}">Status: ${escapeHtml(item.publication_status || '-')}</span>
            <span class="badge">Visibility: ${escapeHtml(item.visibility || '-')}</span>
            ${isArchivedOrDeleted(item) ? '<span class="badge archived">Archived/Deleted</span>' : ''}
        </div>

        <p class="muted">
            ${escapeHtml(item.description || 'Tidak ada deskripsi.')}
        </p>

        <div class="detail-grid">
            ${detailRow('ID', item.id)}
            ${detailRow('ULID', item.ulid)}
            ${detailRow('Record Code', item.record_code)}
            ${detailRow('Unit Type', item.unit_type)}
            ${detailRow('Collection Type', item.collection_type)}
            ${detailRow('Kategori', categoryName(item))}
            ${detailRow('Lokasi Saat Ini', locationName(item))}
            ${detailRow('Bahasa', item.language_code)}
            ${detailRow('Rights Status', item.rights_status)}
            ${detailRow('Date Display', item.date_display)}
            ${detailRow('Year Start', item.year_start)}
            ${detailRow('Year End', item.year_end)}
            ${detailRow('Publication Status', item.publication_status)}
            ${detailRow('Visibility', item.visibility)}
            ${detailRow('Featured', item.is_featured ? 'Ya' : 'Tidak')}
            ${detailRow('Archived At', formatDateTime(item.archived_at))}
            ${detailRow('Deleted At', formatDateTime(item.deleted_at))}
            ${detailRow('Created At', formatDateTime(item.created_at))}
            ${detailRow('Updated At', formatDateTime(item.updated_at))}
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

    if (row.value_string !== undefined && row.value_string !== null) {
        return row.value_string;
    }

    if (row.value_text !== undefined && row.value_text !== null) {
        return row.value_text;
    }

    if (row.value_integer !== undefined && row.value_integer !== null) {
        return row.value_integer;
    }

    if (row.value_decimal !== undefined && row.value_decimal !== null) {
        return row.value_decimal;
    }

    if (row.value_date !== undefined && row.value_date !== null) {
        return row.value_date;
    }

    if (row.value_datetime !== undefined && row.value_datetime !== null) {
        return row.value_datetime;
    }

    if (row.value_json !== undefined && row.value_json !== null) {
        return typeof row.value_json === 'string'
            ? row.value_json
            : JSON.stringify(row.value_json, null, 2);
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
            ${detailRow('Edition', library.edition)}
            ${detailRow('Series Title', library.series_title)}
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
                        — lokasi ${escapeHtml(copy.location?.name || copy.current_location?.name || '-')}
                    </li>
                `).join('')}
            </ul>
        ` : '<p>Belum ada copy.</p>'}
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
            ${detailRow('Dimensions', museum.dimensions_display)}
            ${detailRow('Weight', museum.weight_display)}
            ${detailRow('Condition Current', museum.condition_current)}
            ${detailRow('Condition Checked At', formatDate(museum.condition_checked_at))}
            ${detailRow('Provenance', museum.provenance_history)}
            ${detailRow('Acquisition Method', museum.acquisition_method)}
            ${detailRow('Acquisition Date', formatDate(museum.acquisition_date))}
        </div>

        <h3>Material</h3>
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

        <h3>Condition Report</h3>
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
        ` : '<p>Belum ada condition report.</p>'}
    `;
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

function assetPreviewUrl(asset) {
    return asset.public_url
        || publicStorageUrl(asset.thumbnail_path)
        || publicStorageUrl(asset.watermarked_path)
        || publicStorageUrl(asset.path);
}

function renderAssets(item) {
    if (!assetsContainer) {
        return;
    }

    const assets = item.digital_assets || item.digitalAssets || [];

    if (!Array.isArray(assets) || assets.length === 0) {
        assetsContainer.innerHTML = '<p>Belum ada digital asset.</p>';
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
                            ${detailRow('ID', asset.id)}
                            ${detailRow('ULID', asset.ulid)}
                            ${detailRow('Asset Type', asset.asset_type)}
                            ${detailRow('File Role', asset.file_role)}
                            ${detailRow('Disk', asset.disk)}
                            ${detailRow('Path', asset.path)}
                            ${detailRow('Thumbnail Path', asset.thumbnail_path)}
                            ${detailRow('MIME', asset.mime_type)}
                            ${detailRow('Extension', asset.extension)}
                            ${detailRow('Size Bytes', asset.size_bytes)}
                            ${detailRow('Checksum SHA256', asset.checksum_sha256)}
                            ${detailRow('Caption', asset.caption)}
                            ${detailRow('Is Primary', asset.is_primary ? 'Ya' : 'Tidak')}
                            ${detailRow('Is Public', asset.is_public ? 'Ya' : 'Tidak')}
                            ${detailRow('Access Level', asset.access_level)}
                            ${detailRow('Created At', formatDateTime(asset.created_at))}
                        </div>
                    </div>
                `;
            }).join('')}
        </div>
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
    updateActionButtons(item);
    updateActionLinks(item);
}

async function loadCollection() {
    if (!identifier) {
        showAlert('Identifier kosong', 'Halaman detail tidak memiliki identifier koleksi.');
        return;
    }

    showLoading();

    try {
        const response = await api.get(`/staff/collections/${encodeURIComponent(identifier)}`);
        const collection = normalizeCollectionResponse(response);

        renderCollection(collection);
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
            showAlert('Koleksi tidak ditemukan', 'Koleksi internal tidak ditemukan atau tidak dapat diakses.');
            return;
        }

        showAlert('Detail koleksi gagal dimuat', apiError.message || 'Terjadi kesalahan saat memuat detail koleksi.');
    }
}

function promptReason(defaultReason) {
    const reason = window.prompt('Alasan perubahan status:', defaultReason);

    if (reason === null) {
        return null;
    }

    const trimmed = reason.trim();

    if (!trimmed) {
        return defaultReason;
    }

    return trimmed;
}

async function runStatusAction(action) {
    if (!currentCollection || actionBusy) {
        return;
    }

    const id = encodeURIComponent(collectionIdentifier(currentCollection));

    const config = {
        publish: {
            url: `/staff/collections/${id}/publish`,
            reason: 'Metadata sudah diverifikasi dan koleksi siap dipublikasikan.',
            successTitle: 'Koleksi dipublish',
            successMessage: 'Koleksi berhasil dipublish.',
        },
        archive: {
            url: `/staff/collections/${id}/archive`,
            reason: 'Koleksi diarsipkan melalui halaman detail internal.',
            successTitle: 'Koleksi diarsipkan',
            successMessage: 'Koleksi berhasil diarsipkan.',
        },
        restore: {
            url: `/staff/collections/${id}/restore`,
            reason: 'Koleksi dipulihkan melalui halaman detail internal.',
            successTitle: 'Koleksi dipulihkan',
            successMessage: 'Koleksi berhasil dipulihkan.',
        },
    }[action];

    if (!config) {
        return;
    }

    const reason = promptReason(config.reason);

    if (reason === null) {
        return;
    }

    setActionBusy(true);
    hideAlert();

    try {
        await api.post(config.url, {
            reason,
        });

        showAlert(config.successTitle, config.successMessage, 'success');

        await loadCollection();
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

        showAlert('Aksi gagal', apiError.message || 'Perubahan status koleksi gagal dilakukan.');
    } finally {
        setActionBusy(false);
    }
}

if (publishButton) {
    publishButton.addEventListener('click', () => runStatusAction('publish'));
}

if (archiveButton) {
    archiveButton.addEventListener('click', () => runStatusAction('archive'));
}

if (restoreButton) {
    restoreButton.addEventListener('click', () => runStatusAction('restore'));
}

loadCollection();