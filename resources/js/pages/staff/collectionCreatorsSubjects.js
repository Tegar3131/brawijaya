import { createSimpbApiClient } from '../../api/simpbApi';
import { normalizeApiError, validationErrorsToObject } from '../../api/apiErrors';

const api = createSimpbApiClient({
    baseUrl: '/api',
    tokenStorage: window.sessionStorage,
    onUnauthenticated: () => {
        api.clearAuth();
        window.location.assign('/login?redirect=' + encodeURIComponent(window.location.pathname));
    },
});

const root = document.querySelector('#cs-root');
const identifier = root?.dataset?.identifier;

const loading = document.querySelector('#cs-loading');
const content = document.querySelector('#cs-content');
const alertBox = document.querySelector('#cs-alert');
const pageTitle = document.querySelector('#cs-page-title');
const collectionSummary = document.querySelector('#cs-collection-summary');

const creatorLookupInput = document.querySelector('#creator_lookup');
const creatorSearchButton = document.querySelector('#creator-search-button');
const creatorNewButton = document.querySelector('#creator-new-button');
const creatorSelectedBox = document.querySelector('#creator-selected');
const creatorResultsBox = document.querySelector('#creator-search-results');

const subjectLookupInput = document.querySelector('#subject_lookup');
const subjectSearchButton = document.querySelector('#subject-search-button');
const subjectNewButton = document.querySelector('#subject-new-button');
const subjectSelectedBox = document.querySelector('#subject-selected');
const subjectResultsBox = document.querySelector('#subject-search-results');

let creatorSearchResults = [];
let subjectSearchResults = [];

const creatorForm = document.querySelector('#creator-form');
const subjectForm = document.querySelector('#subject-form');

const creatorSaveButton = document.querySelector('#creator-save-button');
const subjectSaveButton = document.querySelector('#subject-save-button');
const creatorResetButton = document.querySelector('#creator-reset-button');
const subjectResetButton = document.querySelector('#subject-reset-button');
const refreshButton = document.querySelector('#cs-refresh-button');

const creatorList = document.querySelector('#creator-list');
const subjectList = document.querySelector('#subject-list');

let currentCollection = null;
let busyRelationId = null;

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
    alertBox.className = `card cs-alert ${type}`;
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
    alertBox.className = 'card cs-alert';
    alertBox.innerHTML = '';
}

function clearFieldErrors(scope = document) {
    scope.querySelectorAll('[data-error-for]').forEach((element) => {
        element.textContent = '';
    });

    scope.querySelectorAll('[aria-invalid="true"]').forEach((element) => {
        element.removeAttribute('aria-invalid');
    });
}

function showFieldErrors(details, scope = document) {
    const errors = validationErrorsToObject(details);

    Object.entries(errors).forEach(([field, message]) => {
        const simpleField = field
            .replace(/^creator\./, '')
            .replace(/^subject\./, '');

        const errorElement =
            scope.querySelector(`[data-error-for="${field}"]`)
            || scope.querySelector(`[data-error-for="${simpleField}"]`);

        if (errorElement) {
            errorElement.textContent = message;
        }

        const inputElement = scope.querySelector(`[name="${simpleField}"]`);

        if (inputElement) {
            inputElement.setAttribute('aria-invalid', 'true');
        }
    });
}

function normalizeCollectionResponse(response) {
    return response?.data?.collection
        || response?.collection
        || response?.data
        || response;
}

function displayTitle(collection) {
    return collection?.display_title || collection?.title || 'Tanpa judul';
}

function collectionIdentifier(collection) {
    return collection?.record_code || collection?.ulid || collection?.id || identifier;
}

function summaryRow(label, value) {
    return `
        <div class="summary-label">${escapeHtml(label)}</div>
        <div class="summary-value">${escapeHtml(value ?? '-')}</div>
    `;
}

function renderCollectionSummary(collection) {
    if (!collectionSummary) {
        return;
    }

    const title = displayTitle(collection);

    if (pageTitle) {
        pageTitle.textContent = `Creator dan Subject: ${title}`;
    }

    document.title = `Creator dan Subject: ${title} - Staff SIMPB`;

    collectionSummary.innerHTML = `
        <div class="summary-grid">
            ${summaryRow('Judul', title)}
            ${summaryRow('Record Code', collection.record_code)}
            ${summaryRow('Unit', collection.unit_type)}
            ${summaryRow('Tipe Koleksi', collection.collection_type)}
            ${summaryRow('Status Publikasi', collection.publication_status)}
            ${summaryRow('Visibility', collection.visibility)}
        </div>
    `;
}

