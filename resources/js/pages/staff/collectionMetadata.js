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

const root = document.querySelector('#metadata-root');
const identifier = root?.dataset?.identifier;

const loading = document.querySelector('#metadata-loading');
const content = document.querySelector('#metadata-content');
const alertBox = document.querySelector('#metadata-alert');
const pageTitle = document.querySelector('#metadata-page-title');
const collectionSummary = document.querySelector('#metadata-collection-summary');
const metadataList = document.querySelector('#metadata-list');

const form = document.querySelector('#metadata-form');
const elementKeySelect = document.querySelector('#element_key_select');
const elementKeyInput = document.querySelector('#element_key');
const valueTypeInput = document.querySelector('#value_type');
const valueInput = document.querySelector('#value');
const sortOrderInput = document.querySelector('#sort_order');
const sourceInput = document.querySelector('#source');
const languageCodeInput = document.querySelector('#language_code');
const reasonInput = document.querySelector('#reason');

const saveButton = document.querySelector('#metadata-save-button');
const resetFormButton = document.querySelector('#metadata-reset-form-button');
const refreshButton = document.querySelector('#metadata-refresh-button');

let currentCollection = null;
let metadataBusyId = null;

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

const VALUE_TYPE_TO_COLUMN = {
    auto: 'value_string',
    short_text: 'value_string',
    long_text: 'value_text',
    integer: 'value_integer',
    decimal: 'value_decimal',
    date: 'value_date',
    datetime: 'value_datetime',
    json: 'value_json',
};

const VALUE_COLUMN_TO_TYPE = {
    value_string: 'short_text',
    value_text: 'long_text',
    value_integer: 'integer',
    value_decimal: 'decimal',
    value_date: 'date',
    value_datetime: 'datetime',
    value_json: 'json',
};

const VALUE_TYPE_LABELS = {
    auto: 'Otomatis',
    short_text: 'Teks pendek',
    long_text: 'Teks panjang',
    integer: 'Angka bulat',
    decimal: 'Angka desimal',
    date: 'Tanggal',
    datetime: 'Tanggal dan waktu',
    json: 'Data terstruktur',
};

function valueColumnFromType(type) {
    return VALUE_TYPE_TO_COLUMN[type] || 'value_string';
}

function valueTypeFromColumn(column) {
    return VALUE_COLUMN_TO_TYPE[column] || 'short_text';
}

function valueTypeLabel(type) {
    return VALUE_TYPE_LABELS[type] || type || '-';
}

function guessValueTypeFromElementKey(elementKey) {
    const key = String(elementKey || '').toLowerCase();

    if (!key) {
        return 'short_text';
    }

    if (
        key.includes('description')
        || key.includes('scope_content')
        || key.includes('conditions_access')
        || key.includes('finding_aids')
        || key.includes('notes')
        || key.includes('provenance')
        || key.includes('history')
        || key.includes('abstract')
        || key.includes('summary')
        || key.includes('relation')
    ) {
        return 'long_text';
    }

    if (
        key.includes('date')
        || key.includes('created')
        || key.includes('issued')
        || key.includes('modified')
        || key.includes('creation.date')
    ) {
        return 'date';
    }

    if (
        key.includes('extent')
        || key.includes('measurements')
        || key.includes('dimensions')
        || key.includes('format')
    ) {
        return 'short_text';
    }

    if (
        key.includes('title')
        || key.includes('creator')
        || key.includes('subject')
        || key.includes('publisher')
        || key.includes('contributor')
        || key.includes('identifier')
        || key.includes('language')
        || key.includes('rights')
        || key.includes('type')
        || key.includes('source')
        || key.includes('coverage')
        || key.includes('classification')
        || key.includes('material')
        || key.includes('agent')
        || key.includes('location')
        || key.includes('reference_code')
        || key.includes('level_of_description')
    ) {
        return 'short_text';
    }

    return 'short_text';
}

function applySuggestedValueType() {
    if (!valueTypeInput || !elementKeyInput) {
        return;
    }

    if (valueTypeInput.value !== 'auto') {
        return;
    }

    const suggested = guessValueTypeFromElementKey(elementKeyInput.value);

    valueTypeInput.dataset.suggestedType = suggested;
    updateValueInputHint(suggested);
}

function selectedValueType() {
    if (!valueTypeInput) {
        return 'short_text';
    }

    if (valueTypeInput.value === 'auto') {
        return valueTypeInput.dataset.suggestedType || guessValueTypeFromElementKey(elementKeyInput?.value);
    }

    return valueTypeInput.value;
}

