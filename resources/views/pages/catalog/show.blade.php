@extends('layouts.public')

@section('title', 'Detail Koleksi - SIMPB')

@section('content')
    <div style="margin-bottom: 24px;">
        <a href="{{ route('public.catalog') }}" style="display: inline-flex; align-items: center; gap: 8px; font-weight: 600; font-size: 13px; color: var(--muted); text-transform: uppercase; letter-spacing: 0.1em; text-decoration: none; transition: color 0.2s;">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Kembali ke Katalog
        </a>
    </div>

    <h1 id="collection-page-title" style="display: none;">Memuat detail koleksi...</h1>

    <section
        id="collection-detail-root"
        data-identifier="{{ $identifier }}"
    >
        <section id="collection-detail-alert" class="card" style="display: none;"></section>

        <section id="collection-detail-loading" class="card">
            <h2>Memuat koleksi...</h2>
            <p>Sedang mengambil data detail koleksi.</p>
        </section>

        <section id="collection-detail-content" style="display: none;" class="museum-layout">
            <div class="museum-main">
                <section class="card" style="margin-bottom: 0;">
                    <div class="collection-actions" style="margin-bottom: 24px; text-align: right;">
                        <a href="{{ route('public.collections.export.xml', ['identifier' => $identifier]) }}" class="btn-outline" style="border-color: var(--border); color: var(--text); background: white; padding: 8px 16px; border-radius: 4px; font-size: 13px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; border-width: 1px; border-style: solid; text-transform: uppercase; letter-spacing: 0.05em;">
                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            Download XML Metadata
                        </a>
                    </div>
                    <div id="collection-hero"></div>
                </section>

                <section class="card" id="museum-section" style="display: none; margin-bottom: 0;">
                    <h2>Detail Museum</h2>
                    <div id="museum-detail"></div>
                </section>

                <section class="card" id="library-section" style="display: none; margin-bottom: 0;">
                    <h2>Detail Perpustakaan</h2>
                    <div id="library-detail"></div>
                </section>

                <section class="card" style="margin-bottom: 0;">
                    <h2>Metadata Spesifik</h2>
                    <div id="collection-metadata"></div>
                </section>
            </div>

            <div class="museum-sidebar">
                <section class="card" style="margin-bottom: 0;">
                    <h3 style="font-size: 14px; margin-bottom: 16px; text-transform: uppercase; letter-spacing: 0.1em; color: var(--muted); font-family: var(--font-sans);">Media Assets</h3>
                    <div id="collection-assets"></div>
                </section>

                <section class="card" style="margin-bottom: 0;">
                    <h3 style="font-size: 14px; margin-bottom: 16px; text-transform: uppercase; letter-spacing: 0.1em; color: var(--muted); font-family: var(--font-sans);">Kreator</h3>
                    <div id="collection-creators"></div>
                </section>

                <section class="card" style="margin-bottom: 0;">
                    <h3 style="font-size: 14px; margin-bottom: 16px; text-transform: uppercase; letter-spacing: 0.1em; color: var(--muted); font-family: var(--font-sans);">Subjek</h3>
                    <div id="collection-subjects"></div>
                </section>

                <section class="card bookmark-widget" id="bookmark-widget" style="display: none; margin-bottom: 0;">
                    <h3 style="font-size: 14px; margin-bottom: 16px; text-transform: uppercase; letter-spacing: 0.1em; color: var(--muted); font-family: var(--font-sans);">Bookmark</h3>
                    
                    <p id="bookmark-status" class="muted" style="font-size: 14px; margin-bottom: 16px;"></p>
                    
                    <div style="margin-bottom: 12px;">
                        <label for="bookmark-folder">Folder / Kategori</label>
                        <input type="text" id="bookmark-folder" value="Koleksi favorit" placeholder="Misal: Referensi Sejarah">
                    </div>
                    
                    <div style="margin-bottom: 16px;">
                        <label for="bookmark-notes">Catatan Tambahan</label>
                        <textarea id="bookmark-notes" rows="3" placeholder="Opsional..."></textarea>
                    </div>
                    
                    <div class="bookmark-actions">
                        <button type="button" id="bookmark-toggle-button">
                            Tambah Bookmark
                        </button>
                    </div>
                </section>
            </div>
        </section>
    </section>

    <style>
        /* Museum Layout */
        .museum-layout {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 40px;
            align-items: start;
        }

        .museum-main {
            display: flex;
            flex-direction: column;
            gap: 32px;
        }
        
        .museum-sidebar {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 16px;
        }

        .detail-row {
            display: grid;
            grid-template-columns: 140px 1fr;
            gap: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border);
        }

        .detail-label {
            color: var(--muted);
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-family: var(--font-sans);
        }

        .detail-value {
            color: var(--text);
            font-size: 15px;
            font-family: var(--font-serif);
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
            background: var(--bg);
            border-radius: 4px;
            padding: 5px 12px;
            color: var(--muted);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
        }

        /* Metadata Tables */
        .metadata-group {
            margin-bottom: 32px;
        }

        .metadata-group h3 {
            margin: 0 0 16px;
            font-size: 18px;
            border-bottom: 2px solid var(--accent);
            padding-bottom: 8px;
            display: inline-block;
        }

        .metadata-row {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 24px;
            border-bottom: 1px solid var(--border);
            padding: 16px 0;
        }

        .metadata-label {
            color: var(--brand-dark);
            font-size: 14px;
            font-weight: 600;
        }
        
        .metadata-label small {
            color: var(--muted);
            font-weight: 400;
            font-family: monospace;
            font-size: 12px;
        }

        .metadata-value {
            color: var(--text);
            font-size: 15px;
            white-space: pre-wrap;
            font-family: var(--font-serif);
            line-height: 1.7;
        }

        /* Assets Gallery */
        .asset-list {
            display: grid;
            gap: 24px;
        }

        .asset-item {
            border: 1px solid var(--border);
            border-radius: 4px;
            padding: 24px;
            background: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.02);
        }

        .asset-preview {
            margin-bottom: 20px;
            background: var(--bg);
            padding: 20px;
            text-align: center;
            border: 1px solid var(--border);
        }

        .asset-preview img {
            max-width: 100%;
            max-height: 500px;
            object-fit: contain;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        /* Alerts */
        .alert-error {
            border-color: #fecaca;
            background: #fef2f2;
        }

        .alert-error h2,
        .alert-error p {
            color: #b91c1c;
        }

        /* Bookmarks */
        .bookmark-widget label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--muted);
        }

        .bookmark-widget input,
        .bookmark-widget textarea {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 4px;
            padding: 12px;
            font-size: 14px;
            margin-bottom: 16px;
            font-family: var(--font-sans);
            background: var(--bg);
        }

        .bookmark-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .bookmark-actions button {
            border: 0;
            border-radius: 4px;
            padding: 12px 20px;
            background: var(--brand-dark);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .bookmark-actions button:hover {
            background: var(--accent);
        }

        .bookmark-actions button.danger {
            background: white;
            color: #dc2626;
            border: 1px solid #dc2626;
        }
        
        .bookmark-actions button.danger:hover {
            background: #fef2f2;
        }

        .bookmark-actions button:disabled {
            opacity: .6;
            cursor: wait;
        }

        @media (max-width: 1024px) {
            .museum-layout {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 720px) {
            .detail-row,
            .metadata-row {
                grid-template-columns: 1fr;
                gap: 8px;
            }
        }
    </style>
@endsection

@push('scripts')
    @vite('resources/js/pages/catalog/catalogShow.js')
@endpush