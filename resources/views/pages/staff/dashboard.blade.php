@extends('layouts.staff')

@section('title', 'Dashboard Staff')

@section('content')
    <style>
        /* Scoped Custom CSS untuk Tampilan Elegan & Modern */
        .dashboard-header { margin-bottom: 2rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 1rem; }
        .dashboard-header h2 { font-weight: 700; color: #1e293b; font-size: 1.75rem; margin-bottom: 0.5rem; }
        .dashboard-header p { color: #64748b; margin: 0; }
        
        .role-badge { display: inline-flex; align-items: center; background: #f8fafc; border: 1px solid #e2e8f0; padding: 0.75rem 1.25rem; border-radius: 0.75rem; margin-bottom: 2rem; color: #475569; font-size: 0.95rem; }
        .role-badge strong { color: #0f172a; margin: 0 0.25rem; text-transform: capitalize; }
        
        .section-wrapper { margin-bottom: 3rem; }
        .section-title { font-size: 1.25rem; font-weight: 600; color: #334155; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.5rem; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.25rem; margin-bottom: 1.5rem; }
        .stat-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 0.75rem; padding: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.02); transition: all 0.2s ease; }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); border-color: #cbd5e1; }
        .stat-label { font-size: 0.8rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem; display: block; }
        .stat-value { font-size: 1.8rem; font-weight: 700; color: #0f172a; line-height: 1; }
        
        .alert-custom { background: #fef2f2; border-left: 4px solid #ef4444; padding: 1rem 1.5rem; color: #991b1b; border-radius: 0.5rem; margin-bottom: 2rem; }
        .loading-state { text-align: center; padding: 4rem 2rem; color: #64748b; background: #f8fafc; border-radius: 0.75rem; border: 1px dashed #cbd5e1; margin-bottom: 2rem; }
        
        .activity-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 0.75rem; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
    </style>

    <div class="dashboard-header">
        <h2>Ringkasan Staff</h2>
        <p>Dashboard ini mengambil data dari endpoint <code>GET /api/dashboard</code> dan menyesuaikan tampilan berdasarkan role login.</p>
    </div>

    <section id="staff-dashboard-alert" class="alert-custom" style="display: none;"></section>

    <section id="staff-dashboard-loading" class="loading-state">
        <h3 style="font-weight: 600; color: #475569; margin-bottom: 0.5rem;">Memuat dashboard...</h3>
        <p>Sedang mengambil data dashboard staff, mohon tunggu sebentar.</p>
    </section>

    <section id="staff-dashboard-content" style="display: none;">
        
        <div class="role-badge">
            Role Aktif: <strong data-field="dashboard_role">-</strong> 
            <span style="color: #cbd5e1; margin: 0 0.5rem;">|</span> 
            Dibuat pada: <strong data-field="generated_at">-</strong>
        </div>

        <div id="admin-summary" class="section-wrapper" style="display: none;">
            <h3 class="section-title">✨ Admin — Koleksi</h3>
            <div class="stats-grid">
                <div class="stat-card"><span class="stat-label">Total Aktif</span><div class="stat-value" data-field="admin_collections_total_active">0</div></div>
                <div class="stat-card"><span class="stat-label">Total (+Archived)</span><div class="stat-value" data-field="admin_collections_total_with_archived">0</div></div>
                <div class="stat-card"><span class="stat-label">Library</span><div class="stat-value" data-field="admin_collections_library">0</div></div>
                <div class="stat-card"><span class="stat-label">Museum</span><div class="stat-value" data-field="admin_collections_museum">0</div></div>
                <div class="stat-card"><span class="stat-label">Published</span><div class="stat-value" data-field="admin_collections_published">0</div></div>
                <div class="stat-card"><span class="stat-label">Draft</span><div class="stat-value" data-field="admin_collections_draft">0</div></div>
                <div class="stat-card"><span class="stat-label">Archived</span><div class="stat-value" data-field="admin_collections_archived">0</div></div>
            </div>

            <h3 class="section-title">👥 Admin — User & Sirkulasi</h3>
            <div class="stats-grid">
                <div class="stat-card"><span class="stat-label">Total User</span><div class="stat-value" data-field="admin_users_total">0</div></div>
                <div class="stat-card"><span class="stat-label">Total Member</span><div class="stat-value" data-field="admin_users_members">0</div></div>
                <div class="stat-card"><span class="stat-label">Member Aktif</span><div class="stat-value" data-field="admin_users_active_members">0</div></div>
                <div class="stat-card"><span class="stat-label">Pinjaman Aktif</span><div class="stat-value" data-field="admin_circulation_active">0</div></div>
                <div class="stat-card"><span class="stat-label" style="color: #ef4444;">Pinjaman Overdue</span><div class="stat-value" data-field="admin_circulation_overdue">0</div></div>
                <div class="stat-card"><span class="stat-label">Reservasi Aktif</span><div class="stat-value" data-field="admin_circulation_reservations">0</div></div>
            </div>
        </div>

        <div id="library-summary" class="section-wrapper" style="display: none;">
            <h3 class="section-title">📚 Pustakawan — Koleksi Library</h3>
            <div class="stats-grid">
                <div class="stat-card"><span class="stat-label">Total Library</span><div class="stat-value" data-field="library_total">0</div></div>
                <div class="stat-card"><span class="stat-label">Published</span><div class="stat-value" data-field="library_published">0</div></div>
                <div class="stat-card"><span class="stat-label">Draft</span><div class="stat-value" data-field="library_draft">0</div></div>
                <div class="stat-card"><span class="stat-label">Archived</span><div class="stat-value" data-field="library_archived">0</div></div>
            </div>

            <h3 class="section-title">🔄 Pustakawan — Copy & Sirkulasi</h3>
            <div class="stats-grid">
                <div class="stat-card"><span class="stat-label">Total Copy</span><div class="stat-value" data-field="copy_total">0</div></div>
                <div class="stat-card"><span class="stat-label" style="color: #10b981;">Copy Available</span><div class="stat-value" data-field="copy_available">0</div></div>
                <div class="stat-card"><span class="stat-label">Copy Borrowed</span><div class="stat-value" data-field="copy_borrowed">0</div></div>
                <div class="stat-card"><span class="stat-label">Copy Reserved</span><div class="stat-value" data-field="copy_reserved">0</div></div>
                <div class="stat-card"><span class="stat-label">Pinjaman Aktif</span><div class="stat-value" data-field="library_active_borrowings">0</div></div>
                <div class="stat-card"><span class="stat-label" style="color: #ef4444;">Pinjaman Overdue</span><div class="stat-value" data-field="library_overdue_borrowings">0</div></div>
            </div>

            <h3 class="section-title">🔖 Pustakawan — Reservasi</h3>
            <div class="stats-grid">
                <div class="stat-card"><span class="stat-label">Active</span><div class="stat-value" data-field="reservation_active">0</div></div>
                <div class="stat-card"><span class="stat-label">Notified</span><div class="stat-value" data-field="reservation_notified">0</div></div>
                <div class="stat-card"><span class="stat-label">Expired</span><div class="stat-value" data-field="reservation_expired">0</div></div>
                <div class="stat-card"><span class="stat-label">Cancelled</span><div class="stat-value" data-field="reservation_cancelled">0</div></div>
            </div>
        </div>

        <div id="museum-summary" class="section-wrapper" style="display: none;">
            <h3 class="section-title">🏛️ Kurator — Koleksi Museum</h3>
            <div class="stats-grid">
                <div class="stat-card"><span class="stat-label">Total Museum</span><div class="stat-value" data-field="museum_total">0</div></div>
                <div class="stat-card"><span class="stat-label">Published</span><div class="stat-value" data-field="museum_published">0</div></div>
                <div class="stat-card"><span class="stat-label">Draft</span><div class="stat-value" data-field="museum_draft">0</div></div>
                <div class="stat-card"><span class="stat-label">Archived</span><div class="stat-value" data-field="museum_archived">0</div></div>
            </div>

            <h3 class="section-title">🔍 Kurator — Items & Kondisi</h3>
            <div class="stats-grid">
                <div class="stat-card"><span class="stat-label">Total Items</span><div class="stat-value" data-field="museum_items_total">0</div></div>
                <div class="stat-card"><span class="stat-label">Objek Sensitif</span><div class="stat-value" data-field="museum_items_sensitive">0</div></div>
                <div class="stat-card"><span class="stat-label">Total Laporan Kondisi</span><div class="stat-value" data-field="condition_total">0</div></div>
                <div class="stat-card"><span class="stat-label" style="color: #ef4444;">Urgent</span><div class="stat-value" data-field="condition_urgent">0</div></div>
                <div class="stat-card"><span class="stat-label" style="color: #f59e0b;">Maintenance</span><div class="stat-value" data-field="condition_maintenance">0</div></div>
                <div class="stat-card"><span class="stat-label">Review Due</span><div class="stat-value" data-field="condition_review_due">0</div></div>
            </div>

            <h3 class="section-title">📸 Kurator — Digital Assets</h3>
            <div class="stats-grid">
                <div class="stat-card"><span class="stat-label">Total Asset Museum</span><div class="stat-value" data-field="museum_assets_total">0</div></div>
                <div class="stat-card"><span class="stat-label">Foto</span><div class="stat-value" data-field="museum_assets_photos">0</div></div>
                <div class="stat-card"><span class="stat-label">Dokumen</span><div class="stat-value" data-field="museum_assets_documents">0</div></div>
            </div>
        </div>

        <div class="section-wrapper">
            <h3 class="section-title">⏱️ Aktivitas Terbaru</h3>
            <div class="activity-card">
                <div id="staff-latest-list">
                    <p style="color: #64748b; margin: 0;">Belum ada aktivitas terbaru.</p>
                </div>
            </div>
        </div>

    </section>
@endsection

@push('scripts')
    @vite('resources/js/pages/dashboard/staffDashboard.js')
@endpush