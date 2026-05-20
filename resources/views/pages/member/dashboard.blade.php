@extends('layouts.member')

@section('title', 'Dashboard Member')

@section('content')
    <section class="card">
        <h2>Ringkasan Member</h2>
        <p>
            Dashboard ini mengambil data dari endpoint <code>GET /api/dashboard</code>.
        </p>
    </section>

    <section id="member-dashboard-alert" class="card" style="display: none;"></section>

    <section id="member-dashboard-loading" class="card">
        <h2>Memuat dashboard...</h2>
        <p>Sedang mengambil data dashboard member.</p>
    </section>

    <section id="member-dashboard-content" style="display: none;">
        <div class="card">
            <h2>Membership</h2>
            <ul>
                <li>Nomor member: <strong data-field="member_number">-</strong></li>
                <li>Status membership: <strong data-field="membership_status">-</strong></li>
                <li>Aktif sampai: <strong data-field="member_active_until">-</strong></li>
                <li>Bisa meminjam: <strong data-field="can_borrow">-</strong></li>
                <li>Maksimal pinjam: <strong data-field="max_borrow_items">-</strong></li>
                <li>Durasi pinjam default: <strong data-field="borrow_duration_days">-</strong> hari</li>
            </ul>
        </div>

        <div class="card">
            <h2>Pinjaman</h2>
            <ul>
                <li>Pinjaman aktif: <strong data-field="active_borrowings">0</strong></li>
                <li>Pinjaman terlambat: <strong data-field="overdue_borrowings">0</strong></li>
                <li>Total riwayat pinjam: <strong data-field="history_borrowings">0</strong></li>
            </ul>
        </div>

        <div class="card">
            <h2>Denda</h2>
            <ul>
                <li>Jumlah transaksi dengan denda belum lunas: <strong data-field="unpaid_fine_count">0</strong></li>
                <li>Total denda belum lunas: <strong data-field="unpaid_fine_total">0</strong></li>
            </ul>
        </div>

        <div class="card">
            <h2>Reservasi dan Bookmark</h2>
            <ul>
                <li>Reservasi aktif: <strong data-field="active_reservations">0</strong></li>
                <li>Reservasi notified: <strong data-field="notified_reservations">0</strong></li>
                <li>Total bookmark: <strong data-field="bookmark_total">0</strong></li>
            </ul>
        </div>

        <div class="card">
            <h2>Pinjaman Aktif Terdekat</h2>
            <div id="active-borrowing-list">
                <p>Belum ada pinjaman aktif.</p>
            </div>
        </div>

        <div class="card">
            <h2>Reservasi Terbaru</h2>
            <div id="reservation-list">
                <p>Belum ada reservasi.</p>
            </div>
        </div>

        <div class="card">
            <h2>Bookmark Terbaru</h2>
            <div id="bookmark-list">
                <p>Belum ada bookmark.</p>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    @vite('resources/js/pages/dashboard/memberDashboard.js')
@endpush