function creatorId(creator) {
    return creator.id || creator.creator_id || creator.pivot?.creator_id;
}

function creatorRole(creator) {
    return creator.role || creator.pivot?.role || 'creator';
}

function creatorSortOrder(creator) {
    return creator.sort_order ?? creator.pivot?.sort_order ?? '-';
}

function creatorIsPrimary(creator) {
    return Boolean(creator.is_primary || creator.pivot?.is_primary);
}

function subjectId(subject) {
    return subject.id || subject.subject_id || subject.pivot?.subject_id;
}

function subjectType(subject) {
    return subject.subject_type || subject.pivot?.subject_type || subject.type || 'topic';
}

function subjectSortOrder(subject) {
    return subject.sort_order ?? subject.pivot?.sort_order ?? '-';
}

function renderCreators(collection) {
    const creators = collection.creators || [];

    if (!creatorList) {
        return;
    }

    if (!Array.isArray(creators) || creators.length === 0) {
        creatorList.innerHTML = '<p>Belum ada creator.</p>';
        return;
    }

    creatorList.innerHTML = creators.map((creator) => {
        const id = creatorId(creator);

        return `
            <article class="relation-item" data-relation-item data-relation-type="creator" data-relation-id="${escapeHtml(id)}">
                <h3>${escapeHtml(creator.name || '-')}</h3>

                <div class="badge-list">
                    <span class="badge">ID: ${escapeHtml(id || '-')}</span>
                    <span class="badge">Tipe: ${escapeHtml(creator.creator_type || creator.type || '-')}</span>
                    <span class="badge">Role: ${escapeHtml(creatorRole(creator))}</span>
                    <span class="badge">Sort: ${escapeHtml(creatorSortOrder(creator))}</span>
                    ${creatorIsPrimary(creator) ? '<span class="badge">Utama</span>' : ''}
                    ${creator.authority_source ? `<span class="badge">Authority: ${escapeHtml(creator.authority_source)}</span>` : ''}
                </div>

                <div class="relation-actions">
                    <button type="button" class="danger" data-creator-delete data-id="${escapeHtml(id)}">
                        Hapus Creator
                    </button>
                </div>
            </article>
        `;
    }).join('');

    creatorList.querySelectorAll('[data-creator-delete]').forEach((button) => {
        button.addEventListener('click', handleDeleteCreator);
    });
}

function renderSubjects(collection) {
    const subjects = collection.subjects || [];

    if (!subjectList) {
        return;
    }

    if (!Array.isArray(subjects) || subjects.length === 0) {
        subjectList.innerHTML = '<p>Belum ada subject.</p>';
        return;
    }

    subjectList.innerHTML = subjects.map((subject) => {
        const id = subjectId(subject);

        return `
            <article class="relation-item" data-relation-item data-relation-type="subject" data-relation-id="${escapeHtml(id)}">
                <h3>${escapeHtml(subject.term || '-')}</h3>

                <div class="badge-list">
                    <span class="badge">ID: ${escapeHtml(id || '-')}</span>
                    <span class="badge">Tipe: ${escapeHtml(subjectType(subject))}</span>
                    <span class="badge">Sort: ${escapeHtml(subjectSortOrder(subject))}</span>
                    ${subject.authority_source ? `<span class="badge">Authority: ${escapeHtml(subject.authority_source)}</span>` : ''}
                    ${subject.authority_uri ? `<span class="badge">URI: ${escapeHtml(subject.authority_uri)}</span>` : ''}
                </div>

                <div class="relation-actions">
                    <button type="button" class="danger" data-subject-delete data-id="${escapeHtml(id)}">
                        Hapus Subject
                    </button>
                </div>
            </article>
        `;
    }).join('');

    subjectList.querySelectorAll('[data-subject-delete]').forEach((button) => {
        button.addEventListener('click', handleDeleteSubject);
    });
}

function renderCollection(collection) {
    currentCollection = collection;

    renderCollectionSummary(collection);
    renderCreators(collection);
    renderSubjects(collection);
}

function setButtonBusy(button, isBusy, busyText, normalText) {
    if (!button) {
        return;
    }

    button.disabled = isBusy;
    button.textContent = isBusy ? busyText : normalText;
}

