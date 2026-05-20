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

// ------------------------------------------------------------------ //
// DOM refs
// ------------------------------------------------------------------ //

const alertBox          = document.getElementById('import-alert');
const dropZone          = document.getElementById('drop-zone');
const fileInput         = document.getElementById('file-input');
const fileSelected      = document.getElementById('file-selected');
const fileNameDisplay   = document.getElementById('file-name-display');
const btnClearFile      = document.getElementById('btn-clear-file');
const btnPreview        = document.getElementById('btn-preview');
const previewLoading    = document.getElementById('preview-loading');
const previewSection    = document.getElementById('preview-section');
const previewSummary    = document.getElementById('preview-summary');
const previewBody       = document.getElementById('preview-body');
const importAction      = document.getElementById('import-action');
const validCount        = document.getElementById('valid-count');
const btnExecute        = document.getElementById('btn-execute');
const executeLoading    = document.getElementById('execute-loading');
const resultSection     = document.getElementById('result-section');
const resultSummary     = document.getElementById('result-summary');
const resultBody        = document.getElementById('result-body');
const btnImportAgain    = document.getElementById('btn-import-again');
const btnDownloadTpl    = document.getElementById('btn-download-template');
const templateLoading   = document.getElementById('template-loading');

const unitRadios = document.querySelectorAll('input[name="unit_type"]');

// ------------------------------------------------------------------ //
// State
// ------------------------------------------------------------------ //

let selectedFile   = null;
let lastPreviewRows = [];

// ------------------------------------------------------------------ //
// Helpers
// ------------------------------------------------------------------ //

function escHtml(str) {
    return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function getUnitType() {
    for (const r of unitRadios) {
        if (r.checked) return r.value;
    }
    return 'library';
}

function showAlert(msg, type = 'error') {
    alertBox.textContent  = msg;
    alertBox.className    = 'form-alert ' + type;
    alertBox.style.display = '';
    alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function hideAlert() {
    alertBox.style.display = 'none';
    alertBox.textContent   = '';
}

function setFile(file) {
    if (!file) return;
    const ext = file.name.split('.').pop().toLowerCase();
    if (!['csv', 'xlsx', 'xls'].includes(ext)) {
        showAlert('Format file tidak didukung. Gunakan .csv atau .xlsx');
        return;
    }
    if (file.size > 5 * 1024 * 1024) {
        showAlert('Ukuran file melebihi 5 MB.');
        return;
    }
    selectedFile             = file;
    fileNameDisplay.textContent = file.name;
    fileSelected.style.display  = 'flex';
    dropZone.querySelector('.drop-zone-inner').style.display = 'none';
    btnPreview.disabled = false;

    // Reset preview & result sections
    previewSection.style.display = 'none';
    resultSection.style.display  = 'none';
    lastPreviewRows = [];
    hideAlert();
}

function clearFile() {
    selectedFile    = null;
    fileInput.value = '';
    fileSelected.style.display   = 'none';
    dropZone.querySelector('.drop-zone-inner').style.display = 'flex';
    btnPreview.disabled = true;
    previewSection.style.display = 'none';
    resultSection.style.display  = 'none';
    lastPreviewRows = [];
}

function summaryBadgesHtml(items) {
    return items.map(({ label, value, type }) =>
        `<span class="summary-badge ${escHtml(type)}">${escHtml(label)}: <strong>${escHtml(String(value))}</strong></span>`
    ).join('');
}

// ------------------------------------------------------------------ //
// Drag & Drop
// ------------------------------------------------------------------ //

dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropZone.classList.add('drag-over');
});

dropZone.addEventListener('dragleave', () => {
    dropZone.classList.remove('drag-over');
});

dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.classList.remove('drag-over');
    const file = e.dataTransfer.files[0];
    if (file) setFile(file);
});

fileInput.addEventListener('change', () => {
    if (fileInput.files[0]) setFile(fileInput.files[0]);
});

btnClearFile.addEventListener('click', clearFile);

// ------------------------------------------------------------------ //
// Download Template
// ------------------------------------------------------------------ //

btnDownloadTpl.addEventListener('click', async () => {
    const unitType = getUnitType();
    btnDownloadTpl.style.display = 'none';
    templateLoading.style.display = '';

    try {
        const token = window.sessionStorage.getItem('simpb_access_token');
        const res = await fetch(`/api/staff/collections/import/template/${unitType}`, {
            headers: {
                Authorization: `Bearer ${token}`,
            },
        });

        if (!res.ok) {
            throw new Error('Gagal mengunduh template.');
        }

        const blob = await res.blob();
        const url  = URL.createObjectURL(blob);
        const a    = document.createElement('a');
        a.href     = url;
        a.download = `template_import_${unitType}.xlsx`;
        a.click();
        URL.revokeObjectURL(url);
    } catch (err) {
        showAlert('Gagal mengunduh template: ' + err.message);
    } finally {
        btnDownloadTpl.style.display = '';
        templateLoading.style.display = 'none';
    }
});

// ------------------------------------------------------------------ //
// Preview / Validasi
// ------------------------------------------------------------------ //

