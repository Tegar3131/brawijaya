<?php

use Illuminate\Support\Facades\Route;

$placeholder = function (
    string $layout,
    string $title,
    string $description,
    array $endpoints = [],
    array $notes = [],
    string $eyebrow = 'SIMPB'
) {
    return function () use ($layout, $title, $description, $endpoints, $notes, $eyebrow) {
        return view('pages.placeholder', [
            'layout' => $layout,
            'title' => $title,
            'description' => $description,
            'endpoints' => $endpoints,
            'notes' => $notes,
            'eyebrow' => $eyebrow,
        ]);
    };
};

/*
|--------------------------------------------------------------------------
| Public Frontend Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('pages.public.home');
})->name('public.home');

Route::get('/catalog', function () {
    return view('pages.catalog.index');
})->name('public.catalog');

Route::get('/catalog/categories', $placeholder(
    layout: 'layouts.public',
    title: 'Kategori Koleksi',
    description: 'Browse kategori koleksi perpustakaan dan museum.',
    endpoints: [
        ['method' => 'GET', 'path' => '/api/catalog/categories', 'note' => 'Daftar kategori'],
        ['method' => 'GET', 'path' => '/api/catalog/categories/{slug}', 'note' => 'Detail kategori'],
    ],
    notes: [
        'Kategori dapat difilter berdasarkan type jika dibutuhkan.',
    ],
    eyebrow: 'Public Catalog'
))->name('public.categories');

Route::get('/catalog/categories/{slug}', function (string $slug) {
    return view('pages.placeholder', [
        'layout' => 'layouts.public',
        'title' => 'Detail Kategori: ' . $slug,
        'description' => 'Placeholder detail kategori dan daftar koleksi dalam kategori.',
        'endpoints' => [
            ['method' => 'GET', 'path' => '/api/catalog/categories/' . $slug, 'note' => 'Detail kategori dan koleksi'],
        ],
        'notes' => [
            'Slug kategori berasal dari tabel categories.',
        ],
        'eyebrow' => 'Public Catalog',
    ]);
})->name('public.categories.show');

Route::get('/catalog/collections/{identifier}', function (string $identifier) {
    return view('pages.catalog.show', [
        'identifier' => $identifier,
    ]);
})->name('public.collections.show');

Route::get('/catalog/collections/{identifier}/export/xml', [\App\Http\Controllers\CatalogExportController::class, 'exportXml'])
    ->name('public.collections.export.xml');
/*
|--------------------------------------------------------------------------
| Auth Frontend Routes
|--------------------------------------------------------------------------
*/

Route::get('/login', function () {
    return view('pages.auth.login');
})->name('auth.login');

/*
|--------------------------------------------------------------------------
| Forbidden Route
|--------------------------------------------------------------------------
*/
Route::get('/forbidden', $placeholder(
    layout: 'layouts.public',
    title: 'Akses Ditolak',
    description: 'Anda tidak memiliki role yang sesuai untuk membuka halaman ini.',
    endpoints: [],
    notes: [
        'Jika Anda merasa ini keliru, logout lalu login dengan akun yang memiliki role sesuai.',
        'Member tidak dapat membuka halaman staff.',
        'Pustakawan hanya dapat membuka area staff library.',
        'Kurator hanya dapat membuka area staff museum.',
    ],
    eyebrow: 'Forbidden'
))->name('frontend.forbidden');
/*
|--------------------------------------------------------------------------
| Member Frontend Routes
|--------------------------------------------------------------------------
*/

Route::prefix('member')
    ->name('member.')
    ->group(function () use ($placeholder) {
        Route::get('/dashboard', function () {
    return view('pages.member.dashboard');
})->name('dashboard');

        Route::get('/borrowings', function () {
    return view('pages.member.borrowings-active');
})->name('borrowings.active');

       Route::get('/borrowings/history', function () {
    return view('pages.member.borrowings-history');
})->name('borrowings.history');

        Route::get('/reservations', function () {
    return view('pages.member.reservations');
})->name('reservations');

        Route::get('/bookmarks', function () {
    return view('pages.member.bookmarks');
})->name('bookmarks');
    });

/*
|--------------------------------------------------------------------------
| Staff Frontend Routes
|--------------------------------------------------------------------------
*/

Route::prefix('staff')
    ->name('staff.')
    ->group(function () use ($placeholder) {
        Route::get('/dashboard', function () {
    return view('pages.staff.dashboard');
})->name('dashboard');

        Route::get('/collections', function () {
    return view('pages.staff.collections-index');
})->name('collections.index');

        Route::get('/collections/{identifier}/metadata', function (string $identifier) {
    return view('pages.staff.collection-metadata', [
        'identifier' => $identifier,
    ]);
})->name('collections.metadata');

Route::get('/collections/{identifier}/creators-subjects', function (string $identifier) {
    return view('pages.staff.collection-creators-subjects', [
        'identifier' => $identifier,
    ]);
})->name('collections.creators-subjects');

        Route::get('/collections/{identifier}/assets', function (string $identifier) {
    return view('pages.staff.collection-assets', [
        'identifier' => $identifier,
    ]);
})->name('collections.assets');

        Route::get('/collections/{identifier}/audit', function (string $identifier) {
    return view('pages.staff.collection-audit', [
        'identifier' => $identifier,
    ]);
})->name('collections.audit');

        Route::get('/collections/{identifier}/versions', function (string $identifier) {
    return view('pages.staff.collection-versions', [
        'identifier' => $identifier,
    ]);
})->name('collections.versions');

        Route::get('/collections/{identifier}/edit', function (string $identifier) {
            return view('pages.staff.collection-form', [
                'mode' => 'edit',
                'identifier' => $identifier,
            ]);
        })->name('collections.edit');

        // Import route — harus sebelum /{identifier} catch-all
        Route::get('/collections/import', function () {
            return view('pages.staff.collections-import');
        })->name('collections.import');

        Route::get('/collections/{identifier}', function (string $identifier) {
    return view('pages.staff.collections-show', [
        'identifier' => $identifier,
    ]);
})->name('collections.show');

        Route::get('/library/create', function () {
            return view('pages.staff.collection-form', [
                'mode' => 'create',
                'unitType' => 'library',
            ]);
        })->name('library.create');

        Route::get('/museum/create', function () {
            return view('pages.staff.collection-form', [
                'mode' => 'create',
                'unitType' => 'museum',
            ]);
        })->name('museum.create');



        Route::get('/circulation/reservations', $placeholder(
            layout: 'layouts.staff',
            title: 'Reservasi Sirkulasi',
            description: 'Placeholder manajemen reservasi sirkulasi.',
            endpoints: [
                ['method' => 'POST', 'path' => '/api/circulation/reservations/{reservation}/allocate-copy', 'note' => 'Alokasi copy'],
                ['method' => 'PATCH', 'path' => '/api/circulation/reservations/{reservation}/status', 'note' => 'Ubah status reservasi'],
            ],
            notes: [
                'Role: admin atau pustakawan.',
                'List reservasi saat ini memakai endpoint member per user; endpoint staff list reservasi global belum dibuat khusus.',
            ],
            eyebrow: 'Staff Circulation'
        ))->name('circulation.reservations');
    });