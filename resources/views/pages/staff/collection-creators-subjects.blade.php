@extends('layouts.staff')

@section('title', 'Creator dan Subject Koleksi')

@section('content')
    <section class="card">
        <h2 id="cs-page-title">Creator dan Subject Koleksi</h2>
        <p>
            Halaman ini memakai endpoint creator dan subject untuk mengelola relasi deskriptif koleksi.
        </p>
        <p>
            <a href="{{ route('staff.collections.show', ['identifier' => $identifier]) }}">
                ← Kembali ke detail internal
            </a>
        </p>
    </section>

    <section
        id="cs-root"
        data-identifier="{{ $identifier }}"
    ></section>

    <section id="cs-alert" class="card cs-alert" style="display: none;"></section>

    <section id="cs-loading" class="card">
        <h2>Memuat creator dan subject...</h2>
        <p>Sedang mengambil data koleksi.</p>
    </section>

    <section id="cs-content" style="display: none;">
        <section class="card">
            <h2>Identitas Koleksi</h2>
            <div id="cs-collection-summary">-</div>
        </section>

        <section class="card">
            <h2>Tambah Creator</h2>

            <form id="creator-form">
                <div class="cs-form-grid">
                    <div class="full">
                        <label for="creator_lookup">Cari Creator Existing</label>
                        <div class="lookup-panel">
                            <input id="creator_lookup" type="search" placeholder="Ketik nama atau uri creator...">
                            <button type="button" id="creator-search-button">Cari</button>
                            <button type="button" id="creator-new-button" class="secondary">Buat Creator Baru</button>
                        </div>
                        <div id="creator-search-results" class="lookup-results" style="display: none;"></div>
                        <div id="creator-selected" class="cs-alert success" style="display: none; margin-top: 10px; padding: 10px;"></div>
                        <input id="creator_id" name="creator_id" type="hidden">
                        <div class="field-error" data-error-for="creator_id"></div>
                    </div>

                    <div>
                        <label for="creator_name">Nama Creator Baru / Terpilih</label>
                        <input id="creator_name" name="name" type="text" placeholder="Contoh: Nugroho Adi" required>
                        <div class="field-error" data-error-for="name"></div>
                    </div>

                    <div>
                        <label for="creator_type">Tipe Creator</label>
                        <select id="creator_type" name="creator_type">
                            <option value="person">Person</option>
                            <option value="organization">Organization</option>
                            <option value="family">Family</option>
                            <option value="unknown">Unknown</option>
                        </select>
                        <div class="field-error" data-error-for="creator_type"></div>
                    </div>

                    <div>
                        <label for="creator_role">Role</label>
                        <select id="creator_role" name="role">
                            <option value="author">Author</option>
                            <option value="creator">Creator</option>
                            <option value="editor">Editor</option>
                            <option value="contributor">Contributor</option>
                            <option value="photographer">Photographer</option>
                            <option value="maker">Maker</option>
                            <option value="collector">Collector</option>
                            <option value="curator">Curator</option>
                        </select>
                        <div class="field-error" data-error-for="role"></div>
                    </div>

                    <div>
                        <label for="creator_sort_order">Sort Order</label>
                        <input id="creator_sort_order" name="sort_order" type="number" min="0" value="1">
                        <div class="field-error" data-error-for="sort_order"></div>
                    </div>

                    <div class="checkbox-row">
                        <label>
                            <input id="creator_is_primary" name="is_primary" type="checkbox" value="1">
                            Creator utama
                        </label>
                    </div>

                    <div class="full">
                        <label for="creator_reason">Alasan Perubahan</label>
                        <input
                            id="creator_reason"
                            name="reason"
                            type="text"
                            value="Menambahkan creator melalui editor staff."
                            required
                        >
                        <div class="field-error" data-error-for="reason"></div>
                    </div>
                </div>

                <div class="cs-actions">
                    <button type="submit" id="creator-save-button">
                        Tambah Creator
                    </button>
                    <button type="button" id="creator-reset-button" class="secondary">
                        Reset Form
                    </button>
                </div>
            </form>
        </section>

        <section class="card">
            <h2>Tambah Subject</h2>

            <form id="subject-form">
                <div class="cs-form-grid">
                    <div class="full">
                        <label for="subject_lookup">Cari Subject Existing</label>
                        <div class="lookup-panel">
                            <input id="subject_lookup" type="search" placeholder="Ketik term subject...">
                            <button type="button" id="subject-search-button">Cari</button>
                            <button type="button" id="subject-new-button" class="secondary">Buat Subject Baru</button>
                        </div>
                        <div id="subject-search-results" class="lookup-results" style="display: none;"></div>
                        <div id="subject-selected" class="cs-alert success" style="display: none; margin-top: 10px; padding: 10px;"></div>
                        <input id="subject_id" name="subject_id" type="hidden">
                        <div class="field-error" data-error-for="subject_id"></div>
                    </div>

                    <div>
                        <label for="subject_term">Term Subject Baru / Terpilih</label>
                        <input id="subject_term" name="term" type="text" placeholder="Contoh: Sejarah Brawijaya" required>
                        <div class="field-error" data-error-for="term"></div>
                    </div>

                    <div>
                        <label for="subject_type">Tipe Subject</label>
                        <select id="subject_type" name="subject_type">
                            <option value="topic">Topic</option>
                            <option value="person">Person</option>
                            <option value="corporate">Corporate</option>
                            <option value="geographic">Geographic</option>
                            <option value="temporal">Temporal</option>
                            <option value="genre">Genre</option>
                            <option value="event">Event</option>
                        </select>
                        <div class="field-error" data-error-for="subject_type"></div>
                    </div>

                    <div>
                        <label for="subject_authority_source">Authority Source</label>
                        <input
                            id="subject_authority_source"
                            name="authority_source"
                            type="text"
                            placeholder="LCSH, TGM, Lokal SIMPB"
                            value="Lokal SIMPB"
                        >
                        <div class="field-error" data-error-for="authority_source"></div>
                    </div>

                    <div>
                        <label for="subject_sort_order">Sort Order</label>
                        <input id="subject_sort_order" name="sort_order" type="number" min="0" value="1">
                        <div class="field-error" data-error-for="sort_order"></div>
                    </div>

                    <div class="full">
                        <label for="subject_reason">Alasan Perubahan</label>
                        <input
                            id="subject_reason"
                            name="reason"
                            type="text"
                            value="Menambahkan subject melalui editor staff."
                            required
                        >
                        <div class="field-error" data-error-for="reason"></div>
                    </div>
                </div>

                <div class="cs-actions">
                    <button type="submit" id="subject-save-button">
                        Tambah Subject
                    </button>
                    <button type="button" id="subject-reset-button" class="secondary">
                        Reset Form
                    </button>
                    <button type="button" id="cs-refresh-button" class="secondary">
                        Muat Ulang
                    </button>
                </div>
            </form>
        </section>

        <section class="card">
            <h2>Creator Saat Ini</h2>
            <div id="creator-list">
                <p>Belum ada creator.</p>
            </div>
        </section>

        <section class="card">
            <h2>Subject Saat Ini</h2>
            <div id="subject-list">
                <p>Belum ada subject.</p>
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
        select {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 14px;
            background: #ffffff;
        }

        input[aria-invalid="true"],
        select[aria-invalid="true"] {
            border-color: #dc2626;
        }

        .field-error {
            min-height: 18px;
            margin-top: 6px;
            color: #dc2626;
            font-size: 13px;
        }

        .cs-form-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
            align-items: end;
        }

        .cs-form-grid .full {
            grid-column: 1 / -1;
        }

        .checkbox-row {
            display: flex;
            align-items: center;
            min-height: 42px;
        }

        .checkbox-row label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 400;
            color: var(--muted);
        }

        .checkbox-row input {
            width: auto;
        }

        .cs-actions {
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

        .relation-item {
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 14px;
            margin-bottom: 10px;
            background: #ffffff;
        }

        .relation-item h3 {
            margin: 0 0 8px;
        }

        .badge-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin: 10px 0;
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

        .relation-actions {
            margin-top: 12px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .cs-alert.error {
            border-color: #fecaca;
            background: #fef2f2;
        }

        .cs-alert.success {
            border-color: #bbf7d0;
            background: #ecfdf5;
        }

        .cs-alert.warning {
            border-color: #fde68a;
            background: #fffbeb;
        }

        .cs-alert.error h2,
        .cs-alert.error p {
            color: #b91c1c;
        }

        .cs-alert.success h2,
        .cs-alert.success p {
            color: #047857;
        }

        .cs-alert.warning h2,
        .cs-alert.warning p {
            color: #92400e;
        }

        .lookup-panel {
            display: flex;
            gap: 10px;
        }

        .lookup-panel input {
            flex: 1;
        }

        .lookup-results {
            margin-top: 10px;
            border: 1px solid var(--border);
            border-radius: 8px;
            overflow: hidden;
            background: #fff;
        }

        .lookup-result {
            padding: 10px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .lookup-result:last-child {
            border-bottom: none;
        }

        .lookup-result-title {
            font-weight: bold;
            font-size: 14px;
            color: var(--text);
        }

        .lookup-result-meta {
            font-size: 13px;
            color: var(--muted);
            margin-top: 4px;
        }

        .muted {
            color: var(--muted);
        }

        @media (max-width: 980px) {
            .cs-form-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 720px) {
            .cs-form-grid,
            .summary-grid {
                grid-template-columns: 1fr;
            }
            .lookup-panel {
                flex-direction: column;
            }
            .lookup-result {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
    </style>
@endsection

@push('scripts')
    @vite('resources/js/pages/staff/collectionCreatorsSubjects.js')
@endpush