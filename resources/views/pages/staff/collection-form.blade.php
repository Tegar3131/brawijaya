@extends('layouts.staff')

@section('title', $mode === 'create' ? 'Tambah Koleksi ' . ucfirst($unitType) : 'Edit Koleksi')

@push('scripts')
    @vite('resources/js/pages/staff/collectionForm.js')
@endpush

@section('content')
<style>
    .form-section {
        background: var(--panel);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 24px;
    }
    .form-section h3 {
        margin-top: 0;
        margin-bottom: 16px;
        color: var(--accent);
        border-bottom: 1px solid var(--border);
        padding-bottom: 8px;
    }
    .form-group {
        margin-bottom: 16px;
    }
    .form-group label {
        display: block;
        margin-bottom: 6px;
        font-weight: bold;
        font-size: 14px;
    }
    .form-control, .form-select {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid var(--border);
        border-radius: 6px;
        font-size: 14px;
    }
    .form-control:focus, .form-select:focus {
        outline: 2px solid var(--accent);
    }
    .btn {
        padding: 8px 16px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: bold;
        font-size: 14px;
    }
    .btn-primary {
        background: var(--accent);
        color: white;
    }
    .btn-secondary {
        background: var(--muted);
        color: white;
    }
    .btn-danger {
        background: #ef4444;
        color: white;
    }
    .grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }
    .dynamic-list-item {
        border: 1px solid var(--border);
        padding: 12px;
        border-radius: 6px;
        margin-bottom: 12px;
        position: relative;
    }
    .remove-btn {
        position: absolute;
        top: 12px;
        right: 12px;
    }
    .hidden {
        display: none !important;
    }
</style>

