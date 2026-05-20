@extends('layouts.member')

@section('title', 'Bookmark Saya')

@section('content')
    <section class="card">
        <h2>Bookmark Saya</h2>
        <p>
            Halaman ini mengambil data dari endpoint <code>GET /api/member/bookmarks</code>.
            Anda dapat mengubah folder/catatan, menghapus bookmark, atau membuka detail koleksi.
        </p>
    </section>

    <section id="bookmark-alert" class="card bookmark-alert" style="display: none;"></section>

    <section id="bookmark-loading" class="card">
        <h2>Memuat bookmark...</h2>
        <p>Sedang mengambil daftar bookmark Anda.</p>
    </section>

    <section id="bookmark-content" style="display: none;">
        <div class="card">
    <h2>Ringkasan</h2>
    <p id="bookmark-summary">-</p>

    <div class="bookmark-toolbar">
        <button type="button" id="bookmark-refresh-button">
            Muat Ulang
        </button>
    </div>
</div>

<div class="bookmark-layout">
    <aside class="bookmark-folder-panel">
        <h2>Folder</h2>
        <p class="muted">
            Folder dibuat otomatis dari isian <code>folder_name</code>.
        </p>

        <div id="bookmark-folder-list" class="bookmark-folder-list">
            <button type="button" class="folder-button active">
                Semua Bookmark
            </button>
        </div>
    </aside>

    <div class="bookmark-main-panel">
        <div id="bookmark-active-folder" class="card bookmark-active-folder">
            <h2>Semua Bookmark</h2>
            <p>Menampilkan semua bookmark Anda.</p>
        </div>

        <div id="bookmark-list"></div>
    </div>
</div>

        <div id="bookmark-pagination" class="card bookmark-pagination"></div>
    </section>

    <style>
        .bookmark-toolbar {
            margin-top: 14px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .bookmark-item {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 14px;
        }

        .bookmark-item h2 {
            margin-bottom: 8px;
        }

        .bookmark-meta {
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

        .bookmark-form {
            display: grid;
            grid-template-columns: 1fr;
            gap: 10px;
            margin-top: 14px;
        }

        .bookmark-form label {
            display: block;
            margin-bottom: 6px;
            font-size: 14px;
            font-weight: 700;
            color: var(--text);
        }

        .bookmark-form input,
        .bookmark-form textarea {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 14px;
            font-family: Arial, sans-serif;
            background: #ffffff;
        }

        .bookmark-actions {
            margin-top: 14px;
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

        button.danger {
            background: #dc2626;
        }

        button:disabled {
            opacity: .6;
            cursor: wait;
        }

        .bookmark-pagination {
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

        .bookmark-alert.error {
            border-color: #fecaca;
            background: #fef2f2;
        }

        .bookmark-alert.success {
            border-color: #bbf7d0;
            background: #ecfdf5;
        }

        .bookmark-alert.warning {
            border-color: #fde68a;
            background: #fffbeb;
        }

        .bookmark-alert.error h2,
        .bookmark-alert.error p {
            color: #b91c1c;
        }

        .bookmark-alert.success h2,
        .bookmark-alert.success p {
            color: #047857;
        }

        .bookmark-alert.warning h2,
        .bookmark-alert.warning p {
            color: #92400e;
        }

        .muted {
            color: var(--muted);
        }

                .bookmark-layout {
            display: grid;
            grid-template-columns: 260px 1fr;
            gap: 16px;
            align-items: start;
        }

        .bookmark-folder-panel {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 18px;
            position: sticky;
            top: 16px;
        }

        .bookmark-folder-panel h2 {
            margin-top: 0;
        }

        .bookmark-folder-list {
            display: grid;
            gap: 8px;
            margin-top: 14px;
        }

        .folder-button {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 9px 10px;
            background: #ffffff;
            color: var(--text);
            cursor: pointer;
            text-align: left;
            font-weight: 700;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
        }

        .folder-button.active {
            border-color: var(--accent);
            background: #ecfdf5;
            color: var(--accent);
        }

        .folder-count {
            display: inline-flex;
            min-width: 28px;
            justify-content: center;
            border-radius: 999px;
            padding: 2px 7px;
            background: #f1f5f9;
            color: var(--muted);
            font-size: 12px;
        }

        .folder-button.active .folder-count {
            background: #d1fae5;
            color: var(--accent);
        }

        .bookmark-main-panel {
            min-width: 0;
        }

        .bookmark-active-folder h2 {
            margin-bottom: 6px;
        }

        @media (max-width: 860px) {
            .bookmark-layout {
                grid-template-columns: 1fr;
            }

            .bookmark-folder-panel {
                position: static;
            }
        }
    </style>
@endsection

@push('scripts')
    @vite('resources/js/pages/member/bookmarksIndex.js')
@endpush