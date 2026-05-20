@extends('layouts.public')

@section('title', 'Katalog Publik - SIMPB')

@section('content')
    <section class="card">
        <div class="eyebrow">Public Catalog</div>
        <h1>Katalog Museum dan Perpustakaan</h1>
        <p>
            Cari koleksi perpustakaan dan museum yang sudah dipublikasikan.
            Data diambil dari endpoint <code>GET /api/catalog/search</code>.
        </p>
    </section>

    <section class="card">
        <h2>Pencarian</h2>

        <form id="catalog-search-form">
            <div class="catalog-form-grid">
                <div>
                    <label for="q">Keyword</label>
                    <input
                        id="q"
                        name="q"
                        type="search"
                        placeholder="Contoh: Brawijaya, Metadata, Trikora"
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
                    <label for="category_slug">Slug Kategori</label>
                    <input
                        id="category_slug"
                        name="category_slug"
                        type="text"
                        placeholder="buku, artefak-bersejarah"
                    >
                </div>

                <div>
                    <label for="year_from">Tahun dari</label>
                    <input
                        id="year_from"
                        name="year_from"
                        type="number"
                        placeholder="1940"
                    >
                </div>

                <div>
                    <label for="year_to">Tahun sampai</label>
                    <input
                        id="year_to"
                        name="year_to"
                        type="number"
                        placeholder="1965"
                    >
                </div>

                <div>
                    <label for="sort">Urutkan</label>
                    <select id="sort" name="sort">
                        <option value="relevance">Relevance</option>
                        <option value="latest">Terbaru</option>
                        <option value="oldest">Terlama</option>
                        <option value="title_asc">Judul A-Z</option>
                        <option value="title_desc">Judul Z-A</option>
                        <option value="year_asc">Tahun naik</option>
                        <option value="year_desc">Tahun turun</option>
                        <option value="featured">Featured</option>
                    </select>
                </div>

                <div>
                    <label for="per_page">Per halaman</label>
                    <select id="per_page" name="per_page">
                        <option value="5">5</option>
                        <option value="10" selected>10</option>
                        <option value="20">20</option>
                    </select>
                </div>
            </div>

            <div class="catalog-actions">
                <button type="submit">Cari</button>
                <button type="button" id="catalog-reset-button" class="secondary">Reset</button>
            </div>
        </form>
    </section>

    <section id="catalog-alert" class="card" style="display: none;"></section>

    <section id="catalog-loading" class="card">
        <h2>Memuat katalog...</h2>
        <p>Sedang mengambil data koleksi.</p>
    </section>

    <section id="catalog-content" style="display: none;">
        <div class="card">
            <h2>Hasil Pencarian</h2>
            <p id="catalog-summary">-</p>
        </div>

        <div id="catalog-results"></div>

        <div class="card">
            <div id="catalog-pagination" class="catalog-pagination"></div>
        </div>
    </section>

    <style>
        label {
            display: block;
            margin-bottom: 6px;
            font-size: 14px;
            font-weight: 700;
        }

        input,
        select {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 14px;
            background: white;
        }

        .catalog-form-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
        }

        .catalog-actions {
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

        button:disabled {
            opacity: .55;
            cursor: not-allowed;
        }

        .catalog-item {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 14px;
        }

        .catalog-item h2 {
            margin-bottom: 8px;
        }

        .catalog-meta {
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
            padding: 4px 9px;
            color: var(--muted);
            font-size: 12px;
        }

        .catalog-description {
            margin: 10px 0 14px;
        }

        .catalog-pagination {
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

        .alert-error {
            border-color: #fecaca;
            background: #fef2f2;
        }

        .alert-error h2,
        .alert-error p {
            color: #b91c1c;
        }

        @media (max-width: 920px) {
            .catalog-form-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 560px) {
            .catalog-form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection

@push('scripts')
    @vite('resources/js/pages/catalog/catalogIndex.js')
@endpush