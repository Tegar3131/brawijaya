@extends('layouts.public')

@section('title', 'Katalog Publik - SIMPB')

@section('content')
    <section class="card">
        <div class="eyebrow">Public Catalog</div>
        <h1 id="catalog-page-title">Katalog Museum dan Perpustakaan</h1>
        <p id="catalog-page-description">
            Jelajahi koleksi literatur sejarah dan artefak budaya yang telah didigitalisasi.
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
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--muted);
        }

        input,
        select {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 4px;
            padding: 12px 14px;
            font-size: 14px;
            background: white;
            transition: all 0.2s;
        }
        
        input:focus, select:focus {
            border-color: var(--accent);
            outline: none;
            box-shadow: 0 0 0 3px rgba(194, 155, 64, 0.1);
        }

        .catalog-form-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 20px;
        }

        .catalog-actions {
            margin-top: 24px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            padding-top: 24px;
            border-top: 1px solid var(--border);
        }

        button,
        .button-link {
            border: 0;
            border-radius: 4px;
            padding: 12px 24px;
            background: var(--brand-dark);
            color: white;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            transition: all 0.2s;
        }
        
        button:hover, .button-link:hover {
            background: var(--accent);
            color: white;
        }

        button.secondary {
            background: white;
            color: var(--brand-dark);
            border: 1px solid var(--border);
        }
        
        button.secondary:hover {
            background: var(--bg);
            border-color: var(--accent);
        }

        button:disabled {
            opacity: .55;
            cursor: not-allowed;
        }
        
        #catalog-results {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 24px;
            margin-bottom: 24px;
        }

        .catalog-item {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 4px;
            padding: 32px 24px;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .catalog-item:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.04);
            border-color: var(--accent);
        }

        .catalog-item h2 {
            margin-bottom: 12px;
            font-size: 22px;
        }

        .catalog-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin: 16px 0;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            border: 1px solid var(--border);
            background: var(--bg);
            border-radius: 4px;
            padding: 4px 10px;
            color: var(--muted);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
        }

        .catalog-description {
            margin: 0 0 24px;
            flex-grow: 1;
            font-size: 15px;
        }

        .catalog-pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            font-family: var(--font-sans);
            font-size: 14px;
        }

        .pagination-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .pagination-buttons button {
            padding: 8px 16px;
            background: white;
            color: var(--brand-dark);
            border: 1px solid var(--border);
        }
        
        .pagination-buttons button:hover:not(:disabled) {
            background: var(--bg);
            border-color: var(--accent);
            color: var(--brand-dark);
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