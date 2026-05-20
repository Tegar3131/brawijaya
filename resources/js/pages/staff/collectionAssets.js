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

const root = document.querySelector('#asset-root');
const identifier = root?.dataset?.identifier;

const loading = document.querySelector('#asset-loading');
const content = document.querySelector('#asset-content');
const alertBox = document.querySelector('#asset-alert');
const pageTitle = document.querySelector('#asset-page-title');
const collectionSummary = document.querySelector('#asset-collection-summary');
const assetList = document.querySelector('#asset-list');
const uploadForm = document.querySelector('#asset-upload-form');
const registerForm = document.querySelector('#asset-register-form');
const uploadSaveButton = document.querySelector('#upload-save-button');
const registerSaveButton = document.querySelector('#register-save-button');
const refreshButton = document.querySelector('#asset-refresh-button');

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
    alertBox.className = `card asset-alert ${type}`;
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
    alertBox.className = 'card asset-alert';
    alertBox.innerHTML = '';
}

function clearFieldErrors(scope) {
    if (!scope) return;
    const errorElements = scope.querySelectorAll('.field-error');
    errorElements.forEach((el) => {
        el.textContent = '';
    });
    const inputs = scope.querySelectorAll('[aria-invalid]');
    inputs.forEach((input) => {
        input.removeAttribute('aria-invalid');
    });
}