function selectedValueColumn() {
    return valueColumnFromType(selectedValueType());
}

function updateValueInputHint(type = null) {
    if (!valueInput) {
        return;
    }

    const effectiveType = type || selectedValueType();

    const placeholders = {
        short_text: 'Isi teks pendek, misalnya judul, subjek, identifier, atau nama creator.',
        long_text: 'Isi teks panjang, misalnya deskripsi, catatan, scope content, atau relasi antarkoleksi.',
        integer: 'Isi angka bulat, misalnya 1945.',
        decimal: 'Isi angka desimal, misalnya 10.5.',
        date: 'Isi tanggal dengan format YYYY-MM-DD, misalnya 1945-08-17.',
        datetime: 'Isi tanggal dan waktu, misalnya 1945-08-17 10:00:00.',
        json: 'Isi JSON valid, misalnya {"label":"nilai"}',
    };

    valueInput.placeholder = placeholders[effectiveType] || 'Isi nilai metadata.';
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
    alertBox.className = `card metadata-alert ${type}`;
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
    alertBox.className = 'card metadata-alert';
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

        if (errorElement) {
            errorElement.textContent = message;
        }

        const simpleField = field
            .replace(/^metadata\.0\./, '')
            .replace(/^metadata\.\d+\./, '');

        const inputElement = document.querySelector(`[name="${simpleField}"]`);

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
        pageTitle.textContent = `Editor Metadata: ${title}`;
    }

    document.title = `Editor Metadata: ${title} - Staff SIMPB`;

    collectionSummary.innerHTML = `
        <div class="summary-grid">
            ${summaryRow('Judul', title)}
            ${summaryRow('Record Code', collection.record_code)}
            ${summaryRow('Unit', collection.unit_type)}
            ${summaryRow('Tipe Koleksi', collection.collection_type)}
            ${summaryRow('Status Publikasi', collection.publication_status)}
            ${summaryRow('Visibility', collection.visibility)}
            ${summaryRow('Updated At', formatDateTime(collection.updated_at))}
        </div>
    `;
}

function metadataRows(collection) {
    return collection?.metadata || collection?.item_metadata || collection?.itemMetadata || [];
}

function metadataId(row) {
    return row.id || row.metadata_id;
}

