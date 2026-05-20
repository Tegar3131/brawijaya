@extends('layouts.staff')

@section('title', 'Editor Metadata Koleksi')

@section('content')
    <section class="card">
        <h2 id="metadata-page-title">Editor Metadata Koleksi</h2>
        <p>
            Halaman ini memakai endpoint:
            <code>GET /api/staff/collections/{{ $identifier }}</code>,
            <code>PATCH /api/staff/collections/{{ $identifier }}/metadata</code>,
            dan
            <code>DELETE /api/staff/collections/{{ $identifier }}/metadata/{metadata}</code>.
        </p>
        <p>
            <a href="{{ route('staff.collections.show', ['identifier' => $identifier]) }}">
                ← Kembali ke detail internal
            </a>
        </p>
    </section>

    <section
        id="metadata-root"
        data-identifier="{{ $identifier }}"
    ></section>

    <section id="metadata-alert" class="card metadata-alert" style="display: none;"></section>

    <section id="metadata-loading" class="card">
        <h2>Memuat metadata...</h2>
        <p>Sedang mengambil metadata koleksi.</p>
    </section>

    <section id="metadata-content" style="display: none;">
        <section class="card">
            <h2>Identitas Koleksi</h2>
            <div id="metadata-collection-summary">-</div>
        </section>

        <section class="card">
            <h2>Tambah / Update Metadata</h2>
            <p class="muted">
                Untuk metadata repeatable, gunakan <code>sort_order</code> berbeda.
                Jika backend menemukan kombinasi yang sama, data dapat di-update sesuai aturan service.
            </p>

            <form id="metadata-form">
                <div class="metadata-form-grid">
                    <div>
                        <label for="element_key_select">Element Key Umum</label>
                        <select id="element_key_select">
                            <option value="">Pilih element key umum</option>

                            <optgroup label="Dublin Core">
                                <option value="dc.title">dc.title</option>
                                <option value="dc.creator">dc.creator</option>
                                <option value="dc.subject">dc.subject</option>
                                <option value="dc.description">dc.description</option>
                                <option value="dc.publisher">dc.publisher</option>
                                <option value="dc.contributor">dc.contributor</option>
                                <option value="dc.date">dc.date</option>
                                <option value="dc.type">dc.type</option>
                                <option value="dc.format">dc.format</option>
                                <option value="dc.identifier">dc.identifier</option>
                                <option value="dc.source">dc.source</option>
                                <option value="dc.language">dc.language</option>
                                <option value="dc.relation">dc.relation</option>
                                <option value="dc.coverage">dc.coverage</option>
                                <option value="dc.rights">dc.rights</option>
                            </optgroup>

                            <optgroup label="MARC21">
                                <option value="marc.020.a">marc.020.a — ISBN</option>
                                <option value="marc.022.a">marc.022.a — ISSN</option>
                                <option value="marc.082.a">marc.082.a — DDC</option>
                                <option value="marc.100.a">marc.100.a — Main Author</option>
                                <option value="marc.245.a">marc.245.a — Title Statement</option>
                                <option value="marc.245.b">marc.245.b — Remainder of Title</option>
                                <option value="marc.260.a">marc.260.a — Place</option>
                                <option value="marc.260.b">marc.260.b — Publisher</option>
                                <option value="marc.260.c">marc.260.c — Date</option>
                                <option value="marc.300.a">marc.300.a — Extent</option>
                                <option value="marc.650.a">marc.650.a — Subject</option>
                                <option value="marc.700.a">marc.700.a — Added Author</option>
                            </optgroup>

                            <optgroup label="CDWA Lite">
                                <option value="cdwa.title">cdwa.title</option>
                                <option value="cdwa.object.work.type">cdwa.object.work.type</option>
                                <option value="cdwa.classification">cdwa.classification</option>
                                <option value="cdwa.creator">cdwa.creator</option>
                                <option value="cdwa.creation.date">cdwa.creation.date</option>
                                <option value="cdwa.material.medium">cdwa.material.medium</option>
                                <option value="cdwa.measurements">cdwa.measurements</option>
                                <option value="cdwa.subject">cdwa.subject</option>
                                <option value="cdwa.description">cdwa.description</option>
                                <option value="cdwa.location">cdwa.location</option>
                            </optgroup>

                            <optgroup label="VRA Core">
                                <option value="vra.title">vra.title</option>
                                <option value="vra.work_type">vra.work_type</option>
                                <option value="vra.agent">vra.agent</option>
                                <option value="vra.date">vra.date</option>
                                <option value="vra.location">vra.location</option>
                                <option value="vra.material">vra.material</option>
                                <option value="vra.measurements">vra.measurements</option>
                                <option value="vra.description">vra.description</option>
                                <option value="vra.rights">vra.rights</option>
                            </optgroup>

                            <optgroup label="ISAD(G)">
                                <option value="isad.reference_code">isad.reference_code</option>
                                <option value="isad.title">isad.title</option>
                                <option value="isad.date">isad.date</option>
                                <option value="isad.level_of_description">isad.level_of_description</option>
                                <option value="isad.extent">isad.extent</option>
                                <option value="isad.creator">isad.creator</option>
                                <option value="isad.scope_content">isad.scope_content</option>
                                <option value="isad.conditions_access">isad.conditions_access</option>
                                <option value="isad.language_scripts">isad.language_scripts</option>
                                <option value="isad.finding_aids">isad.finding_aids</option>
                                <option value="isad.notes">isad.notes</option>
                            </optgroup>
                        </select>
                    </div>

                    <div>
                        <label for="element_key">Element Key</label>
                        <input
                            id="element_key"
                            name="element_key"
                            type="text"
                            placeholder="Contoh: dc.title"
                            required
                        >
                        <div class="field-error" data-error-for="metadata.0.element_key"></div>
                        <div class="field-error" data-error-for="element_key"></div>
                    </div>

                   <div>
    <label for="value_type">Jenis Nilai</label>
    <select id="value_type" name="value_type" required>
        <option value="auto">Otomatis dari elemen metadata</option>
        <option value="short_text">Teks pendek</option>
        <option value="long_text">Teks panjang</option>
        <option value="integer">Angka bulat</option>
        <option value="decimal">Angka desimal</option>
        <option value="date">Tanggal</option>
        <option value="datetime">Tanggal dan waktu</option>
        <option value="json">Data terstruktur / JSON</option>
    </select>

    <p class="metadata-help">
        Sistem akan memilih jenis nilai otomatis berdasarkan element key.
        Ubah hanya jika diperlukan.
    </p>

    <div class="field-error" data-error-for="metadata.0.value_column"></div>
    <div class="field-error" data-error-for="value_column"></div>
    <div class="field-error" data-error-for="value_type"></div>