function setRelationBusy(type, id, isBusy) {
    busyRelationId = isBusy ? `${type}:${id}` : null;

    const row = Array.from(document.querySelectorAll('[data-relation-item]'))
        .find((element) => (
            element.dataset.relationType === type
            && String(element.dataset.relationId) === String(id)
        ));

    if (!row) {
        return;
    }

    row.querySelectorAll('button').forEach((button) => {
        button.disabled = isBusy;
    });
}

async function loadCollection(mainLoading = true) {
    if (!identifier) {
        showAlert('Identifier kosong', 'Halaman tidak memiliki identifier koleksi.');
        return;
    }

    if (mainLoading) {
        showLoading();
    }

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
            showAlert('Koleksi tidak ditemukan', 'Koleksi tidak ditemukan atau tidak dapat diakses.');
            return;
        }

        showAlert('Data gagal dimuat', apiError.message || 'Terjadi kesalahan saat memuat creator dan subject.');
    }
}

function formValue(form, name) {
    return form?.elements?.[name]?.value?.trim() || '';
}

function formChecked(form, name) {
    return Boolean(form?.elements?.[name]?.checked);
}

function buildCreatorPayload() {
    const creatorIdValue = formValue(creatorForm, 'creator_id');

    if (creatorIdValue) {
        return {
            creator_id: Number.parseInt(creatorIdValue, 10),
            name: formValue(creatorForm, 'name'),
            role: formValue(creatorForm, 'role') || 'creator',
            is_primary: formChecked(creatorForm, 'is_primary'),
            sort_order: Number.parseInt(formValue(creatorForm, 'sort_order') || '1', 10),
            reason: formValue(creatorForm, 'reason') || 'Menambahkan creator melalui editor staff.',
        };
    }

    return {
        name: formValue(creatorForm, 'name'),
        creator_type: formValue(creatorForm, 'creator_type') || 'person',
        role: formValue(creatorForm, 'role') || 'creator',
        is_primary: formChecked(creatorForm, 'is_primary'),
        sort_order: Number.parseInt(formValue(creatorForm, 'sort_order') || '1', 10),
        reason: formValue(creatorForm, 'reason') || 'Menambahkan creator melalui editor staff.',
    };
}

function buildSubjectPayload() {
    const subjectIdValue = formValue(subjectForm, 'subject_id');

    if (subjectIdValue) {
        return {
            subject_id: Number.parseInt(subjectIdValue, 10),
            term: formValue(subjectForm, 'term'),
            subject_type: formValue(subjectForm, 'subject_type') || 'topic',
            sort_order: Number.parseInt(formValue(subjectForm, 'sort_order') || '1', 10),
            reason: formValue(subjectForm, 'reason') || 'Menambahkan subject melalui editor staff.',
        };
    }

    return {
        term: formValue(subjectForm, 'term'),
        subject_type: formValue(subjectForm, 'subject_type') || 'topic',
        authority_source: formValue(subjectForm, 'authority_source') || 'Lokal SIMPB',
        sort_order: Number.parseInt(formValue(subjectForm, 'sort_order') || '1', 10),
        reason: formValue(subjectForm, 'reason') || 'Menambahkan subject melalui editor staff.',
    };
}

function resetCreatorForm() {
    if (!creatorForm) {
        return;
    }

    creatorForm.reset();
    creatorForm.elements.creator_id.value = '';
    if (creatorLookupInput) creatorLookupInput.value = '';
    creatorForm.elements.creator_type.value = 'person';
    creatorForm.elements.role.value = 'author';
    creatorForm.elements.sort_order.value = '1';
    creatorForm.elements.reason.value = 'Menambahkan creator melalui editor staff.';
    
    if (creatorSelectedBox) {
        creatorSelectedBox.style.display = 'none';
        creatorSelectedBox.innerHTML = '';
    }
    if (creatorResultsBox) {
        creatorResultsBox.style.display = 'none';
        creatorResultsBox.innerHTML = '';
    }
    creatorSearchResults = [];
    
    clearFieldErrors(creatorForm);
}

