@extends('layouts.staff')

@section('title', 'Version History Koleksi')

@section('content')
    <section class="card">
        <h2>Version History Koleksi</h2>
        <p>
            Halaman ini mengambil data dari endpoint
            <code>GET /api/staff/collections/{{ $identifier }}/versions</code>.
        </p>
        <p>
            <a href="{{ route('staff.collections.show', ['identifier' => $identifier]) }}">
                ← Kembali ke detail internal
            </a>
        </p>
    </section>

    <section
        id="staff-version-root"
        data-identifier="{{ $identifier }}"
    ></section>

    <section id="staff-version-alert" class="card staff-version-alert" style="display: none;"></section>

    <section id="staff-version-loading" class="card">
        <h2>Memuat version history...</h2>
        <p>Sedang mengambil riwayat versi koleksi.</p>
    </section>

    <section id="staff-version-content" style="display: none;">
        <div class="card">
            <h2>Ringkasan</h2>
            <p id="staff-version-summary">-</p>

            <div class="version-actions">
                <button type="button" id="staff-version-refresh-button">
                    Muat Ulang
                </button>
            </div>
        </div>

        <div id="staff-version-list"></div>

        <div id="staff-version-pagination" class="card staff-pagination"></div>
    </section>

    <style>
        .version-actions {
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

        button:disabled {
            opacity: .6;
            cursor: wait;
        }

        .version-item {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 14px;
        }

        .version-item h2 {
            margin-bottom: 8px;
        }

        .version-meta {
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

        .badge.snapshot {
            background: #eff6ff;
            border-color: #bfdbfe;
            color: #1d4ed8;
        }

        .badge.create,
        .badge.created {
            background: #ecfdf5;
            border-color: #bbf7d0;
            color: #047857;
        }

        .badge.update,
        .badge.updated {
            background: #eff6ff;
            border-color: #bfdbfe;
            color: #1d4ed8;
        }

        .badge.archive,
        .badge.archived,
        .badge.delete,
        .badge.deleted {
            background: #fef2f2;
            border-color: #fecaca;
            color: #b91c1c;
        }

        .badge.restore,
        .badge.restored,
        .badge.publish,
        .badge.published {
            background: #f5f3ff;
            border-color: #ddd6fe;
            color: #7c3aed;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: 190px 1fr;
            gap: 8px 14px;
            margin-top: 12px;
        }

        .detail-label {
            color: var(--muted);
            font-size: 14px;
        }

        .detail-value {
            color: var(--text);
            font-size: 14px;
            word-break: break-word;
        }

        details {
            margin-top: 14px;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 12px;
            background: #f8fafc;
        }

        summary {
            cursor: pointer;
            font-weight: 700;
            color: var(--text);
        }

        pre {
            white-space: pre-wrap;
            word-break: break-word;
            overflow-x: auto;
            margin: 12px 0 0;
            font-size: 13px;
            color: var(--text);
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

        .staff-version-alert.error {
            border-color: #fecaca;
            background: #fef2f2;
        }

        .staff-version-alert.error h2,
        .staff-version-alert.error p {
            color: #b91c1c;
        }

        .muted {
            color: var(--muted);
        }

        @media (max-width: 720px) {
            .detail-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection

@push('scripts')
    @vite('resources/js/pages/staff/collectionVersions.js')
@endpush