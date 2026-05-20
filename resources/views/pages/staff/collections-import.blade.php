@extends('layouts.staff')

@section('title', 'Import Koleksi Massal - SIMPB')

@push('scripts')
    @vite('resources/js/pages/staff/collectionImport.js')
@endpush

@section('content')
<div id="import-app" class="page-container">

    {{-- Header --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Import Koleksi Massal</h1>
            <p class="page-subtitle">Upload file CSV atau Excel (.xlsx) untuk menambahkan banyak koleksi sekaligus.</p>
        </div>
        <a href="{{ route('staff.collections.index') }}" class="button-link secondary">
            ← Kembali ke Daftar Koleksi
        </a>
    </div>

    {{-- Alert Box --}}
    <div id="import-alert" class="form-alert" style="display:none;"></div>

    {{-- Step 1: Pilih Unit Type & Download Template --}}
    <div class="card import-step">
        <div class="step-header">
            <span class="step-number">1</span>
            <h2 class="step-title">Pilih Jenis Koleksi & Download Template</h2>
        </div>
        <div class="step-body">
            <div class="unit-type-selector">
                <label class="unit-radio-card" id="radio-library">
                    <input type="radio" name="unit_type" value="library" checked>
                    <div class="unit-card-inner">
                        <span class="unit-icon">📚</span>
                        <span class="unit-label">Koleksi Perpustakaan</span>
                        <span class="unit-desc">Buku, jurnal, tesis, dll.</span>
                    </div>
                </label>
                <label class="unit-radio-card" id="radio-museum">
                    <input type="radio" name="unit_type" value="museum">
                    <div class="unit-card-inner">
                        <span class="unit-icon">🏛️</span>
                        <span class="unit-label">Koleksi Museum</span>
                        <span class="unit-desc">Artefak, foto sejarah, dokumen arsip, dll.</span>
                    </div>
                </label>
            </div>

            <div class="template-download-section">
                <p class="muted">Download template terlebih dahulu, isi data koleksi Anda, lalu upload kembali.</p>
                <button id="btn-download-template" class="button-link primary download-btn">
                    ⬇ Download Template Excel
                </button>
                <span id="template-loading" style="display:none;" class="muted">Mempersiapkan template...</span>
            </div>
        </div>
    </div>

    {{-- Step 2: Upload File --}}
    <div class="card import-step">
        <div class="step-header">
            <span class="step-number">2</span>
            <h2 class="step-title">Upload File</h2>
        </div>
        <div class="step-body">
            <div id="drop-zone" class="drop-zone">
                <div class="drop-zone-inner">
                    <span class="drop-icon">📂</span>
                    <p class="drop-text">Drag & drop file di sini, atau</p>
                    <label class="button-link secondary" style="cursor:pointer;">
                        Pilih File
                        <input type="file" id="file-input" accept=".csv,.xlsx,.xls" style="display:none;">
                    </label>
                    <p class="drop-hint">Format: CSV atau Excel (.xlsx) • Maksimum 5 MB • Maksimum 500 baris</p>
                </div>
                <div id="file-selected" class="file-selected-info" style="display:none;">
                    <span class="file-icon">📄</span>
                    <span id="file-name-display"></span>
                    <button id="btn-clear-file" class="btn-clear" title="Hapus file">✕</button>
                </div>
            </div>

            <div style="margin-top: 1rem;">
                <button id="btn-preview" class="button-link primary" disabled>
                    🔍 Validasi & Preview Data
                </button>
                <span id="preview-loading" style="display:none;" class="muted loading-text">
                    <span class="spinner"></span> Memproses file...
                </span>
            </div>
        </div>
    </div>

    {{-- Step 3: Preview --}}
    <div id="preview-section" class="card import-step" style="display:none;">
        <div class="step-header">
            <span class="step-number">3</span>
            <h2 class="step-title">Preview & Validasi Data</h2>
        </div>
        <div class="step-body">
            <div id="preview-summary" class="preview-summary"></div>

            <div class="table-scroll">
                <table id="preview-table" class="import-table">
                    <thead>
                        <tr>
                            <th class="col-row">#</th>
                            <th class="col-status">Status</th>
                            <th>Kode Record</th>
                            <th>Judul</th>
                            <th>Creator</th>
                            <th>Keterangan Error</th>
                        </tr>
                    </thead>
                    <tbody id="preview-body"></tbody>
                </table>
            </div>

            <div id="import-action" style="margin-top:1.5rem; display:none;">
                <button id="btn-execute" class="button-link success import-execute-btn">
                    ✅ Impor <span id="valid-count">0</span> Baris Valid
                </button>
                <span id="execute-loading" style="display:none;" class="muted loading-text">
                    <span class="spinner"></span> Mengimpor data...
                </span>
            </div>
        </div>
    </div>

    {{-- Step 4: Hasil Import --}}
    <div id="result-section" class="card import-step" style="display:none;">
        <div class="step-header">
            <span class="step-number">4</span>
            <h2 class="step-title">Hasil Import</h2>
        </div>
        <div class="step-body">
            <div id="result-summary" class="preview-summary"></div>

            <div class="table-scroll">
                <table id="result-table" class="import-table">
                    <thead>
                        <tr>
                            <th class="col-row">#</th>
                            <th class="col-status">Status</th>
                            <th>Kode Record</th>
                            <th>Judul</th>
                            <th>Keterangan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="result-body"></tbody>
                </table>
            </div>

            <div style="margin-top:1.5rem;">
                <a href="{{ route('staff.collections.index') }}" class="button-link primary">
                    Lihat Daftar Koleksi
                </a>
                <button id="btn-import-again" class="button-link secondary" style="margin-left:0.75rem;">
                    Import Lagi
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* ===== Import Page Styles ===== */
.page-container { max-width: 1100px; margin: 0 auto; padding: 1.5rem; }
.page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2rem; gap: 1rem; flex-wrap: wrap; }
.page-title { font-size: 1.75rem; font-weight: 700; color: var(--color-text-heading, #1e3a5f); margin: 0 0 0.25rem; }
.page-subtitle { color: var(--color-text-muted, #6b7280); margin: 0; }

.card { background: var(--color-surface, #fff); border: 1px solid var(--color-border, #e5e7eb); border-radius: 12px; margin-bottom: 1.5rem; overflow: hidden; }
.import-step { }
.step-header { display: flex; align-items: center; gap: 1rem; padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--color-border, #e5e7eb); background: var(--color-surface-alt, #f9fafb); }
.step-number { width: 2rem; height: 2rem; background: var(--color-primary, #1e3a5f); color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.9rem; flex-shrink: 0; }
.step-title { font-size: 1.05rem; font-weight: 600; margin: 0; color: var(--color-text-heading, #1e3a5f); }
.step-body { padding: 1.5rem; }

/* Unit Type Selector */
.unit-type-selector { display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; }
.unit-radio-card { flex: 1; min-width: 200px; cursor: pointer; }
.unit-radio-card input[type="radio"] { display: none; }
.unit-card-inner { border: 2px solid var(--color-border, #e5e7eb); border-radius: 10px; padding: 1.25rem; display: flex; flex-direction: column; align-items: center; gap: 0.4rem; text-align: center; transition: all 0.2s; }
.unit-radio-card input:checked + .unit-card-inner { border-color: var(--color-primary, #1e3a5f); background: #eff6ff; }
.unit-icon { font-size: 2rem; }
.unit-label { font-weight: 600; color: var(--color-text-heading, #1e3a5f); }
.unit-desc { font-size: 0.8rem; color: var(--color-text-muted, #6b7280); }

/* Template Download */
.template-download-section { display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; }
.download-btn { display: inline-flex; align-items: center; gap: 0.4rem; }

/* Drop Zone */
.drop-zone { border: 2px dashed var(--color-border, #d1d5db); border-radius: 10px; padding: 2.5rem 1.5rem; text-align: center; transition: all 0.2s; background: var(--color-surface-alt, #f9fafb); }
.drop-zone.drag-over { border-color: var(--color-primary, #1e3a5f); background: #eff6ff; }
.drop-zone-inner { display: flex; flex-direction: column; align-items: center; gap: 0.75rem; }
.drop-icon { font-size: 3rem; }
.drop-text { font-size: 1rem; color: var(--color-text-muted, #6b7280); margin: 0; }
.drop-hint { font-size: 0.8rem; color: var(--color-text-muted, #9ca3af); margin: 0; }
.file-selected-info { display: flex; align-items: center; gap: 0.75rem; justify-content: center; margin-top: 1rem; padding: 0.75rem 1rem; background: #eff6ff; border-radius: 8px; }
.file-icon { font-size: 1.5rem; }
.btn-clear { background: none; border: none; cursor: pointer; color: #ef4444; font-size: 1.1rem; padding: 0.2rem 0.4rem; border-radius: 4px; }
.btn-clear:hover { background: #fee2e2; }

/* Loading */
.loading-text { display: inline-flex; align-items: center; gap: 0.5rem; color: var(--color-text-muted, #6b7280); }
.spinner { width: 16px; height: 16px; border: 2px solid #e5e7eb; border-top-color: var(--color-primary, #1e3a5f); border-radius: 50%; animation: spin 0.7s linear infinite; display: inline-block; }
@keyframes spin { to { transform: rotate(360deg); } }

/* Preview Summary */
.preview-summary { display: flex; gap: 1rem; margin-bottom: 1.25rem; flex-wrap: wrap; }
.summary-badge { padding: 0.6rem 1.2rem; border-radius: 8px; font-weight: 600; font-size: 0.9rem; }
.summary-badge.total { background: #f3f4f6; color: #374151; }
.summary-badge.valid { background: #dcfce7; color: #15803d; }
.summary-badge.invalid { background: #fee2e2; color: #b91c1c; }
.summary-badge.success { background: #dcfce7; color: #15803d; }
.summary-badge.error { background: #fee2e2; color: #b91c1c; }
.summary-badge.skipped { background: #fef9c3; color: #854d0e; }

/* Table */
.table-scroll { overflow-x: auto; border-radius: 8px; border: 1px solid var(--color-border, #e5e7eb); }
.import-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
.import-table th { background: #f9fafb; padding: 0.75rem 1rem; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb; white-space: nowrap; }
.import-table td { padding: 0.75rem 1rem; border-bottom: 1px solid #f3f4f6; vertical-align: top; }
.import-table tr:last-child td { border-bottom: none; }
.import-table tr:hover td { background: #f9fafb; }
.col-row { width: 50px; text-align: center; }
.col-status { width: 90px; }

.status-badge { display: inline-flex; align-items: center; gap: 0.3rem; padding: 0.2rem 0.6rem; border-radius: 99px; font-size: 0.78rem; font-weight: 600; }
.status-badge.valid { background: #dcfce7; color: #15803d; }
.status-badge.invalid { background: #fee2e2; color: #b91c1c; }
.status-badge.success { background: #dcfce7; color: #15803d; }
.status-badge.error { background: #fee2e2; color: #b91c1c; }
.status-badge.skipped { background: #fef9c3; color: #854d0e; }

.error-list { list-style: none; margin: 0; padding: 0; }
.error-list li { color: #b91c1c; font-size: 0.8rem; line-height: 1.4; }
.error-list li::before { content: '• '; }

/* Execute button */
.import-execute-btn { font-size: 1rem; padding: 0.75rem 2rem; }
.button-link.success { background: #15803d; color: #fff; border: none; }
.button-link.success:hover { background: #166534; }

.form-alert { padding: 0.9rem 1.25rem; border-radius: 8px; margin-bottom: 1.25rem; font-size: 0.9rem; }
.form-alert.error { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }
.form-alert.success { background: #dcfce7; color: #15803d; border: 1px solid #86efac; }
.muted { color: var(--color-text-muted, #6b7280); font-size: 0.9rem; }
</style>
@endsection
