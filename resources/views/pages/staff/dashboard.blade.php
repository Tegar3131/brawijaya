@extends('layouts.staff')

@section('title', 'Dashboard Staff')

@section('content')
    <section class="card">
        <h2>Ringkasan Staff</h2>
        <p>
            Dashboard ini mengambil data dari endpoint <code>GET /api/dashboard</code>
            dan menyesuaikan tampilan berdasarkan role login.
        </p>
    </section>

    <section id="staff-dashboard-alert" class="card" style="display: none;"></section>

    <section id="staff-dashboard-loading" class="card">
        <h2>Memuat dashboard...</h2>
        <p>Sedang mengambil data dashboard staff.</p>
    </section>

    <section id="staff-dashboard-content" style="display: none;">
        <div class="card">
            <h2>Role Dashboard</h2>
            <ul>
                <li>Role aktif: <strong data-field="dashboard_role">-</strong></li>
                <li>Dibuat pada: <strong data-field="generated_at">-</strong></li>
            </ul>
        </div>

        <div id="admin-summary" style="display: none;">
            <div class="card">
                <h2>Admin — Koleksi</h2>
                <ul>
                    <li>Total aktif: <strong data-field="admin_collections_total_active">0</strong></li>
                    <li>Total termasuk archived: <strong data-field="admin_collections_total_with_archived">0</strong></li>
                    <li>Library: <strong data-field="admin_collections_library">0</strong></li>
                    <li>Museum: <strong data-field="admin_collections_museum">0</strong></li>
                    <li>Published: <strong data-field="admin_collections_published">0</strong></li>
                    <li>Draft: <strong data-field="admin_collections_draft">0</strong></li>
                    <li>Archived: <strong data-field="admin_collections_archived">0</strong></li>
                </ul>
            </div>

            <div class="card">
                <h2>Admin — User dan Sirkulasi</h2>
                <ul>
                    <li>Total user: <strong data-field="admin_users_total">0</strong></li>
                    <li>Total member: <strong data-field="admin_users_members">0</strong></li>
                    <li>Member aktif: <strong data-field="admin_users_active_members">0</strong></li>
                    <li>Pinjaman aktif: <strong data-field="admin_circulation_active">0</strong></li>
                    <li>Pinjaman overdue: <strong data-field="admin_circulation_overdue">0</strong></li>
                    <li>Reservasi aktif: <strong data-field="admin_circulation_reservations">0</strong></li>
                </ul>
            </div>
        </div>

        <div id="library-summary" style="display: none;">
            <div class="card">
                <h2>Pustakawan — Koleksi Library</h2>
                <ul>
                    <li>Total library: <strong data-field="library_total">0</strong></li>
                    <li>Published: <strong data-field="library_published">0</strong></li>
                    <li>Draft: <strong data-field="library_draft">0</strong></li>
                    <li>Archived: <strong data-field="library_archived">0</strong></li>
                </ul>
            </div>

            <div class="card">
                <h2>Pustakawan — Copy dan Sirkulasi</h2>
                <ul>
                    <li>Total copy: <strong data-field="copy_total">0</strong></li>
                    <li>Copy available: <strong data-field="copy_available">0</strong></li>
                    <li>Copy borrowed: <strong data-field="copy_borrowed">0</strong></li>
                    <li>Copy reserved: <strong data-field="copy_reserved">0</strong></li>
                    <li>Pinjaman aktif: <strong data-field="library_active_borrowings">0</strong></li>
                    <li>Pinjaman overdue: <strong data-field="library_overdue_borrowings">0</strong></li>
                </ul>
            </div>

            <div class="card">
                <h2>Pustakawan — Reservasi</h2>
                <ul>
                    <li>Active: <strong data-field="reservation_active">0</strong></li>
                    <li>Notified: <strong data-field="reservation_notified">0</strong></li>
                    <li>Expired: <strong data-field="reservation_expired">0</strong></li>
                    <li>Cancelled: <strong data-field="reservation_cancelled">0</strong></li>
                </ul>
            </div>
        </div>

        <div id="museum-summary" style="display: none;">
            <div class="card">
                <h2>Kurator — Koleksi Museum</h2>
                <ul>
                    <li>Total museum: <strong data-field="museum_total">0</strong></li>
                    <li>Published: <strong data-field="museum_published">0</strong></li>
                    <li>Draft: <strong data-field="museum_draft">0</strong></li>
                    <li>Archived: <strong data-field="museum_archived">0</strong></li>
                </ul>
            </div>

            <div class="card">
                <h2>Kurator — Museum Items dan Kondisi</h2>
                <ul>
                    <li>Total museum items: <strong data-field="museum_items_total">0</strong></li>
                    <li>Object sensitive: <strong data-field="museum_items_sensitive">0</strong></li>
                    <li>Total condition report: <strong data-field="condition_total">0</strong></li>
                    <li>Urgent: <strong data-field="condition_urgent">0</strong></li>
                    <li>Maintenance: <strong data-field="condition_maintenance">0</strong></li>
                    <li>Review due: <strong data-field="condition_review_due">0</strong></li>
                </ul>
            </div>

            <div class="card">
                <h2>Kurator — Digital Assets</h2>
                <ul>
                    <li>Total asset museum: <strong data-field="museum_assets_total">0</strong></li>
                    <li>Foto: <strong data-field="museum_assets_photos">0</strong></li>
                    <li>Dokumen: <strong data-field="museum_assets_documents">0</strong></li>
                </ul>
            </div>
        </div>

        <div class="card">
            <h2>Aktivitas Terbaru</h2>
            <div id="staff-latest-list">
                <p>Belum ada aktivitas terbaru.</p>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    @vite('resources/js/pages/dashboard/staffDashboard.js')
@endpush