function metadataStandard(row) {
    return row.standard || row.metadata_element?.standard || 'metadata';
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

function metadataValueColumn(row) {
    if (row.value_column) {
        return row.value_column;
    }

    const candidates = [
        'value_string',
        'value_text',
        'value_integer',
        'value_decimal',
        'value_date',
        'value_datetime',
        'value_json',
    ];

    return candidates.find((key) => row[key] !== null && row[key] !== undefined) || 'value_string';
}

function metadataValue(row) {
    if (row.value !== undefined && row.value !== null) {
        return row.value;
    }

    if (row.display_value !== undefined && row.display_value !== null) {
        return row.display_value;
    }

    const column = metadataValueColumn(row);
    const value = row[column];

    if (value === null || value === undefined) {
        return '-';
    }

    if (typeof value === 'object') {
        return JSON.stringify(value, null, 2);
    }

    return value;
}

function groupMetadata(rows) {
    const groups = {};

    rows.forEach((row) => {
        const standard = metadataStandard(row);

        if (!groups[standard]) {
            groups[standard] = [];
        }

        groups[standard].push(row);
    });

    return groups;
}

function renderMetadataList(collection) {
    if (!metadataList) {
        return;
    }

    const rows = metadataRows(collection);

    if (!Array.isArray(rows) || rows.length === 0) {
        metadataList.innerHTML = '<p>Belum ada metadata.</p>';
        return;
    }

    const groups = groupMetadata(rows);

    metadataList.innerHTML = Object.entries(groups)
        .map(([standard, groupRows]) => `
            <div class="metadata-group">
                <h3>${escapeHtml(String(standard).toUpperCase())}</h3>

                ${groupRows.map(renderMetadataRow).join('')}
            </div>
        `)
        .join('');

    metadataList.querySelectorAll('[data-metadata-edit]').forEach((button) => {
        button.addEventListener('click', handleEditMetadataClick);
    });

    metadataList.querySelectorAll('[data-metadata-delete]').forEach((button) => {
        button.addEventListener('click', handleDeleteMetadataClick);
    });
}

function renderMetadataRow(row) {
    const id = metadataId(row);
    const key = metadataKey(row);
    const label = metadataLabel(row);
    const column = metadataValueColumn(row);
    const value = metadataValue(row);
    const type = valueTypeFromColumn(column);

    return `
        <article class="metadata-row" data-metadata-row data-metadata-id="${escapeHtml(id)}">
            <div class="badge-list">
                <span class="badge">ID: ${escapeHtml(id || '-')}</span>
                <span class="badge">${escapeHtml(key || '-')}</span>
<span class="badge">Jenis: ${escapeHtml(valueTypeLabel(type))}</span>                <span class="badge">Sort: ${escapeHtml(row.sort_order ?? '-')}</span>
                ${row.language_code ? `<span class="badge">Lang: ${escapeHtml(row.language_code)}</span>` : ''}
                ${row.source ? `<span class="badge">Source: ${escapeHtml(row.source)}</span>` : ''}
            </div>

            <div class="metadata-row-grid">
                <div class="metadata-label">
                    ${escapeHtml(label)}
                    ${key ? `<br><small>${escapeHtml(key)}</small>` : ''}
                </div>
                <div class="metadata-value">${escapeHtml(value)}</div>
            </div>

            <div class="metadata-row-actions">
                <button
                    type="button"
                    class="secondary"
                    data-metadata-edit
                    data-id="${escapeHtml(id)}"
                    data-key="${escapeHtml(key)}"
                    data-type="${escapeHtml(type)}"
                    data-column="${escapeHtml(column)}"
                    data-value="${escapeHtml(value)}"
                    data-sort-order="${escapeHtml(row.sort_order ?? 1)}"
                    data-source="${escapeHtml(row.source ?? 'staff_ui')}"
                    data-language-code="${escapeHtml(row.language_code ?? '')}"
                >
                    Edit di Form
                </button>

                ${id ? `
                    <button
                        type="button"
                        class="danger"
                        data-metadata-delete
                        data-id="${escapeHtml(id)}"
                    >
                        Hapus
                    </button>
                ` : ''}
            </div>
        </article>
    `;
}

function resetForm() {
    if (!form) {
        return;
    }

    form.reset();

    if (valueTypeInput) {
        valueTypeInput.value = 'auto';
        valueTypeInput.dataset.suggestedType = 'short_text';
    }

    updateValueInputHint('short_text');

    if (sortOrderInput) {
        sortOrderInput.value = '1';
    }

    if (sourceInput) {
        sourceInput.value = 'staff_ui';
    }

    if (reasonInput) {
        reasonInput.value = 'Update metadata melalui editor staff.';
    }

    clearFieldErrors();
}

function setFormBusy(isBusy) {
    if (saveButton) {
        saveButton.disabled = isBusy;
        saveButton.textContent = isBusy ? 'Menyimpan...' : 'Simpan Metadata';
    }
}

function setMetadataRowBusy(id, isBusy) {
    metadataBusyId = isBusy ? id : null;

    const row = Array.from(metadataList?.querySelectorAll('[data-metadata-row]') || [])
        .find((element) => String(element.dataset.metadataId) === String(id));

    if (!row) {
        return;
    }

    row.querySelectorAll('button').forEach((button) => {
        button.disabled = isBusy;
    });
}

function castValueByColumn(value, column) {
    if (column === 'value_integer') {
        return value === '' ? null : Number.parseInt(value, 10);
    }

    if (column === 'value_decimal') {
        return value === '' ? null : Number.parseFloat(value);
    }

    if (column === 'value_json') {
        if (!value) {
            return null;
        }

        try {
            return JSON.parse(value);
        } catch {
            return value;
        }
    }

    return value;
}

function buildMetadataPayload() {
    const elementKey = elementKeyInput?.value?.trim();
    const valueType = selectedValueType();
    const valueColumn = valueColumnFromType(valueType);
    const rawValue = valueInput?.value ?? '';

    const item = {
        element_key: elementKey,
        value: castValueByColumn(rawValue, valueColumn),
        value_column: valueColumn,
        sort_order: Number.parseInt(sortOrderInput?.value || '1', 10),
        source: sourceInput?.value?.trim() || 'staff_ui',
    };

    const languageCode = languageCodeInput?.value?.trim();

    if (languageCode) {
        item.language_code = languageCode;
    }

    return {
        reason: reasonInput?.value?.trim() || 'Update metadata melalui editor staff.',
        metadata: [item],
    };
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
        currentCollection = normalizeCollectionResponse(response);

        renderCollectionSummary(currentCollection);
        renderMetadataList(currentCollection);
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

        showAlert('Metadata gagal dimuat', apiError.message || 'Terjadi kesalahan saat memuat metadata.');
    }
}