<div id="collection-form-app" 
     data-mode="{{ $mode }}" 
     data-unit-type="{{ $unitType ?? '' }}" 
     data-identifier="{{ $identifier ?? '' }}">
    
    <div class="card">
        <p>Gunakan formulir ini untuk mengisi data dasar koleksi. Metadata standar akan otomatis disinkronkan.</p>
    </div>

    <form id="collection-form">
        <!-- SECTION: IDENTITAS DASAR -->
        <div class="form-section">
            <h3>Identitas Dasar (Collection)</h3>
            <div class="grid-2">
                <div class="form-group">
                    <label for="record_code">Record Code *</label>
                    <input type="text" id="record_code" name="collection[record_code]" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="collection_type">Tipe Koleksi *</label>
                    <input type="text" id="collection_type" name="collection[collection_type]" class="form-control" placeholder="Contoh: book, artifact, photo" required>
                </div>
            </div>
            <div class="form-group">
                <label for="title">Judul Utama *</label>
                <input type="text" id="title" name="collection[title]" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="subtitle">Sub-Judul</label>
                <input type="text" id="subtitle" name="collection[subtitle]" class="form-control">
            </div>
            <div class="form-group">
                <label for="description">Deskripsi / Abstrak</label>
                <textarea id="description" name="collection[description]" class="form-control" rows="4"></textarea>
            </div>
            <div class="grid-2">
                <div class="form-group">
                    <label for="language_code">Bahasa (Kode ISO)</label>
                    <input type="text" id="language_code" name="collection[language_code]" class="form-control" placeholder="id, en, ms">
                </div>
                <div class="form-group">
                    <label for="date_display">Tahun/Tanggal Display</label>
                    <input type="text" id="date_display" name="collection[date_display]" class="form-control">
                </div>
            </div>
        </div>

        <!-- SECTION: STATUS & AKSES -->
        <div class="form-section">
            <h3>Status & Akses</h3>
            <div class="grid-2">
                <div class="form-group">
                    <label for="publication_status">Status Publikasi</label>
                    <select id="publication_status" name="collection[publication_status]" class="form-select">
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                        <option value="restricted">Restricted</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="visibility">Visibilitas</label>
                    <select id="visibility" name="collection[visibility]" class="form-select">
                        <option value="public">Public (Semua)</option>
                        <option value="member">Member (Login)</option>
                        <option value="internal">Internal (Staff)</option>
                    </select>
                </div>
            </div>
            <div class="grid-2">
                <div class="form-group">
                    <label for="category_id">Kategori</label>
                    <select id="category_id" name="collection[category_id]" class="form-select">
                        <option value="">Pilih Kategori...</option>
                        <!-- Akan diisi via JS (lookup) -->
                    </select>
                </div>
                <div class="form-group">
                    <label for="current_location_id">Lokasi Saat Ini</label>
                    <select id="current_location_id" name="collection[current_location_id]" class="form-select">
                        <option value="">Pilih Lokasi...</option>
                        <!-- Akan diisi via JS (lookup) -->
                    </select>
                </div>
            </div>
        </div>

        <!-- SECTION: LIBRARY ITEM -->
        <div class="form-section" id="section-library" style="display: none;">
            <h3>Detail Perpustakaan</h3>
            <div class="grid-2">
                <div class="form-group">
                    <label for="bibliographic_level">Tingkat Bibliografi *</label>
                    <select id="bibliographic_level" name="library_item[bibliographic_level]" class="form-select">
                        <option value="monograph">Monograf (Buku)</option>
                        <option value="serial">Serial (Jurnal/Majalah)</option>
                        <option value="article">Artikel</option>
                        <option value="thesis">Tesis/Disertasi</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="isbn13">ISBN-13</label>
                    <input type="text" id="isbn13" name="library_item[isbn13]" class="form-control">
                </div>
            </div>
            <div class="grid-2">
                <div class="form-group">
                    <label for="publisher_name">Penerbit</label>
                    <input type="text" id="publisher_name" name="library_item[publisher_name]" class="form-control">
                </div>
                <div class="form-group">
                    <label for="publication_year">Tahun Terbit</label>
                    <input type="number" id="publication_year" name="library_item[publication_year]" class="form-control">
                </div>
            </div>
            <div class="grid-2">
                <div class="form-group">
                    <label for="call_number">Call Number</label>
                    <input type="text" id="call_number" name="library_item[call_number]" class="form-control">
                </div>
                <div class="form-group">
                    <label for="physical_extent">Ekstensi Fisik</label>
                    <input type="text" id="physical_extent" name="library_item[physical_extent]" class="form-control" placeholder="Contoh: xii, 300 hlm">
                </div>
            </div>
        </div>

        <!-- SECTION: MUSEUM ITEM -->
        <div class="form-section" id="section-museum" style="display: none;">
            <h3>Detail Museum</h3>
            <div class="grid-2">
                <div class="form-group">
                    <label for="inventory_number">Nomor Inventaris *</label>
                    <input type="text" id="inventory_number" name="museum_item[inventory_number]" class="form-control">
                </div>
                <div class="form-group">
                    <label for="object_name">Nama Objek *</label>
                    <input type="text" id="object_name" name="museum_item[object_name]" class="form-control">
                </div>
            </div>
            <div class="grid-2">
                <div class="form-group">
                    <label for="object_type_label">Tipe Objek (Label) *</label>
                    <input type="text" id="object_type_label" name="museum_item[object_type_label]" class="form-control">
                </div>
                <div class="form-group">
                    <label for="classification">Klasifikasi *</label>
                    <input type="text" id="classification" name="museum_item[classification]" class="form-control">
                </div>
            </div>
            <div class="grid-2">
                <div class="form-group">
                    <label for="maker_name">Nama Pembuat/Seniman</label>
                    <input type="text" id="maker_name" name="museum_item[maker_name]" class="form-control">
                </div>
                <div class="form-group">
                    <label for="period_display">Periode/Zaman</label>
                    <input type="text" id="period_display" name="museum_item[period_display]" class="form-control">
                </div>
            </div>
            <div class="form-group">
                <label for="material_summary">Ringkasan Material</label>
                <input type="text" id="material_summary" name="museum_item[material_summary]" class="form-control">
            </div>
            <div class="form-group">
                <label for="provenance_history">Sejarah Asal-Usul (Provenance)</label>
                <textarea id="provenance_history" name="museum_item[provenance_history]" class="form-control" rows="3"></textarea>
            </div>
            <div class="grid-2">
                <div class="form-group">
                    <label for="condition_current">Kondisi Saat Ini</label>
                    <select id="condition_current" name="museum_item[condition_current]" class="form-select">
                        <option value="good">Baik (Good)</option>
                        <option value="excellent">Sangat Baik (Excellent)</option>
                        <option value="fair">Sedang (Fair)</option>
                        <option value="poor">Buruk (Poor)</option>
                        <option value="critical">Kritis (Critical)</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- SECTION: KREATOR -->
        <div class="form-section">
            <h3>Kreator (Penulis/Seniman)</h3>
            <div id="creators-container">
                <!-- Javascript will populate creators here -->
            </div>
            <button type="button" class="btn btn-secondary mt-2" id="btn-add-creator">+ Tambah Kreator</button>
        </div>

        <!-- SECTION: SUBJEK -->
        <div class="form-section">
            <h3>Subjek (Topik/Kata Kunci)</h3>
            <div id="subjects-container">
                <!-- Javascript will populate subjects here -->
            </div>
            <button type="button" class="btn btn-secondary mt-2" id="btn-add-subject">+ Tambah Subjek</button>
        </div>
        
        <!-- UPDATE REASON (FOR EDIT ONLY) -->
        <div class="form-section" id="section-reason" style="display: none;">
            <h3>Alasan Perubahan</h3>
            <div class="form-group">
                <label for="update_reason">Catatan Versi (Wajib) *</label>
                <input type="text" id="update_reason" name="update_reason" class="form-control" placeholder="Contoh: Memperbaiki judul yang typo">
            </div>
        </div>

        <div style="margin-top: 30px; margin-bottom: 50px;">
            <button type="submit" class="btn btn-primary" id="btn-submit">Simpan Koleksi</button>
            <a href="javascript:history.back()" class="btn btn-secondary" style="text-decoration: none; display: inline-block;">Batal</a>
        </div>
    </form>
</div>

<!-- TEMPLATES FOR DYNAMIC FIELDS -->
<template id="creator-template">
    <div class="dynamic-list-item creator-item">
        <button type="button" class="btn btn-danger remove-btn btn-remove-item">X</button>
        <div class="grid-2">
            <div class="form-group">
                <label>Nama Kreator *</label>
                <input type="text" class="form-control input-creator-name" required>
            </div>
            <div class="form-group">
                <label>Peran (Role)</label>
                <input type="text" class="form-control input-creator-role" placeholder="author, maker, dll">
            </div>
        </div>
        <div class="form-group">
            <label>
                <input type="checkbox" class="input-creator-primary"> Sebagai Kreator Utama (Primary)
            </label>
        </div>
    </div>
</template>

<template id="subject-template">
    <div class="dynamic-list-item subject-item">
        <button type="button" class="btn btn-danger remove-btn btn-remove-item">X</button>
        <div class="grid-2">
            <div class="form-group">
                <label>Term / Subjek *</label>
                <input type="text" class="form-control input-subject-term" required>
            </div>
            <div class="form-group">
                <label>Tipe Entitas</label>
                <select class="form-select input-subject-type">
                    <option value="topic">Topic</option>
                    <option value="geographic">Geographic</option>
                    <option value="person">Person</option>
                    <option value="organization">Organization</option>
                    <option value="temporal">Temporal</option>
                </select>
            </div>
        </div>
    </div>
</template>
@endsection
