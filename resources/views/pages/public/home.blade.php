@extends('layouts.public')

@section('title', 'Beranda | SIMPB Malang')

@section('content')
<style>
    /* Scoped CSS untuk Landing Page SIMPB */
    .hero-section {
        position: relative;
        background: linear-gradient(135deg, #1f2922 0%, #111811 100%);
        color: white;
        padding: 8rem 2rem;
        text-align: center;
        overflow: hidden;
        border-radius: 4px;
        margin-bottom: 5rem;
        box-shadow: 0 20px 40px rgba(0,0,0,0.15);
    }

    .hero-section::after {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background-image: url('data:image/svg+xml,%3Csvg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg fill="%23c29b40" fill-opacity="0.05"%3E%3Cpath d="M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');
        opacity: 0.8;
        z-index: 1;
    }

    .hero-content {
        position: relative;
        z-index: 2;
        max-width: 900px;
        margin: 0 auto;
    }

    .hero-eyebrow {
        color: var(--accent);
        font-family: var(--font-sans);
        font-weight: 700;
        letter-spacing: 0.2em;
        text-transform: uppercase;
        font-size: 14px;
        margin-bottom: 1.5rem;
        display: block;
    }

    .hero-title {
        font-family: var(--font-heading);
        font-size: 4.5rem;
        font-weight: 500;
        line-height: 1.1;
        margin-bottom: 2rem;
        color: white;
        text-shadow: 0 4px 12px rgba(0,0,0,0.3);
    }

    .hero-description {
        font-family: var(--font-serif);
        font-size: 1.25rem;
        color: rgba(255,255,255,0.85);
        margin-bottom: 3rem;
        line-height: 1.8;
        max-width: 700px;
        margin-left: auto;
        margin-right: auto;
    }

    .btn-group {
        display: flex;
        gap: 1.5rem;
        justify-content: center;
        flex-wrap: wrap;
    }

    .btn-primary {
        background: var(--accent);
        color: white;
        padding: 1rem 2.5rem;
        border-radius: 4px;
        font-family: var(--font-sans);
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s ease;
        border: 1px solid var(--accent);
        text-transform: uppercase;
        letter-spacing: 0.1em;
        font-size: 14px;
    }

    .btn-primary:hover {
        background: var(--accent-hover);
        border-color: var(--accent-hover);
    }

    .btn-outline {
        background: transparent;
        color: white;
        padding: 1rem 2.5rem;
        border-radius: 4px;
        font-family: var(--font-sans);
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s ease;
        border: 1px solid rgba(255,255,255,0.4);
        text-transform: uppercase;
        letter-spacing: 0.1em;
        font-size: 14px;
    }

    .btn-outline:hover {
        border-color: white;
        background: rgba(255,255,255,0.05);
    }

    /* Features Section */
    .features-section {
        max-width: 1200px;
        margin: 0 auto 6rem;
    }

    .section-header {
        text-align: center;
        margin-bottom: 4rem;
    }

    .section-title {
        font-family: var(--font-heading);
        font-size: 2.5rem;
        color: var(--brand-dark);
        font-weight: 700;
        margin-bottom: 1rem;
    }

    .section-subtitle {
        font-family: var(--font-serif);
        color: var(--muted);
        font-size: 1.15rem;
        max-width: 650px;
        margin: 0 auto;
        line-height: 1.8;
    }

    .grid-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 3rem;
    }

    .feature-card {
        background: white;
        border-radius: 4px;
        padding: 3rem;
        border: 1px solid var(--border);
        transition: all 0.4s ease;
        position: relative;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0,0,0,0.02);
    }

    .feature-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.08);
        border-color: var(--accent);
    }

    .feature-icon {
        background: #fbf9f4;
        width: 72px;
        height: 72px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--accent);
        margin-bottom: 2rem;
        border: 1px solid rgba(194,155,64,0.2);
    }

    .feature-card h3 {
        font-family: var(--font-heading);
        font-size: 1.75rem;
        color: var(--brand-dark);
        margin-bottom: 1rem;
        font-weight: 700;
    }

    .feature-card p {
        font-family: var(--font-serif);
        color: var(--muted);
        line-height: 1.8;
        margin-bottom: 2rem;
    }

    .card-link {
        font-family: var(--font-sans);
        color: var(--accent);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.75rem;
        font-size: 13px;
        border-bottom: 1px solid transparent;
        padding-bottom: 4px;
        transition: all 0.2s;
    }

    .card-link:hover {
        color: var(--accent-hover);
        border-bottom-color: var(--accent-hover);
        gap: 1rem;
    }

    /* Info Section */
    .info-bar {
        background: white;
        border-top: 1px solid var(--border);
        border-bottom: 1px solid var(--border);
        padding: 4rem 2rem;
    }

    .info-grid {
        max-width: 1200px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 3rem;
        text-align: center;
    }

    .info-item h4 {
        font-family: var(--font-heading);
        color: var(--brand-dark);
        font-weight: 700;
        margin-bottom: 1rem;
        font-size: 1.25rem;
    }

    .info-item p {
        font-family: var(--font-serif);
        color: var(--muted);
        font-size: 1rem;
        line-height: 1.7;
    }
    
    @media (max-width: 768px) {
        .hero-title { font-size: 3rem; }
        .hero-section { padding: 4rem 1.5rem; }
    }
