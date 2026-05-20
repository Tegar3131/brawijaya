@extends('layouts.member')

@section('title', 'Riwayat Pinjaman')

@section('content')
    <section class="card">
        <h2>Riwayat Pinjaman</h2>
        <p>
            Halaman ini mengambil data dari endpoint
            <code>GET /api/member/borrowings/history</code>.
        </p>
    </section>

    <section id="history-borrowing-alert" class="card borrowing-alert" style="display: none;"></section>

    <section id="history-borrowing-loading" class="card">
        <h2>Memuat riwayat pinjaman...</h2>
        <p>Sedang mengambil data riwayat pinjaman Anda.</p>
    </section>

    <section id="history-borrowing-content" style="display: none;">
        <div class="card">
            <h2>Ringkasan</h2>
            <p id="history-borrowing-summary">-</p>

            <div class="borrowing-actions">
                <button type="button" id="history-borrowing-refresh-button">
                    Muat Ulang
                </button>
            </div>
        </div>

        <div id="history-borrowing-list"></div>

        <div id="history-borrowing-pagination" class="card borrowing-pagination"></div>
    </section>

    <style>
        .borrowing-actions {
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

        .borrowing-item {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 14px;
        }

        .borrowing-item.overdue {
            border-color: #fecaca;
            background: #fffafa;
        }

        .borrowing-item h2 {
            margin-bottom: 8px;
        }

        .borrowing-meta {
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

        .badge.borrowed {
            background: #eff6ff;
            border-color: #bfdbfe;
            color: #1d4ed8;
        }

        .badge.overdue {
            background: #fef2f2;
            border-color: #fecaca;
            color: #b91c1c;
        }

        .badge.returned {
            background: #ecfdf5;
            border-color: #bbf7d0;
            color: #047857;
        }

        .badge.cancelled {
            background: #f8fafc;
            border-color: #cbd5e1;
            color: #475569;
        }

        .borrowing-detail-grid {
            display: grid;
            grid-template-columns: 180px 1fr;
            gap: 8px 14px;
            margin-top: 12px;
        }

        .borrowing-label {
            color: var(--muted);
            font-size: 14px;
        }

        .borrowing-value {
            color: var(--text);
            font-size: 14px;
        }

        .borrowing-pagination {
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

        .borrowing-alert.error {
            border-color: #fecaca;
            background: #fef2f2;
        }

        .borrowing-alert.error h2,
        .borrowing-alert.error p {
            color: #b91c1c;
        }

        .muted {
            color: var(--muted);
        }

        @media (max-width: 720px) {
            .borrowing-detail-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection

@push('scripts')
    @vite('resources/js/pages/member/borrowingsHistory.js')
@endpush