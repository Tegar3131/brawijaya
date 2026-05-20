@extends('layouts.staff')

@section('title', 'Detail Internal Koleksi')

@section('content')
    <section class="card">
        <h2 id="staff-collection-page-title">Memuat detail koleksi...</h2>
        <p>
            Halaman ini mengambil data dari endpoint
            <code>GET /api/staff/collections/{{ $identifier }}</code>.
        </p>
        <p>
            <a href="{{ route('staff.collections.index') }}">← Kembali ke koleksi internal</a>
        </p>
    </section>

    <section
        id="staff-collection-root"
        data-identifier="{{ $identifier }}"
    ></section>

    <section id="staff-collection-alert" class="card staff-detail-alert" style="display: none;"></section>

    <section id="staff-collection-loading" class="card">
        <h2>Memuat koleksi...</h2>
        <p>Sedang mengambil detail internal koleksi.</p>
    </section>

    <section id="staff-collection-content" style="display: none;">
        <div class="card" style="background: #e0f2fe; border-color: #bae6fd;">
            <p style="margin: 0; color: #0369a1; font-size: 14px;">
                <strong>Catatan:</strong> Data utama diedit melalui <strong style="color: #0c4a6e;">Edit Koleksi</strong>. Metadata dasar seperti dc.title, dc.creator, dan dc.subject akan tersinkron otomatis. Gunakan <em>Metadata Tambahan</em> hanya untuk metadata khusus yang tidak tersedia di form koleksi.
            </p>
        </div>

        <section class="card">
            <div id="staff-collection-hero"></div>

            <div class="staff-detail-actions">
                <a id="edit-collection-link" class="button-link" href="{{ route('staff.collections.edit', ['identifier' => $identifier]) }}">
                    Edit Koleksi
                </a>

                <a id="metadata-link" class="button-link secondary" href="{{ route('staff.collections.metadata', ['identifier' => $identifier]) }}">
                    Metadata Tambahan
                </a>

                <a id="creators-subjects-link" class="button-link secondary" href="{{ route('staff.collections.creators-subjects', ['identifier' => $identifier]) }}">
                    Creator & Subject
                </a>

                <a id="assets-link" class="button-link secondary" href="{{ route('staff.collections.assets', ['identifier' => $identifier]) }}">
                    Digital Assets
                </a>

                <a id="audit-link" class="button-link secondary" href="{{ route('staff.collections.audit', ['identifier' => $identifier]) }}">
                    Audit Log
                </a>

                <a id="versions-link" class="button-link secondary" href="{{ route('staff.collections.versions', ['identifier' => $identifier]) }}">
                    Version History
                </a>
            </div>

            <div class="staff-status-actions" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid var(--border); display: flex; gap: 10px; align-items: center;">
                <span class="muted" style="font-size: 13px;">Aksi Status:</span>
                <button type="button" id="publish-button" class="secondary" style="padding: 6px 10px; font-size: 13px;">Publish</button>
                <button type="button" id="archive-button" class="danger" style="padding: 6px 10px; font-size: 13px;">Archive</button>
                <button type="button" id="restore-button" class="secondary" style="padding: 6px 10px; font-size: 13px;">Restore</button>
            </div>
        </section>

        <section id="staff-library-section" class="card" style="display: none;">
            <h2>Detail Perpustakaan</h2>
            <div id="staff-library-detail"></div>
        </section>

        <section id="staff-museum-section" class="card" style="display: none;">
            <h2>Detail Museum</h2>
            <div id="staff-museum-detail"></div>
        </section>

        <section class="card">
            <h2>Creator</h2>
            <div id="staff-collection-creators">
                <p>Belum ada creator.</p>
            </div>
        </section>

        <section class="card">
            <h2>Subject</h2>
            <div id="staff-collection-subjects">
                <p>Belum ada subject.</p>
            </div>
        </section>

        <section class="card">
            <h2>Metadata Auto-Sync & Tambahan</h2>
            <div id="staff-collection-metadata">
                <p>Belum ada metadata.</p>
            </div>
        </section>

        <section class="card">
            <h2>Digital Assets (Ringkas)</h2>
            <div id="staff-collection-assets">
                <p>Belum ada digital asset.</p>
            </div>
        </section>
    </section>

    <style>
        .staff-detail-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 18px;
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

        .detail-grid {
            display: grid;
            grid-template-columns: 210px 1fr;
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
            word-break: break-word;
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
        .badge.archived,
        .badge.deleted {
            background: #fef2f2;
            border-color: #fecaca;
            color: #b91c1c;
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
            grid-template-columns: 240px 1fr;
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
            word-break: break-word;
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
            max-width: 260px;
            max-height: 180px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: #f8fafc;
        }

        .staff-detail-alert.error {
            border-color: #fecaca;
            background: #fef2f2;
        }

        .staff-detail-alert.success {
            border-color: #bbf7d0;
            background: #ecfdf5;
        }

        .staff-detail-alert.warning {
            border-color: #fde68a;
            background: #fffbeb;
        }

        .staff-detail-alert.error h2,
        .staff-detail-alert.error p {
            color: #b91c1c;
        }

        .staff-detail-alert.success h2,
        .staff-detail-alert.success p {
            color: #047857;
        }

        .staff-detail-alert.warning h2,
        .staff-detail-alert.warning p {
            color: #92400e;
        }

        .muted {
            color: var(--muted);
        }

        a {
            color: var(--accent);
        }

        @media (max-width: 760px) {
            .detail-grid,
            .metadata-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection

@push('scripts')
    @vite('resources/js/pages/staff/collectionShow.js')
@endpush