</style>

<section class="hero-section">
    <div class="hero-content">
        <span class="hero-eyebrow">Sistem Informasi Terpadu</span>
        <h1 class="hero-title">Merawat Ingatan,<br>Membangun Literasi Bangsa</h1>
        <p class="hero-description">
            Jelajahi arsip sejarah perjuangan militer dan ribuan koleksi literatur di Museum dan Perpustakaan Brawijaya Malang. Akses katalog digital kami kapan saja, di mana saja.
        </p>
        <div class="btn-group">
            <a href="{{ route('public.catalog') }}" class="btn-primary">Mulai Eksplorasi Katalog</a>
            <a href="{{ route('public.categories') }}" class="btn-outline">Lihat Kategori Koleksi</a>
        </div>
    </div>
</section>

<section class="features-section">
    <div class="section-header">
        <h2 class="section-title">Koleksi & Layanan Digital</h2>
        <p class="section-subtitle">Akses informasi artefak museum dan inventaris perpustakaan secara transparan melalui satu gerbang utama.</p>
    </div>

    <div class="grid-cards">
        <div class="feature-card">
            <div class="feature-icon">
                <svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"></path></svg>
            </div>
            <h3>Katalog Museum</h3>
            <p>Telusuri koleksi artefak militer, senjata bersejarah, kendaraan tempur, dan dokumen perjuangan kemerdekaan dari wilayah Jawa Timur.</p>
            <a href="{{ route('public.catalog') }}?unit_type=museum" class="card-link">
                Jelajahi Museum <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
            </a>
        </div>

        <div class="feature-card">
            <div class="feature-icon">
                <svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
            </div>
            <h3>Koleksi Perpustakaan</h3>
            <p>Akses ribuan monograf, jurnal militer, dan literatur umum. Cek ketersediaan buku secara real-time sebelum Anda berkunjung.</p>
            <a href="{{ route('public.catalog') }}?unit_type=library" class="card-link">
                Cari Buku <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
            </a>
        </div>
    </div>
</section>

<section class="info-bar">
    <div class="info-grid">
        <div class="info-item">
            <svg width="24" height="24" fill="none" stroke="var(--gold-accent)" stroke-width="2" viewBox="0 0 24 24" style="margin-bottom:1rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
            <h4>Lokasi Kami</h4>
            <p>Jl. Ijen No.25A, Gading Kasri,<br>Kota Malang, Jawa Timur 65115</p>
        </div>
        <div class="info-item">
            <svg width="24" height="24" fill="none" stroke="var(--gold-accent)" stroke-width="2" viewBox="0 0 24 24" style="margin-bottom:1rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <h4>Jam Operasional</h4>
            <p>Senin - Jumat: 08.00 - 15.00 WIB<br>Sabtu - Minggu: Tutup (Kecuali Janji Temu)</p>
        </div>
        <div class="info-item">
            <svg width="24" height="24" fill="none" stroke="var(--gold-accent)" stroke-width="2" viewBox="0 0 24 24" style="margin-bottom:1rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
            <h4>Keanggotaan</h4>
            <p>Daftar sebagai member untuk<br>meminjam buku fisik di perpustakaan.</p>
        </div>
    </div>
</section>
@endsection