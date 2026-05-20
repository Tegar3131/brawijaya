@extends('layouts.member')

@section('title', 'Reservasi Saya')

@section('content')
    <section class="card">
        <h2>Reservasi Saya</h2>
        <p>
            Halaman ini mengambil data dari endpoint <code>GET /api/member/reservations</code>.
            Member dapat membuat reservasi koleksi perpustakaan memakai <code>record_code</code>.
        </p>
    </section>

    <section id="reservation-alert" class="card reservation-alert" style="display: none;"></section>

    <section class="card">
        <h2>Buat Reservasi</h2>

        <form id="reservation-create-form">
            <div class="reservation-form-grid">
                <div>
                    <label for="record_code">Record Code Koleksi</label>
                    <input
                        id="record_code"
                        name="record_code"
                        type="text"
                        placeholder="Contoh: LIB-BK-2024-0001"
                        required
                    >
                    <div class="field-error" data-error-for="record_code"></div>
                    <div class="field-error" data-error-for="collection_id"></div>
                </div>

                <div>
                    <label for="notes">Catatan</label>
                    <textarea
                        id="notes"
                        name="notes"
                        rows="3"
                        placeholder="Catatan reservasi, opsional"
                    ></textarea>
                    <div class="field-error" data-error-for="notes"></div>
                </div>
            </div>

            <div class="reservation-actions">
                <button type="submit" id="reservation-create-button">
                    Buat Reservasi
                </button>
                <button type="button" id="reservation-refresh-button" class="secondary">
                    Muat Ulang
                </button>
            </div>
        </form>
    </section>

    <section id="reservation-loading" class="card">
        <h2>Memuat reservasi...</h2>
        <p>Sedang mengambil daftar reservasi Anda.</p>
    </section>

    <section id="reservation-content" style="display: none;">
        <div class="card">
            <h2>Ringkasan</h2>
            <p id="reservation-summary">-</p>
        </div>

        <div id="reservation-list"></div>

        <div id="reservation-pagination" class="card reservation-pagination"></div>
    </section>

    <style>
        .reservation-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-size: 14px;
            font-weight: 700;
            color: var(--text);
        }

        input,
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
        textarea[aria-invalid="true"] {
            border-color: #dc2626;
        }

        .field-error {
            min-height: 18px;
            margin-top: 6px;
            color: #dc2626;
            font-size: 13px;
        }

        .reservation-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 16px;
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

        .reservation-item {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 14px;
        }

        .reservation-item h2 {
            margin-bottom: 8px;
        }

        .reservation-meta {
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

        .badge.active {
            background: #ecfdf5;
            border-color: #bbf7d0;
            color: #047857;
        }

        .badge.notified {
            background: #eff6ff;
            border-color: #bfdbfe;
            color: #1d4ed8;
        }

        .badge.cancelled,
        .badge.expired {
            background: #fef2f2;
            border-color: #fecaca;
            color: #b91c1c;
        }

        .badge.fulfilled {
            background: #f8fafc;
            border-color: #cbd5e1;
            color: #475569;
        }

        .reservation-note {
            color: var(--muted);
            line-height: 1.6;
            margin: 10px 0;
        }

        .reservation-pagination {
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

        .reservation-alert.error {
            border-color: #fecaca;
            background: #fef2f2;
        }

        .reservation-alert.success {
            border-color: #bbf7d0;
            background: #ecfdf5;
        }

        .reservation-alert.warning {
            border-color: #fde68a;
            background: #fffbeb;
        }

        .reservation-alert.error h2,
        .reservation-alert.error p {
            color: #b91c1c;
        }

        .reservation-alert.success h2,
        .reservation-alert.success p {
            color: #047857;
        }

        .reservation-alert.warning h2,
        .reservation-alert.warning p {
            color: #92400e;
        }

        .muted {
            color: var(--muted);
        }

        @media (max-width: 760px) {
            .reservation-form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection

@push('scripts')
    @vite('resources/js/pages/member/reservationsIndex.js')
@endpush