</div>

                    <div>
                        <label for="sort_order">Sort Order</label>
                        <input
                            id="sort_order"
                            name="sort_order"
                            type="number"
                            value="1"
                            min="0"
                        >
                        <div class="field-error" data-error-for="metadata.0.sort_order"></div>
                        <div class="field-error" data-error-for="sort_order"></div>
                    </div>

                    <div class="full">
                        <label for="value">Nilai Metadata</label>
                        <textarea
                            id="value"
                            name="value"
                            rows="4"
                            placeholder="Isi nilai metadata"
                            required
                        ></textarea>
                        <div class="field-error" data-error-for="metadata.0.value"></div>
                        <div class="field-error" data-error-for="value"></div>
                    </div>

                    <div>
                        <label for="source">Source</label>
                        <input
                            id="source"
                            name="source"
                            type="text"
                            value="staff_ui"
                        >
                    </div>

                    <div>
                        <label for="language_code">Language Code</label>
                        <input
                            id="language_code"
                            name="language_code"
                            type="text"
                            placeholder="ind, eng"
                        >
                    </div>

                    <div class="full">
                        <label for="reason">Alasan Perubahan</label>
                        <input
                            id="reason"
                            name="reason"
                            type="text"
                            value="Update metadata melalui editor staff."
                            required
                        >
                        <div class="field-error" data-error-for="reason"></div>
                    </div>
                </div>

                <div class="metadata-actions">
                    <button type="submit" id="metadata-save-button">
                        Simpan Metadata
                    </button>

                    <button type="button" id="metadata-reset-form-button" class="secondary">
                        Reset Form
                    </button>

                    <button type="button" id="metadata-refresh-button" class="secondary">
                        Muat Ulang
                    </button>
                </div>
            </form>
        </section>

        <section class="card">
            <h2>Metadata Saat Ini</h2>
            <div id="metadata-list">
                <p>Belum ada metadata.</p>
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

        input[aria-invalid="true"],
        select[aria-invalid="true"],
        textarea[aria-invalid="true"] {
            border-color: #dc2626;
        }

        .field-error {
            min-height: 18px;
            margin-top: 6px;
            color: #dc2626;
            font-size: 13px;
        }

        .metadata-form-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
        }

        .metadata-form-grid .full {
            grid-column: 1 / -1;
        }

        .metadata-actions {
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

        .summary-label,
        .metadata-label {
            color: var(--muted);
            font-size: 14px;
        }

        .summary-value,
        .metadata-value {
            color: var(--text);
            font-size: 14px;
            word-break: break-word;
        }

        .metadata-group {
            margin-bottom: 18px;
        }

        .metadata-group h3 {
            margin: 0 0 10px;
            font-size: 16px;
        }

        .metadata-row {
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 14px;
            margin-bottom: 10px;
            background: #ffffff;
        }

        .metadata-row-grid {
            display: grid;
            grid-template-columns: 220px 1fr;
            gap: 10px 14px;
        }

        .metadata-row-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 14px;
        }

        .badge-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 12px;
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

        .metadata-alert.error {
            border-color: #fecaca;
            background: #fef2f2;
        }

        .metadata-alert.success {
            border-color: #bbf7d0;
            background: #ecfdf5;
        }

        .metadata-alert.warning {
            border-color: #fde68a;
            background: #fffbeb;
        }

        .metadata-alert.error h2,
        .metadata-alert.error p {
            color: #b91c1c;
        }

        .metadata-alert.success h2,
        .metadata-alert.success p {
            color: #047857;
        }

        .metadata-alert.warning h2,
        .metadata-alert.warning p {
            color: #92400e;
        }

        .muted {
            color: var(--muted);
        }

        code {
            font-size: 12px;
        }

        @media (max-width: 1100px) {
            .metadata-form-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 720px) {
            .metadata-form-grid,
            .summary-grid,
            .metadata-row-grid {
                grid-template-columns: 1fr;
            }
        }

        .metadata-help {
            margin: 6px 0 0;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.5;
        }
    </style>
@endsection

@push('scripts')
    @vite('resources/js/pages/staff/collectionMetadata.js')
@endpush