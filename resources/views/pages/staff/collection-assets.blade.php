@extends('layouts.staff')

@section('title', 'Digital Asset Koleksi')

@section('content')
    <section class="card">
        <h2 id="asset-page-title">Digital Asset Koleksi</h2>
        <p>
            <a href="{{ route('staff.collections.show', ['identifier' => $identifier]) }}">
                ← Kembali ke detail internal
            </a>
        </p>
    </section>

    <section id="asset-root" data-identifier="{{ $identifier }}"></section>

    <section id="asset-alert" class="card asset-alert" style="display: none;"></section>

    <section id="asset-loading" class="card">
        <h2>Memuat digital asset...</h2>
        <p>Sedang mengambil data digital asset koleksi.</p>
    </section>

    <section id="asset-content" style="display: none;">
        <section class="card">
            <h2>Identitas Koleksi</h2>
            <div id="asset-collection-summary">-</div>
        </section>

        <section class="card">
            <h2>Upload File Fisik</h2>
            <p class="muted">Unggah file fisik baru sebagai digital asset dari koleksi ini.</p>
            <form id="asset-upload-form" enctype="multipart/form-data">
                <div class="asset-form-grid">
                    <div>
                        <label for="upload_file">File *</label>
                        <input
                            id="upload_file"
                            name="file"
                            type="file"
                            required
                        >
                        <div class="field-error" data-error-for="file"></div>
                    </div>

                    <div>
                        <label for="upload_asset_type">Asset Type</label>
                        <select id="upload_asset_type" name="asset_type">
                            <option value="cover">cover</option>
                            <option value="image">image</option>
                            <option value="pdf">pdf</option>
                            <option value="document">document</option>
                            <option value="audio">audio</option>
                            <option value="video">video</option>
                            <option value="other">other</option>
                        </select>
                        <div class="field-error" data-error-for="asset_type"></div>
                    </div>

                    <div>
                        <label for="upload_file_role">File Role</label>
                        <select id="upload_file_role" name="file_role">
                            <option value="access">access</option>
                            <option value="master">master</option>
                            <option value="thumbnail">thumbnail</option>
                            <option value="preservation">preservation</option>
                        </select>
                        <div class="field-error" data-error-for="file_role"></div>
                    </div>

                    <div>
                        <label for="upload_disk">Disk</label>
                        <select id="upload_disk" name="disk">
                            <option value="public">public</option>
                            <option value="local">local</option>
                        </select>
                        <div class="field-error" data-error-for="disk"></div>
                    </div>

                    <div>
                        <label for="upload_access_level">Access Level</label>
                        <select id="upload_access_level" name="access_level">
                            <option value="public">public</option>
                            <option value="internal" selected>internal</option>
                            <option value="restricted">restricted</option>
                        </select>
                        <div class="field-error" data-error-for="access_level"></div>
                    </div>

                    <div>
                        <label for="upload_sort_order">Sort Order</label>
                        <input
                            id="upload_sort_order"
                            name="sort_order"
                            type="number"
                            value="1"
                            min="0"
                        >
                        <div class="field-error" data-error-for="sort_order"></div>
                    </div>

                    <div class="full">
                        <label for="upload_caption">Caption</label>
                        <input
                            id="upload_caption"
                            name="caption"
                            type="text"
                            placeholder="Keterangan singkat tentang file"
                        >
                        <div class="field-error" data-error-for="caption"></div>
                    </div>

                    <div class="checkbox-group">
                        <label class="checkbox-label">
                            <input
                                id="upload_is_primary"
                                name="is_primary"
                                type="checkbox"
                                value="1"
                            >
                            Jadikan primary asset
                        </label>
                        <div class="field-error" data-error-for="is_primary"></div>
                    </div>

                    <div class="checkbox-group">
                        <label class="checkbox-label">
                            <input
                                id="upload_is_public"
                                name="is_public"
                                type="checkbox"
                                value="1"
                            >
                            Publikasikan asset ini
                        </label>
                        <div class="field-error" data-error-for="is_public"></div>
                    </div>

                    <div class="full">
                        <label for="upload_reason">Alasan Upload</label>
                        <input
                            id="upload_reason"
                            name="reason"
                            type="text"
                            value="Upload file digital asset melalui staff manager."
                            required
                        >
                        <div class="field-error" data-error-for="reason"></div>
                    </div>
                </div>

                <div class="asset-actions">
                    <button type="submit" id="upload-save-button">
                        Upload File
                    </button>
                </div>
            </form>
        </section>

        <section class="card">
            <h2>Register Metadata Asset (Tanpa Upload)</h2>
            <p class="muted">Daftarkan metadata asset yang filenya sudah ada di server secara manual.</p>
            <form id="asset-register-form">
                <div class="asset-form-grid">
                    <div class="full">
                        <label for="register_path">Path Berkas *</label>
                        <input
                            id="register_path"
                            name="path"
                            type="text"
                            placeholder="digital-assets/collection/file.pdf"
                            required
                        >
                        <div class="field-error" data-error-for="path"></div>
                    </div>

                    <div>
                        <label for="register_asset_type">Asset Type</label>
                        <select id="register_asset_type" name="asset_type">
                            <option value="cover">cover</option>
                            <option value="image">image</option>
                            <option value="pdf">pdf</option>
                            <option value="document">document</option>
                            <option value="audio">audio</option>
                            <option value="video">video</option>
                            <option value="other">other</option>
                        </select>
                        <div class="field-error" data-error-for="asset_type"></div>
                    </div>

                    <div>
                        <label for="register_file_role">File Role</label>
                        <select id="register_file_role" name="file_role">
                            <option value="access">access</option>
                            <option value="master">master</option>
                            <option value="thumbnail">thumbnail</option>
                            <option value="preservation">preservation</option>
                        </select>
                        <div class="field-error" data-error-for="file_role"></div>
                    </div>

                    <div>
                        <label for="register_disk">Disk</label>
                        <select id="register_disk" name="disk">
                            <option value="public" selected>public</option>
                            <option value="local">local</option>
                        </select>
                        <div class="field-error" data-error-for="disk"></div>
                    </div>

                    <div>
                        <label for="register_mime_type">MIME Type</label>
                        <input
                            id="register_mime_type"
                            name="mime_type"
                            type="text"
                            placeholder="e.g. application/pdf"
                        >
                        <div class="field-error" data-error-for="mime_type"></div>
                    </div>

                    <div>
                        <label for="register_extension">Ekstensi</label>
                        <input
                            id="register_extension"
                            name="extension"
                            type="text"
                            placeholder="e.g. pdf"
                        >
                        <div class="field-error" data-error-for="extension"></div>
                    </div>

                    <div>
                        <label for="register_access_level">Access Level</label>
                        <select id="register_access_level" name="access_level">
                            <option value="public">public</option>
                            <option value="internal" selected>internal</option>
                            <option value="restricted">restricted</option>
                        </select>
                        <div class="field-error" data-error-for="access_level"></div>
                    </div>

                    <div>
                        <label for="register_sort_order">Sort Order</label>
                        <input
                            id="register_sort_order"
                            name="sort_order"
                            type="number"
                            value="1"
                            min="0"
                        >
                        <div class="field-error" data-error-for="sort_order"></div>
                    </div>

                    <div class="full">
                        <label for="register_caption">Caption</label>
                        <input
                            id="register_caption"
                            name="caption"
                            type="text"
                            placeholder="Keterangan singkat tentang file"
                        >
                        <div class="field-error" data-error-for="caption"></div>
                    </div>

                    <div class="checkbox-group">
                        <label class="checkbox-label">
                            <input
                                id="register_is_primary"
                                name="is_primary"
                                type="checkbox"
                                value="1"
                            >
                            Jadikan primary asset
                        </label>
                        <div class="field-error" data-error-for="is_primary"></div>
                    </div>

                    <div class="checkbox-group">
                        <label class="checkbox-label">
                            <input
                                id="register_is_public"
                                name="is_public"
                                type="checkbox"
                                value="1"
                            >
                            Publikasikan asset ini
                        </label>
                        <div class="field-error" data-error-for="is_public"></div>
                    </div>

                    <div class="full">
                        <label for="register_reason">Alasan Registrasi</label>
                        <input
                            id="register_reason"
                            name="reason"
                            type="text"
                            value="Register metadata digital asset melalui staff manager."
                            required
                        >
                        <div class="field-error" data-error-for="reason"></div>
                    </div>
                </div>

                <div class="asset-actions">
                    <button type="submit" id="register-save-button">
                        Register Asset
                    </button>

                    <button type="button" id="asset-refresh-button" class="secondary">
                        Muat Ulang
                    </button>
                </div>
            </form>
        </section>

        <section class="card">
            <h2>Daftar Digital Asset</h2>
            <div id="asset-list">
                <p>Belum ada digital asset.</p>
            </div>
        </section>
    </section>

    <style>
        label {
            display: block;
            margin-bottom: 6px;
            font-size: 14px;
            font-weight: 700;
            color: var(--text);
        }

        input,
        select,
        textarea {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 14px;
            font-family: Arial, sans-serif;
            background: #ffffff;
        }

        input[type="checkbox"] {
            width: auto;
            margin-right: 8px;
        }

        input[aria-invalid="true"],
        select[aria-invalid="true"],
        textarea[aria-invalid="true"] {
            border-color: #dc2626;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            min-height: 40px;
        }

        .checkbox-label {
            display: inline-flex;
            align-items: center;
            font-weight: normal;
            cursor: pointer;
            margin-bottom: 0;
        }

        .field-error {
            min-height: 18px;
            margin-top: 6px;
            color: #dc2626;
            font-size: 13px;
        }

        .asset-form-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
        }

        .asset-form-grid .full {
            grid-column: 1 / -1;
        }

        .asset-actions {
            margin-top: 18px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        button,
        .button-link {
            border: 0;
            border-radius: 8px;
            padding: 10px 14px;
            background: var(--accent);
            color: white;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
        }

        button.secondary {
            background: #e2e8f0;
            color: var(--text);
        }

        button.danger {
            background: #dc2626;
        }

        button:disabled {
            opacity: .6;
            cursor: wait;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: 190px 1fr;
            gap: 8px 14px;
        }

        .summary-label {
            color: var(--muted);
            font-size: 14px;
        }

        .summary-value {
            color: var(--text);
            font-size: 14px;
            word-break: break-word;
        }

        .asset-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
            margin-top: 16px;
        }

        .asset-item {
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 18px;
            background: #ffffff;
            display: flex;
            flex-direction: column;
            gap: 14px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .asset-preview {
            display: flex;
            justify-content: center;
            align-items: center;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px solid var(--border);
            overflow: hidden;
            height: 180px;
        }

        .asset-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .badge-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            border: 1px solid var(--border);
            background: #f8fafc;
            border-radius: 999px;
            padding: 5px 10px;
            color: var(--muted);
            font-size: 13px;
        }

        .badge.primary {
            background: #eff6ff;
            border-color: #bfdbfe;
            color: #1d4ed8;
            font-weight: 600;
        }

        .badge.public {
            background: #ecfdf5;
            border-color: #bbf7d0;
            color: #047857;
        }

        .badge.private {
            background: #fef2f2;
            border-color: #fecaca;
            color: #b91c1c;
        }

        .asset-alert {
            padding: 16px;
            border-left: 4px solid transparent;
            margin-bottom: 20px;
        }

        .asset-alert.error {
            border-left-color: #dc2626;
            background: #fef2f2;
            color: #991b1b;
        }

        .asset-alert.success {
            border-left-color: #16a34a;
            background: #f0fdf4;
            color: #166534;
        }

        .asset-alert.warning {
            border-left-color: #d97706;
            background: #fffbeb;
            color: #92400e;
        }

        .asset-alert h2 {
            margin-top: 0;
            font-size: 16px;
            margin-bottom: 6px;
        }

        .asset-alert p {
            margin: 0;
            font-size: 14px;
            color: inherit;
        }

        .muted {
            color: var(--muted);
        }

        @media (max-width: 1100px) {
            .asset-form-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 720px) {
            .asset-form-grid,
            .summary-grid,
            .asset-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection

@push('scripts')
    @vite('resources/js/pages/staff/collectionAssets.js')
@endpush