async function handleSubmit(event) {
    event.preventDefault();

    clearFieldErrors();
    hideAlert();
    setFormBusy(true);

    try {
        await api.patch(
            `/staff/collections/${encodeURIComponent(collectionIdentifier(currentCollection))}/metadata`,
            buildMetadataPayload()
        );

        showAlert('Metadata disimpan', 'Metadata berhasil ditambahkan atau diperbarui.', 'success');

        resetForm();
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
            showFieldErrors(apiError.details);
            showAlert('Metadata tidak valid', apiError.message || 'Data metadata tidak valid.', 'warning');
            return;
        }

        showAlert('Metadata gagal disimpan', apiError.message || 'Terjadi kesalahan saat menyimpan metadata.');
    } finally {
        setFormBusy(false);
    }
}

function handleEditMetadataClick(event) {
    const button = event.currentTarget;

    if (elementKeyInput) {
        elementKeyInput.value = button.dataset.key || '';
    }

    if (valueTypeInput) {
        valueTypeInput.value = button.dataset.type || valueTypeFromColumn(button.dataset.column || 'value_string');
        valueTypeInput.dataset.suggestedType = valueTypeInput.value;
        updateValueInputHint(valueTypeInput.value);
    }
    if (valueInput) {
        valueInput.value = button.dataset.value || '';
        valueInput.focus();
    }

    if (sortOrderInput) {
        sortOrderInput.value = button.dataset.sortOrder || '1';
    }

    if (sourceInput) {
        sourceInput.value = button.dataset.source || 'staff_ui';
    }

    if (languageCodeInput) {
        languageCodeInput.value = button.dataset.languageCode || '';
    }

    if (reasonInput) {
        reasonInput.value = 'Update metadata existing melalui editor staff.';
    }

    window.scrollTo({
        top: form.getBoundingClientRect().top + window.scrollY - 80,
        behavior: 'smooth',
    });
}

async function handleDeleteMetadataClick(event) {
    const id = event.currentTarget.dataset.id;

    if (!id || metadataBusyId) {
        return;
    }

    const confirmed = window.confirm('Hapus metadata ini?');

    if (!confirmed) {
        return;
    }

    const reason = window.prompt(
        'Alasan penghapusan metadata:',
        'Menghapus metadata melalui editor staff.'
    );

    if (reason === null) {
        return;
    }

    setMetadataRowBusy(id, true);
    hideAlert();

    try {
        await api.delete(
            `/staff/collections/${encodeURIComponent(collectionIdentifier(currentCollection))}/metadata/${encodeURIComponent(id)}`,
            {
                body: {
                    reason: reason.trim() || 'Menghapus metadata melalui editor staff.',
                },
            }
        );

        showAlert('Metadata dihapus', 'Metadata berhasil dihapus.', 'success');

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

        showAlert('Metadata gagal dihapus', apiError.message || 'Terjadi kesalahan saat menghapus metadata.');
    } finally {
        setMetadataRowBusy(id, false);
    }
}

if (elementKeySelect) {
    elementKeySelect.addEventListener('change', () => {
        if (elementKeyInput && elementKeySelect.value) {
            elementKeyInput.value = elementKeySelect.value;
        }

        if (valueTypeInput) {
            valueTypeInput.value = 'auto';
        }

        applySuggestedValueType();
    });
}

if (elementKeyInput) {
    elementKeyInput.addEventListener('input', () => {
        applySuggestedValueType();
    });

    elementKeyInput.addEventListener('blur', () => {
        applySuggestedValueType();
    });
}

if (valueTypeInput) {
    valueTypeInput.addEventListener('change', () => {
        if (valueTypeInput.value === 'auto') {
            applySuggestedValueType();
            return;
        }

        valueTypeInput.dataset.suggestedType = valueTypeInput.value;
        updateValueInputHint(valueTypeInput.value);
    });
}

if (form) {
    form.addEventListener('submit', handleSubmit);
}

if (resetFormButton) {
    resetFormButton.addEventListener('click', resetForm);
}

if (refreshButton) {
    refreshButton.addEventListener('click', () => {
        loadCollection(true);
    });
}

loadCollection(true);