btnPreview.addEventListener('click', async () => {
    if (!selectedFile) return;
    hideAlert();

    btnPreview.disabled       = true;
    previewLoading.style.display = '';
    previewSection.style.display = 'none';
    resultSection.style.display  = 'none';

    const formData = new FormData();
    formData.append('file', selectedFile);
    formData.append('unit_type', getUnitType());

    try {
        const token = window.sessionStorage.getItem('simpb_access_token');
        const res   = await fetch('/api/staff/collections/import/preview', {
            method: 'POST',
            headers: { Authorization: `Bearer ${token}` },
            body: formData,
        });

        const json = await res.json();

        if (!res.ok || !json.success) {
            const msg = json.error?.message || json.message || 'Validasi gagal.';
            showAlert(msg);
            return;
        }

        renderPreview(json.data);
    } catch (err) {
        showAlert('Terjadi kesalahan jaringan: ' + err.message);
    } finally {
        btnPreview.disabled       = false;
        previewLoading.style.display = 'none';
    }
});

function renderPreview(data) {
    lastPreviewRows = data.rows;

    previewSummary.innerHTML = summaryBadgesHtml([
        { label: 'Total Baris', value: data.total_rows,    type: 'total'   },
        { label: 'Valid',       value: data.total_valid,   type: 'valid'   },
        { label: 'Perlu Diperbaiki', value: data.total_invalid, type: 'invalid' },
    ]);

    previewBody.innerHTML = data.rows.map(row => `
        <tr class="${row.valid ? '' : 'row-invalid'}">
            <td class="col-row">${escHtml(row.row)}</td>
            <td class="col-status">
                <span class="status-badge ${row.valid ? 'valid' : 'invalid'}">
                    ${row.valid ? '✓ Valid' : '✗ Error'}
                </span>
            </td>
            <td>${escHtml(row.data.record_code)}</td>
            <td>${escHtml(row.data.title)}</td>
            <td style="max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="${escHtml(row.data.creators)}">${escHtml(row.data.creators)}</td>
            <td>
                ${row.errors && row.errors.length > 0
                    ? `<ul class="error-list">${row.errors.map(e => `<li>${escHtml(e)}</li>`).join('')}</ul>`
                    : '<span style="color:#15803d;">—</span>'
                }
            </td>
        </tr>
    `).join('');

    previewSection.style.display = '';

    if (data.total_valid > 0) {
        validCount.textContent = data.total_valid;
        importAction.style.display = '';
    } else {
        importAction.style.display = 'none';
    }

    previewSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// ------------------------------------------------------------------ //
// Execute Import
// ------------------------------------------------------------------ //

btnExecute.addEventListener('click', async () => {
    if (!selectedFile) return;

    const confirmMsg = `Anda akan mengimpor ${validCount.textContent} baris koleksi yang valid.\n\nBaris dengan error akan dilewati.\n\nLanjutkan?`;
    if (!window.confirm(confirmMsg)) return;

    hideAlert();
    btnExecute.disabled       = true;
    executeLoading.style.display = '';

    const formData = new FormData();
    formData.append('file', selectedFile);
    formData.append('unit_type', getUnitType());

    try {
        const token = window.sessionStorage.getItem('simpb_access_token');
        const res   = await fetch('/api/staff/collections/import/execute', {
            method: 'POST',
            headers: { Authorization: `Bearer ${token}` },
            body: formData,
        });

        const json = await res.json();

        if (!res.ok || !json.success) {
            const msg = json.error?.message || json.message || 'Import gagal.';
            showAlert(msg);
            return;
        }

        renderResult(json.data, json.message);
    } catch (err) {
        showAlert('Terjadi kesalahan jaringan: ' + err.message);
    } finally {
        btnExecute.disabled       = false;
        executeLoading.style.display = 'none';
    }
});

function renderResult(data, message) {
    showAlert(message || 'Import selesai.', data.total_error === 0 ? 'success' : 'error');

    resultSummary.innerHTML = summaryBadgesHtml([
        { label: 'Total',    value: data.total_rows,    type: 'total'   },
        { label: 'Berhasil', value: data.total_success, type: 'success' },
        { label: 'Gagal',    value: data.total_error,   type: 'error'   },
        { label: 'Dilewati', value: data.total_skipped, type: 'skipped' },
    ]);

    resultBody.innerHTML = data.rows.map(row => {
        const statusLabel = row.status === 'success' ? '✓ Berhasil'
            : row.status === 'error'   ? '✗ Gagal'
            : '– Dilewati';

        const detailLink = row.identifier
            ? `<a href="/staff/collections/${encodeURIComponent(row.identifier)}" class="button-link secondary" style="padding:0.2rem 0.6rem; font-size:0.8rem;">Lihat</a>`
            : '—';

        return `
            <tr>
                <td class="col-row">${escHtml(row.row)}</td>
                <td class="col-status">
                    <span class="status-badge ${escHtml(row.status)}">${statusLabel}</span>
                </td>
                <td>${escHtml(row.record_code || '—')}</td>
                <td>${escHtml(row.title || '—')}</td>
                <td>${escHtml(row.reason || '—')}</td>
                <td>${detailLink}</td>
            </tr>
        `;
    }).join('');

    resultSection.style.display = '';
    resultSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// ------------------------------------------------------------------ //
// Import Again
// ------------------------------------------------------------------ //

btnImportAgain.addEventListener('click', () => {
    clearFile();
    previewSection.style.display = 'none';
    resultSection.style.display  = 'none';
    hideAlert();
    window.scrollTo({ top: 0, behavior: 'smooth' });
});