function showFieldErrors(details, scope) {
    if (!scope) return;
    clearFieldErrors(scope);
    const errors = validationErrorsToObject(details);
    Object.entries(errors).forEach(([field, message]) => {
        const errEl = scope.querySelector(`[data-error-for="${field}"]`);
        if (errEl) {
            errEl.textContent = message;
        }
        const inputEl = scope.querySelector(`[name="${field}"]`);
        if (inputEl) {
            inputEl.setAttribute('aria-invalid', 'true');
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

function digitalAssets(collection) {
    return collection?.digital_assets || collection?.digitalAssets || [];
}

function renderCollectionSummary(collection) {
    if (!collectionSummary) {
        return;
    }
    const title = displayTitle(collection);
    
    collectionSummary.innerHTML = `
        <div class="badge-list" style="margin-bottom: 12px;">
            <span class="badge">${escapeHtml(collection.record_code || '-')}</span>
            <span class="badge">${escapeHtml(collection.unit_type || '-')}</span>
            <span class="badge">${escapeHtml(collection.collection_type || '-')}</span>
            <span class="badge">Status: ${escapeHtml(collection.publication_status || '-')}</span>
            <span class="badge">Visibility: ${escapeHtml(collection.visibility || '-')}</span>
        </div>
        <div class="summary-grid">
            <div class="summary-label">Judul Koleksi</div>
            <div class="summary-value">${escapeHtml(title)}</div>
            
            <div class="summary-label">Deskripsi</div>
            <div class="summary-value">${escapeHtml(collection.description || '-')}</div>
            
            <div class="summary-label">Rights Status</div>
            <div class="summary-value">${escapeHtml(collection.rights_status || '-')}</div>
        </div>
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

function renderAssets(collection) {
    if (!assetList) {
        return;
    }
    const assets = digitalAssets(collection);
    if (!Array.isArray(assets) || assets.length === 0) {
        assetList.innerHTML = '<p>Belum ada digital asset.</p>';
        return;
    }

    assetList.innerHTML = `
        <div class="asset-grid">
            ${assets.map((asset) => {
                const preview = assetPreviewUrl(asset);
                const isImage = String(asset.mime_type || '').startsWith('image/');
                
                return `
                    <div class="asset-item">
                        ${preview && isImage ? `
                            <div class="asset-preview">
                                <img src="${escapeHtml(preview)}" alt="${escapeHtml(asset.caption || 'Digital asset')}">
                            </div>
                        ` : `
                            <div class="asset-preview">
                                <span class="muted">${escapeHtml(String(asset.extension || 'file').toUpperCase())} File</span>
                            </div>
                        `}
                        
                        <div class="summary-grid">
                            <div class="summary-label">ID / ULID</div>
                            <div class="summary-value">${escapeHtml(asset.id)} / ${escapeHtml(asset.ulid)}</div>
                            
                            <div class="summary-label">Asset Type</div>
                            <div class="summary-value">${escapeHtml(asset.asset_type || '-')}</div>
                            
                            <div class="summary-label">File Role</div>
                            <div class="summary-value">${escapeHtml(asset.file_role || '-')}</div>
                            
                            <div class="summary-label">Disk</div>
                            <div class="summary-value">${escapeHtml(asset.disk || '-')}</div>
                            
                            <div class="summary-label">Path</div>
                            <div class="summary-value" style="font-family: monospace; font-size: 12px; word-break: break-all;">${escapeHtml(asset.path || '-')}</div>
                            
                            <div class="summary-label">Access Level</div>
                            <div class="summary-value">${escapeHtml(asset.access_level || '-')}</div>
                            
                            <div class="summary-label">MIME Type / Ext</div>
                            <div class="summary-value">${escapeHtml(asset.mime_type || '-')} / ${escapeHtml(asset.extension || '-')}</div>
                            
                            <div class="summary-label">Size Bytes</div>
                            <div class="summary-value">${escapeHtml(asset.size_bytes || '-')} bytes</div>
                            
                            <div class="summary-label">Checksum</div>
                            <div class="summary-value" style="font-family: monospace; font-size: 11px; word-break: break-all;">${escapeHtml(asset.checksum_sha256 || '-')}</div>
                            
                            <div class="summary-label">Dimensi</div>
                            <div class="summary-value">${asset.width_px ? `${asset.width_px}x${asset.height_px} px` : '-'}</div>
                            
                            <div class="summary-label">Caption</div>
                            <div class="summary-value">${escapeHtml(asset.caption || '-')}</div>
                            
                            <div class="summary-label">Is Primary / Public</div>
                            <div class="summary-value">${asset.is_primary ? 'Ya' : 'Tidak'} / ${asset.is_public ? 'Ya' : 'Tidak'}</div>
                            
                            <div class="summary-label">Sort Order</div>
                            <div class="summary-value">${escapeHtml(asset.sort_order ?? 1)}</div>
                            
                            <div class="summary-label">Dibuat</div>
                            <div class="summary-value">${escapeHtml(asset.created_at || '-')}</div>
                        </div>
                    </div>
                `;
            }).join('')}
        </div>
    `;
}

function renderCollection(collection) {
    if (pageTitle) {
        pageTitle.textContent = `Digital Asset: ${displayTitle(collection)}`;
    }
    document.title = `Digital Asset: ${displayTitle(collection)} - Staff SIMPB`;

    renderCollectionSummary(collection);
    renderAssets(collection);
}

function formValue(form, name) {
    const el = form?.querySelector(`[name="${name}"]`);
    return el ? el.value : '';
}

function formChecked(form, name) {
    const el = form?.querySelector(`[name="${name}"]`);
    return el ? el.checked : false;
}

function setButtonBusy(button, isBusy, busyText, normalText) {
    if (!button) {
        return;
    }
    button.disabled = isBusy;
    button.textContent = isBusy ? busyText : normalText;
}

function buildRegisterPayload() {
    const form = registerForm;
    return {
        path: formValue(form, 'path') || null,
        asset_type: formValue(form, 'asset_type') || null,
        file_role: formValue(form, 'file_role') || null,
        disk: formValue(form, 'disk') || 'public',
        mime_type: formValue(form, 'mime_type') || null,
        extension: formValue(form, 'extension') || null,
        access_level: formValue(form, 'access_level') || 'internal',
        caption: formValue(form, 'caption') || null,
        sort_order: parseInt(formValue(form, 'sort_order') || '1', 10),
        is_primary: formChecked(form, 'is_primary') ? 1 : 0,
        is_public: formChecked(form, 'is_public') ? 1 : 0,
        reason: formValue(form, 'reason') || 'Register metadata digital asset melalui staff manager.'
    };
}

function buildUploadFormData() {
    const form = uploadForm;
    const formData = new FormData();
    
    const fileEl = form?.querySelector('[name="file"]');
    if (fileEl && fileEl.files.length > 0) {
        formData.append('file', fileEl.files[0]);
    }
    
    formData.append('asset_type', formValue(form, 'asset_type') || '');
    formData.append('file_role', formValue(form, 'file_role') || '');
    formData.append('disk', formValue(form, 'disk') || '');
    formData.append('access_level', formValue(form, 'access_level') || 'internal');
    formData.append('caption', formValue(form, 'caption') || '');
    formData.append('sort_order', formValue(form, 'sort_order') || '1');
    formData.append('is_primary', formChecked(form, 'is_primary') ? '1' : '0');
    formData.append('is_public', formChecked(form, 'is_public') ? '1' : '0');
    formData.append('reason', formValue(form, 'reason') || 'Upload file digital asset melalui staff manager.');

    return formData;
}

async function multipartPost(path, formData) {
    const token = api.getToken();
    const headers = {
        'Accept': 'application/json',
    };
    if (token) {
        headers['Authorization'] = `Bearer ${token}`;
    }
    
    const response = await fetch('/api' + path, {
        method: 'POST',
        headers,
        body: formData
    });

    const contentType = response.headers.get('content-type') || '';
    const isJson = contentType.includes('application/json');
    const payload = isJson ? await response.json() : await response.text();

    if (response.ok) {
        return payload;
    }

    if (isJson && payload?.error) {
        const err = new Error(payload.error.message || 'Multipart request failed');
        err.status = response.status;
        err.code = payload.error.code || 'VALIDATION_ERROR';
        err.details = payload.error.details || null;
        err.raw = payload;
        throw err;
    }

    if (isJson && payload?.message) {
        const err = new Error(payload.message || 'Multipart request failed');
        err.status = response.status;
        err.code = 'VALIDATION_ERROR';
        err.details = payload.errors || null;
        err.raw = payload;
        throw err;
    }
    
    const err = new Error(typeof payload === 'string' ? payload : 'Multipart request failed');
    err.status = response.status;
    err.code = 'NON_JSON_ERROR';
    err.raw = payload;
    throw err;
}

function messageFromError(error, fallback) {
    if (error?.raw?.error?.message) {
        return error.raw.error.message;
    }
    if (error?.raw?.message) {
        return error.raw.message;
    }
    return error?.message || fallback;
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
        
        // Memeriksa scope role di client side dengan data type dari database jika data tersedia
        const user = api.getStoredUser();
        if (user && Array.isArray(user.roles)) {
            const roles = user.roles;
            const unitType = String(collection.unit_type || '').toLowerCase();
            
            const isAdmin = roles.includes('admin');
            const isPustakawan = roles.includes('pustakawan');
            const isKurator = roles.includes('kurator');
            
            if (!isAdmin) {
                if (unitType === 'library' && !isPustakawan) {
                    window.location.assign('/forbidden');
                    return;
                }
                if (unitType === 'museum' && !isKurator) {
                    window.location.assign('/forbidden');
                    return;
                }
            }
        }

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

async function handleUpload(event) {
    event.preventDefault();
    if (!uploadForm || !uploadSaveButton) return;
    
    clearFieldErrors(uploadForm);
    hideAlert();
    setButtonBusy(uploadSaveButton, true, 'Sedang mengunggah...', 'Upload File');
    
    try {
        const formData = buildUploadFormData();
        const endpoint = `/staff/collections/${encodeURIComponent(identifier)}/digital-assets/upload`;
        
        await multipartPost(endpoint, formData);
        
        showAlert('Sukses', 'Berkas fisik digital asset berhasil diunggah.', 'success');
        uploadForm.reset();
        
        // Reset default values
        const uploadAccessLevel = document.querySelector('#upload_access_level');
        if (uploadAccessLevel) uploadAccessLevel.value = 'internal';
        const uploadSortOrder = document.querySelector('#upload_sort_order');
        if (uploadSortOrder) uploadSortOrder.value = '1';
        const uploadReason = document.querySelector('#upload_reason');
        if (uploadReason) uploadReason.value = 'Upload file digital asset melalui staff manager.';
        
        await loadCollection(false);
    } catch (error) {
        const msg = messageFromError(error, 'Gagal mengunggah berkas fisik.');
        showAlert('Gagal mengunggah', msg, 'error');
        
        if (error.details) {
            showFieldErrors(error.details, uploadForm);
        }
    } finally {
        setButtonBusy(uploadSaveButton, false, '', 'Upload File');
    }
}

async function handleRegister(event) {
    event.preventDefault();
    if (!registerForm || !registerSaveButton) return;

    clearFieldErrors(registerForm);
    hideAlert();
    setButtonBusy(registerSaveButton, true, 'Sedang mendaftarkan...', 'Register Asset');

    try {
        const payload = buildRegisterPayload();
        const endpoint = `/staff/collections/${encodeURIComponent(identifier)}/digital-assets`;
        
        await api.post(endpoint, payload);
        
        showAlert('Sukses', 'Metadata digital asset berhasil diregistrasikan.', 'success');
        registerForm.reset();
        
        // Reset default values
        const registerDisk = document.querySelector('#register_disk');
        if (registerDisk) registerDisk.value = 'public';
        const registerAccessLevel = document.querySelector('#register_access_level');
        if (registerAccessLevel) registerAccessLevel.value = 'internal';
        const registerSortOrder = document.querySelector('#register_sort_order');
        if (registerSortOrder) registerSortOrder.value = '1';
        const registerReason = document.querySelector('#register_reason');
        if (registerReason) registerReason.value = 'Register metadata digital asset melalui staff manager.';
        
        await loadCollection(false);
    } catch (error) {
        const apiError = normalizeApiError(error);
        const msg = messageFromError(apiError, 'Gagal meregistrasikan metadata.');
        showAlert('Gagal meregistrasi', msg, 'error');
        
        if (apiError.details) {
            showFieldErrors(apiError.details, registerForm);
        }
    } finally {
        setButtonBusy(registerSaveButton, false, '', 'Register Asset');
    }
}

// Initial setup
if (identifier) {
    loadCollection();
}

if (uploadForm) {
    uploadForm.addEventListener('submit', handleUpload);
}

if (registerForm) {
    registerForm.addEventListener('submit', handleRegister);
}

if (refreshButton) {
    refreshButton.addEventListener('click', () => {
        hideAlert();
        loadCollection();
    });
}