function resetSubjectForm() {
    if (!subjectForm) {
        return;
    }

    subjectForm.reset();
    subjectForm.elements.subject_id.value = '';
    if (subjectLookupInput) subjectLookupInput.value = '';
    subjectForm.elements.subject_type.value = 'topic';
    subjectForm.elements.authority_source.value = 'Lokal SIMPB';
    subjectForm.elements.sort_order.value = '1';
    subjectForm.elements.reason.value = 'Menambahkan subject melalui editor staff.';
    
    if (subjectSelectedBox) {
        subjectSelectedBox.style.display = 'none';
        subjectSelectedBox.innerHTML = '';
    }
    if (subjectResultsBox) {
        subjectResultsBox.style.display = 'none';
        subjectResultsBox.innerHTML = '';
    }
    subjectSearchResults = [];

    clearFieldErrors(subjectForm);
}

async function handleCreateCreator(event) {
    event.preventDefault();

    clearFieldErrors(creatorForm);
    hideAlert();
    setButtonBusy(creatorSaveButton, true, 'Menyimpan...', 'Tambah Creator');

    try {
        await api.post(
            `/staff/collections/${encodeURIComponent(collectionIdentifier(currentCollection))}/creators`,
            buildCreatorPayload()
        );

        showAlert('Creator ditambahkan', 'Creator berhasil ditambahkan ke koleksi.', 'success');
        resetCreatorForm();
        await loadCollection(false);
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

        if (apiError.isValidationError()) {
            showFieldErrors(apiError.details, creatorForm);
            showAlert('Creator tidak valid', apiError.message || 'Data creator tidak valid.', 'warning');
            return;
        }

        showAlert('Creator gagal ditambahkan', apiError.message || 'Terjadi kesalahan saat menambahkan creator.');
    } finally {
        setButtonBusy(creatorSaveButton, false, 'Menyimpan...', 'Tambah Creator');
    }
}

async function handleCreateSubject(event) {
    event.preventDefault();

    clearFieldErrors(subjectForm);
    hideAlert();
    setButtonBusy(subjectSaveButton, true, 'Menyimpan...', 'Tambah Subject');

    try {
        await api.post(
            `/staff/collections/${encodeURIComponent(collectionIdentifier(currentCollection))}/subjects`,
            buildSubjectPayload()
        );

        showAlert('Subject ditambahkan', 'Subject berhasil ditambahkan ke koleksi.', 'success');
        resetSubjectForm();
        await loadCollection(false);
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

        if (apiError.isValidationError()) {
            showFieldErrors(apiError.details, subjectForm);
            showAlert('Subject tidak valid', apiError.message || 'Data subject tidak valid.', 'warning');
            return;
        }

        showAlert('Subject gagal ditambahkan', apiError.message || 'Terjadi kesalahan saat menambahkan subject.');
    } finally {
        setButtonBusy(subjectSaveButton, false, 'Menyimpan...', 'Tambah Subject');
    }
}

async function handleDeleteCreator(event) {
    const id = event.currentTarget.dataset.id;

    if (!id || busyRelationId) {
        return;
    }

    const confirmed = window.confirm('Hapus creator ini dari koleksi?');

    if (!confirmed) {
        return;
    }

    const reason = window.prompt(
        'Alasan penghapusan creator:',
        'Menghapus creator dari koleksi melalui editor staff.'
    );

    if (reason === null) {
        return;
    }

    setRelationBusy('creator', id, true);
    hideAlert();

    try {
        await api.delete(
            `/staff/collections/${encodeURIComponent(collectionIdentifier(currentCollection))}/creators/${encodeURIComponent(id)}`,
            {
                body: {
                    reason: reason.trim() || 'Menghapus creator dari koleksi melalui editor staff.',
                },
            }
        );

        showAlert('Creator dihapus', 'Creator berhasil dihapus dari koleksi.', 'success');
        await loadCollection(false);
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

        showAlert('Creator gagal dihapus', apiError.message || 'Terjadi kesalahan saat menghapus creator.');
    } finally {
        setRelationBusy('creator', id, false);
    }
}

async function handleDeleteSubject(event) {
    const id = event.currentTarget.dataset.id;

    if (!id || busyRelationId) {
        return;
    }

    const confirmed = window.confirm('Hapus subject ini dari koleksi?');

    if (!confirmed) {
        return;
    }

    const reason = window.prompt(
        'Alasan penghapusan subject:',
        'Menghapus subject dari koleksi melalui editor staff.'
    );

    if (reason === null) {
        return;
    }

    setRelationBusy('subject', id, true);
    hideAlert();

    try {
        await api.delete(
            `/staff/collections/${encodeURIComponent(collectionIdentifier(currentCollection))}/subjects/${encodeURIComponent(id)}`,
            {
                body: {
                    reason: reason.trim() || 'Menghapus subject dari koleksi melalui editor staff.',
                },
            }
        );

        showAlert('Subject dihapus', 'Subject berhasil dihapus dari koleksi.', 'success');
        await loadCollection(false);
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

        showAlert('Subject gagal dihapus', apiError.message || 'Terjadi kesalahan saat menghapus subject.');
    } finally {
        setRelationBusy('subject', id, false);
    }
}

