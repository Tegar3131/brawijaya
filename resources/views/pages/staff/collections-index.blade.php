@extends('layouts.staff')

@section('title', 'Koleksi Internal')

@section('content')
    <section class="card">
        <h2>Koleksi Internal</h2>
        <p>
            Halaman ini mengambil data dari endpoint <code>GET /api/staff/collections</code>.
            Data yang tampil mengikuti role login: admin, pustakawan, atau kurator.
        </p>
    </section>

    <section id="staff-collection-scope" class="card">
        <h2>Scope Akses</h2>
        <p id="staff-collection-scope-text">Memeriksa role pengguna...</p>
    </section>

    <section class="card">
        <h2>Filter Koleksi</h2>

        <form id="staff-collection-filter-form">
            <div class="staff-filter-grid">
                <div>
                    <label for="q">Keyword</label>
                    <input
                        id="q"
                        name="q"
                        type="search"
                        placeholder="Judul, record code, deskripsi"
                    >
                </div>

                <div>
                    <label for="unit_type">Unit</label>
                    <select id="unit_type" name="unit_type">
                        <option value="">Semua unit</option>
                        <option value="library">Perpustakaan</option>
                        <option value="museum">Museum</option>
                    </select>
                </div>

                <div>
                    <label for="collection_type">Tipe Koleksi</label>
                    <input
                        id="collection_type"
                        name="collection_type"
                        type="text"
                        placeholder="book, journal, artifact, photo"
                    >
                </div>

                <div>
                    <label for="publication_status">Status Publikasi</label>
                    <select id="publication_status" name="publication_status">
                        <option value="">Semua status</option>
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                        <option value="restricted">Restricted</option>
                        <option value="archived">Archived</option>
                    </select>
                </div>

                <div>
                    <label for="visibility">Visibility</label>
                    <select id="visibility" name="visibility">
                        <option value="">Semua visibility</option>
                        <option value="public">Public</option>
                        <option value="member">Member</option>
                        <option value="internal">Internal</option>
                        <option value="restricted">Restricted</option>
                    </select>
                </div>

                <div>
                    <label for="category_id">Category ID</label>
                    <input
                        id="category_id"
                        name="category_id"
                        type="number"
                        min="1"
                        placeholder="Opsional"
                    >
                </div>

                <div>
                    <label for="sort">Urutkan</label>
                    <select id="sort" name="sort">
                        <option value="latest">Terbaru</option>
                        <option value="oldest">Terlama</option>
                        <option value="title_asc">Judul A-Z</option>
                        <option value="title_desc">Judul Z-A</option>
                        <option value="year_asc">Tahun naik</option>
                        <option value="year_desc">Tahun turun</option>
                    </select>
                </div>

                <div>
                    <label for="per_page">Per halaman</label>
                    <select id="per_page" name="per_page">
                        <option value="10" selected>10</option>
                        <option value="20">20</option>
                        <option value="50">50</option>
                    </select>
                </div>

                <div class="checkbox-row">
                    <label>
                        <input id="include_trashed" name="include_trashed" type="checkbox" value="1">
                        Tampilkan archived/trashed jika diizinkan
                    </label>
                </div>
            </div>

            <div class="staff-filter-actions">
                <button type="submit">Terapkan Filter</button>
                <button type="button" id="staff-collection-reset-button" class="secondary">Reset</button>
                <a class="button-link secondary" href="{{ route('staff.library.create') }}">Tambah Library</a>
                <a class="button-link secondary" href="{{ route('staff.museum.create') }}">Tambah Museum</a>
            </div>
        </form>
    </section>

    <section id="staff-collection-alert" class="card staff-collection-alert" style="display: none;"></section>

    <section id="staff-collection-loading" class="card">
        <h2>Memuat koleksi internal...</h2>
        <p>Sedang mengambil data dari API.</p>
    </section>

    <section id="staff-collection-content" style="display: none;">
        <div class="card">
            <h2>Ringkasan</h2>
            <p id="staff-collection-summary">-</p>
        </div>

        <div id="staff-collection-list"></div>

        <div id="staff-collection-pagination" class="card staff-pagination"></div>
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

        input:disabled,
        select:disabled {
            background: #f1f5f9;
            color: var(--muted);
            cursor: not-allowed;
        }

        .staff-filter-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            align-items: end;
        }

        .checkbox-row {
            display: flex;
            align-items: center;
            min-height: 42px;
        }

        .checkbox-row label {
            display: flex;
            gap: 8px;
            align-items: center;
            font-weight: 400;
            color: var(--muted);
        }

        .checkbox-row input {
            width: auto;
        }

        .staff-filter-actions {
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

        button.secondary,
        .button-link.secondary {
            background: #e2e8f0;
            color: var(--text);
        }

        button:disabled {
            opacity: .6;
            cursor: wait;
        }

        .collection-item {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 14px;
        }

        .collection-item.archived,
        .collection-item.deleted {
            border-color: #fecaca;
            background: #fffafa;
        }

        .collection-item h2 {
            margin-bottom: 8px;
        }

        .collection-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin: 10px 0 14px;
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

        .badge.library {
            background: #eff6ff;
            border-color: #bfdbfe;
            color: #1d4ed8;
        }

        .badge.museum {
            background: #f5f3ff;
            border-color: #ddd6fe;
            color: #7c3aed;
        }

        .badge.published {
            background: #ecfdf5;
            border-color: #bbf7d0;
            color: #047857;
        }

        .badge.draft {
            background: #fffbeb;
            border-color: #fde68a;
            color: #92400e;
        }

        .badge.restricted,
        .badge.archived {
            background: #fef2f2;
            border-color: #fecaca;
            color: #b91c1c;
        }

        .collection-description {
            color: var(--muted);
            line-height: 1.6;
            margin: 10px 0 14px;
        }

        .collection-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .staff-pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .pagination-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .staff-collection-alert.error {
            border-color: #fecaca;
            background: #fef2f2;
        }

        .staff-collection-alert.error h2,
        .staff-collection-alert.error p {
            color: #b91c1c;
        }

        .staff-collection-alert.warning {
            border-color: #fde68a;
            background: #fffbeb;
        }

        .staff-collection-alert.warning h2,
        .staff-collection-alert.warning p {
            color: #92400e;
        }

        .muted {
            color: var(--muted);
        }

        @media (max-width: 1100px) {
            .staff-filter-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .staff-filter-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection

@push('scripts')
    @vite('resources/js/pages/staff/collectionsIndex.js')
@endpush