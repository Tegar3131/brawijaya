@extends('layouts.public')

@section('title', 'Detail Koleksi - SIMPB')

@section('content')
    <section class="card">
        <div class="eyebrow">Detail Koleksi</div>
        <h1 id="collection-page-title">Memuat detail koleksi...</h1>
        <p>
            Data diambil dari endpoint
            <code>GET /api/catalog/collections/{{ $identifier }}</code>.
        </p>
        <p>
            <a href="{{ route('public.catalog') }}">← Kembali ke katalog</a>
        </p>
    </section>

    <section
        id="collection-detail-root"
        data-identifier="{{ $identifier }}"
    ></section>

    <section id="collection-detail-alert" class="card" style="display: none;"></section>

    <section id="collection-detail-loading" class="card">
        <h2>Memuat koleksi...</h2>
        <p>Sedang mengambil data detail koleksi.</p>
    </section>

    <section id="collection-detail-content" style="display: none;">
    <section class="card">
        <div id="collection-hero"></div>
    </section>

    <section id="bookmark-widget" class="card bookmark-widget" style="display: none;">
        <h2>Bookmark</h2>

        <p id="bookmark-status">
            Memeriksa status bookmark...
        </p>

        <div class="bookmark-form">
            <label for="bookmark-folder">Folder</label>
            <input
                id="bookmark-folder"
                type="text"
                placeholder="Contoh: Favorit, Riset, Bacaan nanti"
                value="Koleksi favorit"
            >

            <label for="bookmark-notes">Catatan</label>
            <textarea
                id="bookmark-notes"
                rows="3"
                placeholder="Catatan pribadi untuk koleksi ini"
            ></textarea>
        </div>

        <div class="bookmark-actions">
            <button type="button" id="bookmark-toggle-button">
                Tambah Bookmark
            </button>
        </div>
    </section>

        <section class="card">
            <h2>Creator</h2>
            <div id="collection-creators">
                <p>Belum ada creator.</p>
            </div>
        </section>

        <section class="card">
            <h2>Subject</h2>
            <div id="collection-subjects">
                <p>Belum ada subject.</p>
            </div>
        </section>

        <section id="library-section" class="card" style="display: none;">
            <h2>Detail Perpustakaan</h2>
            <div id="library-detail"></div>
        </section>

        <section id="museum-section" class="card" style="display: none;">
            <h2>Detail Museum</h2>
            <div id="museum-detail"></div>
        </section>

        <section class="card">
            <h2>Metadata</h2>
            <div id="collection-metadata">
                <p>Belum ada metadata.</p>
            </div>
        </section>

        <section class="card">
            <h2>Digital Asset</h2>
            <div id="collection-assets">
                <p>Belum ada digital asset publik.</p>
            </div>
        </section>
    </section>

    <style>
        .detail-grid {
            display: grid;
            grid-template-columns: 180px 1fr;
            gap: 10px 16px;
            align-items: start;
        }

        .detail-label {
            color: var(--muted);
            font-size: 14px;
        }

        .detail-value {
            color: var(--text);
            font-size: 14px;
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

        .metadata-group {
            margin-bottom: 18px;
        }

        .metadata-group h3 {
            margin: 0 0 10px;
            font-size: 16px;
        }

        .metadata-row {
            display: grid;
            grid-template-columns: 220px 1fr;
            gap: 12px;
            border-top: 1px solid var(--border);
            padding: 10px 0;
        }

        .metadata-label {
            color: var(--muted);
            font-size: 14px;
        }

        .metadata-value {
            color: var(--text);
            font-size: 14px;
            white-space: pre-wrap;
        }

        .asset-list {
            display: grid;
            gap: 14px;
        }

        .asset-item {
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 14px;
            background: #ffffff;
        }

        .asset-preview {
            margin-bottom: 12px;
        }

        .asset-preview img {
            max-width: 240px;
            max-height: 160px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: #f8fafc;
        }

        .alert-error {
            border-color: #fecaca;
            background: #fef2f2;
        }

        .alert-error h2,
        .alert-error p {
            color: #b91c1c;
        }

        .muted {
            color: var(--muted);
        }

        a {
            color: var(--accent);
        }

                .bookmark-widget label {
            display: block;
            margin-bottom: 6px;
            font-size: 14px;
            font-weight: 700;
        }

        .bookmark-widget input,
        .bookmark-widget textarea {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 14px;
            margin-bottom: 12px;
            font-family: Arial, sans-serif;
        }

        .bookmark-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .bookmark-actions button {
            border: 0;
            border-radius: 8px;
            padding: 10px 14px;
            background: var(--accent);
            color: white;
            font-weight: 700;
            cursor: pointer;
        }

        .bookmark-actions button.danger {
            background: #dc2626;
        }

        .bookmark-actions button:disabled {
            opacity: .6;
            cursor: wait;
        }

        @media (max-width: 720px) {
            .detail-grid,
            .metadata-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection

@push('scripts')
    @vite('resources/js/pages/catalog/catalogShow.js')
@endpush