function normalizeLookupResponse(response) {
    return response?.data || response;
}

function setCreatorSelected(creator) {
    if (!creatorForm) return;
    
    creatorForm.elements.creator_id.value = creator.id;
    creatorForm.elements.name.value = creator.name;
    
    if (creator.creator_type) {
        creatorForm.elements.creator_type.value = creator.creator_type;
    } else {
        creatorForm.elements.creator_type.value = 'person';
    }
    
    creatorResultsBox.style.display = 'none';
    creatorResultsBox.innerHTML = '';
    creatorSearchResults = [];
    
    creatorSelectedBox.style.display = '';
    creatorSelectedBox.innerHTML = `<strong>Creator existing dipilih:</strong> ${escapeHtml(creator.name)} (ID: ${creator.id})`;
}

function clearCreatorSelection(useLookupAsName = false) {
    if (!creatorForm) return;
    
    creatorForm.elements.creator_id.value = '';
    
    if (useLookupAsName && creatorLookupInput.value.trim() !== '') {
        creatorForm.elements.name.value = creatorLookupInput.value.trim();
    }
    
    creatorResultsBox.style.display = 'none';
    creatorResultsBox.innerHTML = '';
    creatorSearchResults = [];
    
    creatorSelectedBox.style.display = 'none';
    creatorSelectedBox.innerHTML = '';
    
    showAlert('Peringatan', 'Creator akan dibuat baru dan disimpan ke database saat Anda menekan tombol Tambah Creator.', 'warning');
}

function renderCreatorSearchResults(items) {
    if (!creatorResultsBox) return;
    
    if (!Array.isArray(items) || items.length === 0) {
        creatorResultsBox.style.display = '';
        creatorResultsBox.innerHTML = '<div class="lookup-result"><div class="muted">Tidak ada hasil ditemukan.</div></div>';
        return;
    }
    
    creatorResultsBox.style.display = '';
    creatorResultsBox.innerHTML = items.map((item, index) => {
        return `
            <div class="lookup-result">
                <div>
                    <div class="lookup-result-title">${escapeHtml(item.name)} (ID: ${item.id})</div>
                    <div class="lookup-result-meta">Tipe: ${escapeHtml(item.creator_type || 'person')} | Authority: ${escapeHtml(item.authority_source || '-')}</div>
                </div>
                <button type="button" data-creator-select-index="${index}">Pilih</button>
            </div>
        `;
    }).join('');
    
    creatorResultsBox.querySelectorAll('[data-creator-select-index]').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            const idx = parseInt(e.currentTarget.dataset.creatorSelectIndex, 10);
            if (creatorSearchResults[idx]) {
                setCreatorSelected(creatorSearchResults[idx]);
            }
        });
    });
}

async function searchCreators() {
    const q = creatorLookupInput.value.trim();
    if (!q) return;
    
    creatorSearchButton.disabled = true;
    creatorSearchButton.textContent = 'Mencari...';
    
    try {
        const response = await api.get(`/staff/creators/search?q=${encodeURIComponent(q)}&limit=10`);
        creatorSearchResults = normalizeLookupResponse(response) || [];
        renderCreatorSearchResults(creatorSearchResults);
    } catch (error) {
        console.error('Error searching creators:', error);
        showAlert('Pencarian gagal', 'Terjadi kesalahan saat mencari creator.');
    } finally {
        creatorSearchButton.disabled = false;
        creatorSearchButton.textContent = 'Cari';
    }
}

