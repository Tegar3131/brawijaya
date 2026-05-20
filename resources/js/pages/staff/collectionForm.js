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

document.addEventListener('DOMContentLoaded', () => {
    const appEl = document.getElementById('collection-form-app');
    if (!appEl) return;

    const mode       = appEl.dataset.mode;       // 'create' | 'edit'
    let   unitType   = appEl.dataset.unitType;   // 'library' | 'museum'
    const identifier = appEl.dataset.identifier; // untuk mode edit

    const form             = document.getElementById('collection-form');
    const submitBtn        = document.getElementById('btn-submit');
    const alertBox         = document.getElementById('form-alert');
    const sectionLibrary   = document.getElementById('section-library');
    const sectionMuseum    = document.getElementById('section-museum');
    const sectionReason    = document.getElementById('section-reason');
    const creatorsContainer = document.getElementById('creators-container');
    const subjectsContainer = document.getElementById('subjects-container');
    const btnAddCreator    = document.getElementById('btn-add-creator');
    const btnAddSubject    = document.getElementById('btn-add-subject');
    const creatorTemplate  = document.getElementById('creator-template');
    const subjectTemplate  = document.getElementById('subject-template');

    // ------------------------------------------------------------------ //
    // Helpers
    // ------------------------------------------------------------------ //

    function showAlert(message, type = 'error') {
        if (!alertBox) { alert(message); return; }
        alertBox.textContent = message;
        alertBox.className = 'form-alert ' + type;
        alertBox.style.display = '';
        alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function hideAlert() {
        if (!alertBox) return;
        alertBox.style.display = 'none';
        alertBox.textContent = '';
    }

    function applyUnitTypeUI() {
        if (sectionLibrary) sectionLibrary.style.display = unitType === 'library' ? 'block' : 'none';
        if (sectionMuseum)  sectionMuseum.style.display  = unitType === 'museum'  ? 'block' : 'none';
    }

    function setVal(id, value) {
        const el = document.getElementById(id);
        if (el && value !== null && value !== undefined) el.value = value;
    }

    // ------------------------------------------------------------------ //
    // Creator & Subject rows
    // ------------------------------------------------------------------ //

    function addCreatorRow(data = {}) {
        if (!creatorTemplate) return;
        const clone = creatorTemplate.content.cloneNode(true);
        const item  = clone.querySelector('.creator-item');

        item.querySelector('.input-creator-name').value    = data.name       || '';
        item.querySelector('.input-creator-role').value    = data.role       || '';
        item.querySelector('.input-creator-primary').checked = data.is_primary || false;

        item.querySelector('.btn-remove-item').addEventListener('click', () => item.remove());
        creatorsContainer.appendChild(item);
    }

    function addSubjectRow(data = {}) {
        if (!subjectTemplate) return;
        const clone = subjectTemplate.content.cloneNode(true);
        const item  = clone.querySelector('.subject-item');

        item.querySelector('.input-subject-term').value = data.term || '';
        const typeEl = item.querySelector('.input-subject-type');
        if (typeEl && data.type) typeEl.value = data.type;

        item.querySelector('.btn-remove-item').addEventListener('click', () => item.remove());
        subjectsContainer.appendChild(item);
    }

    if (btnAddCreator) btnAddCreator.addEventListener('click', () => addCreatorRow());
    if (btnAddSubject) btnAddSubject.addEventListener('click', () => addSubjectRow());

    // ------------------------------------------------------------------ //
    // Load lookup: kategori
    // ------------------------------------------------------------------ //

    async function loadLookups() {
        try {
            const catSelect = document.getElementById('category_id');
            if (!catSelect) return;

            const catRes = await fetch('/api/catalog/categories', {
                headers: { Accept: 'application/json' },
            });

            if (catRes.ok) {
                const catData = await catRes.json();
                const items = catData.data || catData;
                if (Array.isArray(items)) {
                    items.forEach((cat) => {
                        const opt = document.createElement('option');
                        opt.value = cat.id;
                        opt.textContent = cat.name;
                        catSelect.appendChild(opt);
                    });
                }
            }
        } catch (err) {
            console.warn('Gagal memuat kategori:', err);
        }
    }

    // ------------------------------------------------------------------ //
    // Load collection data (mode edit)
    // ------------------------------------------------------------------ //

    async function loadCollectionData() {
        submitBtn.disabled   = true;
        submitBtn.textContent = 'Memuat Data...';

        try {
            const response   = await api.get(`/staff/collections/${encodeURIComponent(identifier)}`);
            const data       = response?.data?.collection || response?.data || response;

            unitType = data.unit_type;
            applyUnitTypeUI();

            // Base fields
            setVal('record_code',        data.record_code);
            setVal('collection_type',    data.collection_type);
            setVal('title',              data.title);
            setVal('subtitle',           data.subtitle);
            setVal('description',        data.description);
            setVal('language_code',      data.language_code);
            setVal('date_display',       data.date_display);
            setVal('publication_status', data.publication_status);
            setVal('visibility',         data.visibility);

            if (data.category?.id) setVal('category_id', data.category.id);

            // Library fields
            if (unitType === 'library' && data.library) {
                setVal('bibliographic_level', data.library.bibliographic_level);
                setVal('isbn13',              data.library.isbn13);
                setVal('isbn10',              data.library.isbn10);
                setVal('issn',                data.library.issn);
                setVal('doi',                 data.library.doi);
                setVal('publisher_name',      data.library.publisher_name);
                setVal('publisher_place',     data.library.publisher_place);
                setVal('publication_year',    data.library.publication_year);
                setVal('edition',             data.library.edition);
                setVal('series_title',        data.library.series_title);
                setVal('ddc_classification',  data.library.ddc_classification);
                setVal('call_number',         data.library.call_number);
                setVal('physical_extent',     data.library.physical_extent);
                setVal('pages',               data.library.pages);
            }

            // Museum fields
            if (unitType === 'museum' && data.museum) {
                setVal('inventory_number',    data.museum.inventory_number);
                setVal('object_name',         data.museum.object_name);
                setVal('object_type_label',   data.museum.object_type_label);
                setVal('classification',      data.museum.classification);
                setVal('maker_name',          data.museum.maker_name);
                setVal('culture',             data.museum.culture);
                setVal('period_display',      data.museum.period_display);
                setVal('material_summary',    data.museum.material_summary);
                setVal('technique_summary',   data.museum.technique_summary);
                setVal('dimensions_display',  data.museum.dimensions_display);
                setVal('weight_display',      data.museum.weight_display);
                setVal('provenance_history',  data.museum.provenance_history);
                setVal('acquisition_method',  data.museum.acquisition_method);
                setVal('condition_current',   data.museum.condition_current);
            }

            // Creators
            if (Array.isArray(data.creators) && data.creators.length > 0) {
                data.creators.forEach((c) => addCreatorRow(c));
            } else {
                addCreatorRow();
            }

            // Subjects
            if (Array.isArray(data.subjects) && data.subjects.length > 0) {
                data.subjects.forEach((s) => addSubjectRow(s));
            } else {
                addSubjectRow();
            }

            submitBtn.disabled   = false;
            submitBtn.textContent = 'Simpan Perubahan';
        } catch (err) {
            const apiError = normalizeApiError(err);

            if (apiError.isUnauthenticated()) {
                window.location.assign('/login?redirect=' + encodeURIComponent(window.location.pathname));
                return;
            }

            showAlert('Gagal memuat data koleksi: ' + (apiError.message || 'Terjadi kesalahan.'));
            submitBtn.disabled   = false;
            submitBtn.textContent = 'Simpan Perubahan';
        }
    }

    // ------------------------------------------------------------------ //
    // Build payload from form
    // ------------------------------------------------------------------ //

    function buildPayload() {
        const payload = {
            collection:   {},
            library_item: {},
            museum_item:  {},
            creators:     [],
            subjects:     [],
        };

        const formData = new FormData(form);

        for (const [key, value] of formData.entries()) {
            const v = typeof value === 'string' ? value.trim() : value;
            if (v === '') continue;

            if (key === 'update_reason') {
                payload.update_reason = v;
            } else if (key.startsWith('collection[')) {
                const k = key.slice('collection['.length, -1);
                payload.collection[k] = v;
            } else if (key.startsWith('library_item[')) {
                const k = key.slice('library_item['.length, -1);
                payload.library_item[k] = v;
            } else if (key.startsWith('museum_item[')) {
                const k = key.slice('museum_item['.length, -1);
                payload.museum_item[k] = v;
            }
        }

        // Creators
        creatorsContainer.querySelectorAll('.creator-item').forEach((item) => {
            const name = item.querySelector('.input-creator-name')?.value?.trim();
            if (name) {
                payload.creators.push({
                    name,
                    role:       item.querySelector('.input-creator-role')?.value || 'author',
                    is_primary: item.querySelector('.input-creator-primary')?.checked || false,
                });
            }
        });

        // Subjects
        subjectsContainer.querySelectorAll('.subject-item').forEach((item) => {
            const term = item.querySelector('.input-subject-term')?.value?.trim();
            if (term) {
                payload.subjects.push({
                    term,
                    type: item.querySelector('.input-subject-type')?.value || 'topical',
                });
            }
        });

        // Bersihkan objek kosong
        if (Object.keys(payload.library_item).length === 0) delete payload.library_item;
        if (Object.keys(payload.museum_item).length === 0)  delete payload.museum_item;

        return payload;
    }

    // ------------------------------------------------------------------ //
    // Submit handler
    // ------------------------------------------------------------------ //

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        hideAlert();

        submitBtn.disabled   = true;
        submitBtn.textContent = 'Menyimpan...';

        const payload = buildPayload();

        try {
            let response;

            if (mode === 'create') {
                response = await api.post(`/staff/collections/${unitType}`, payload);
            } else {
                response = await api.patch(`/staff/collections/${encodeURIComponent(identifier)}`, payload);
            }

            const savedIdentifier = response?.data?.record_code
                || response?.data?.ulid
                || response?.data?.id
                || identifier;

            showAlert(
                response?.message || (mode === 'create' ? 'Koleksi berhasil dibuat!' : 'Koleksi berhasil diperbarui!'),
                'success'
            );

            // Redirect ke halaman detail setelah singkat
            setTimeout(() => {
                window.location.href = `/staff/collections/${encodeURIComponent(savedIdentifier)}`;
            }, 800);

        } catch (err) {
            const apiError = normalizeApiError(err);

            if (apiError.isUnauthenticated()) {
                window.location.assign('/login?redirect=' + encodeURIComponent(window.location.pathname));
                return;
            }

            if (apiError.isValidationError() && apiError.details) {
                const messages = Object.values(apiError.details).flat().join(' • ');
                showAlert('Validasi gagal: ' + messages);
            } else {
                showAlert('Gagal menyimpan: ' + (apiError.message || 'Terjadi kesalahan.'));
            }

            console.error('[collectionForm] submit error:', apiError);
        } finally {
            submitBtn.disabled   = false;
            submitBtn.textContent = mode === 'create' ? 'Simpan Koleksi' : 'Simpan Perubahan';
        }
    });

    // ------------------------------------------------------------------ //
    // Init
    // ------------------------------------------------------------------ //

    loadLookups();

    if (mode === 'create') {
        applyUnitTypeUI();
        addCreatorRow();
        addSubjectRow();
    } else if (mode === 'edit') {
        if (sectionReason) sectionReason.style.display = 'block';
        const reasonEl = document.getElementById('update_reason');
        if (reasonEl) reasonEl.setAttribute('required', 'required');
        const recordCodeEl = document.getElementById('record_code');
        if (recordCodeEl) recordCodeEl.setAttribute('readonly', 'readonly');
        loadCollectionData();
    }
});