function setSubjectSelected(subject) {
    if (!subjectForm) return;
    
    subjectForm.elements.subject_id.value = subject.id;
    subjectForm.elements.term.value = subject.term;
    
    if (subject.subject_type) {
        subjectForm.elements.subject_type.value = subject.subject_type;
    } else {
        subjectForm.elements.subject_type.value = 'topic';
    }
    
    if (subject.authority_source) {
        subjectForm.elements.authority_source.value = subject.authority_source;
    } else {
        subjectForm.elements.authority_source.value = 'Lokal SIMPB';
    }
    
    subjectResultsBox.style.display = 'none';
    subjectResultsBox.innerHTML = '';
    subjectSearchResults = [];
    
    subjectSelectedBox.style.display = '';
    subjectSelectedBox.innerHTML = `<strong>Subject existing dipilih:</strong> ${escapeHtml(subject.term)} (ID: ${subject.id})`;
}

function clearSubjectSelection(useLookupAsTerm = false) {
    if (!subjectForm) return;
    
    subjectForm.elements.subject_id.value = '';
    
    if (useLookupAsTerm && subjectLookupInput.value.trim() !== '') {
        subjectForm.elements.term.value = subjectLookupInput.value.trim();
    }
    
    subjectResultsBox.style.display = 'none';
    subjectResultsBox.innerHTML = '';
    subjectSearchResults = [];
    
    subjectSelectedBox.style.display = 'none';
    subjectSelectedBox.innerHTML = '';
    
    showAlert('Peringatan', 'Subject akan dibuat baru dan disimpan ke database saat Anda menekan tombol Tambah Subject.', 'warning');
}

function renderSubjectSearchResults(items) {
    if (!subjectResultsBox) return;
    
    if (!Array.isArray(items) || items.length === 0) {
        subjectResultsBox.style.display = '';
        subjectResultsBox.innerHTML = '<div class="lookup-result"><div class="muted">Tidak ada hasil ditemukan.</div></div>';
        return;
    }
    
    subjectResultsBox.style.display = '';
    subjectResultsBox.innerHTML = items.map((item, index) => {
        return `
            <div class="lookup-result">
                <div>
                    <div class="lookup-result-title">${escapeHtml(item.term)} (ID: ${item.id})</div>
                    <div class="lookup-result-meta">Tipe: ${escapeHtml(item.subject_type)} | Authority: ${escapeHtml(item.authority_source || '-')}</div>
                </div>
                <button type="button" data-subject-select-index="${index}">Pilih</button>
            </div>
        `;
    }).join('');
    
    subjectResultsBox.querySelectorAll('[data-subject-select-index]').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            const idx = parseInt(e.currentTarget.dataset.subjectSelectIndex, 10);
            if (subjectSearchResults[idx]) {
                setSubjectSelected(subjectSearchResults[idx]);
            }
        });
    });
}

async function searchSubjects() {
    const q = subjectLookupInput.value.trim();
    if (!q) return;
    
    subjectSearchButton.disabled = true;
    subjectSearchButton.textContent = 'Mencari...';
    
    try {
        const response = await api.get(`/staff/subjects/search?q=${encodeURIComponent(q)}&limit=10`);
        subjectSearchResults = normalizeLookupResponse(response) || [];
        renderSubjectSearchResults(subjectSearchResults);
    } catch (error) {
        console.error('Error searching subjects:', error);
        showAlert('Pencarian gagal', 'Terjadi kesalahan saat mencari subject.');
    } finally {
        subjectSearchButton.disabled = false;
        subjectSearchButton.textContent = 'Cari';
    }
}

if (creatorForm) {
    creatorForm.addEventListener('submit', handleCreateCreator);
}

if (subjectForm) {
    subjectForm.addEventListener('submit', handleCreateSubject);
}

if (creatorResetButton) {
    creatorResetButton.addEventListener('click', resetCreatorForm);
}

if (subjectResetButton) {
    subjectResetButton.addEventListener('click', resetSubjectForm);
}

if (refreshButton) {
    refreshButton.addEventListener('click', () => {
        loadCollection(true);
    });
}

if (creatorSearchButton) {
    creatorSearchButton.addEventListener('click', searchCreators);
}

if (creatorLookupInput) {
    creatorLookupInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            searchCreators();
        }
    });
}

if (creatorNewButton) {
    creatorNewButton.addEventListener('click', () => {
        clearCreatorSelection(true);
    });
}

if (subjectSearchButton) {
    subjectSearchButton.addEventListener('click', searchSubjects);
}

if (subjectLookupInput) {
    subjectLookupInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            searchSubjects();
        }
    });
}

if (subjectNewButton) {
    subjectNewButton.addEventListener('click', () => {
        clearSubjectSelection(true);
    });
}

loadCollection(true);