<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\ReservationController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\BookmarkController;
use App\Http\Controllers\Api\StaffCollectionController;
use App\Http\Controllers\Api\StaffCollectionCreateController;
use App\Http\Controllers\Api\StaffCollectionImportController;
use App\Http\Controllers\Api\StaffCollectionMaintenanceController;
use App\Http\Controllers\Api\DigitalAssetAccessController;
use App\Http\Controllers\Api\StaffDigitalAssetUploadController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\StaffAuthorityLookupController;

Route::prefix('catalog')
    ->name('api.catalog.')
    ->group(function () {
        Route::get('/search', [CatalogController::class, 'search'])
    ->middleware('throttle:api-search')
    ->name('search');

        Route::get('/collections/{identifier}', [CatalogController::class, 'show'])
            ->name('collections.show');

        Route::get('/categories', [CatalogController::class, 'categories'])
            ->name('categories.index');

        Route::get('/categories/{slug}', [CatalogController::class, 'category'])
            ->name('categories.show');
    });

Route::prefix('auth')
    ->name('api.auth.')
    ->group(function () {
        Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:api-login')
    ->name('login');

        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/me', [AuthController::class, 'me'])
                ->name('me');

            Route::post('/logout', [AuthController::class, 'logout'])
                ->name('logout');
        });
    });

Route::prefix('member')
    ->name('api.member.')
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::get('/borrowings/active', [MemberController::class, 'activeBorrowings'])
            ->name('borrowings.active');

        Route::get('/borrowings/history', [MemberController::class, 'borrowingHistory'])
            ->name('borrowings.history');

        Route::get('/reservations', [MemberController::class, 'reservations'])
            ->name('reservations.index');

        Route::post('/reservations', [ReservationController::class, 'store'])
            ->name('reservations.store');

        Route::post('/reservations/{reservation}/cancel', [ReservationController::class, 'cancel'])
            ->name('reservations.cancel');

        Route::get('/bookmarks', [BookmarkController::class, 'index'])
    ->name('bookmarks.index');

Route::post('/bookmarks', [BookmarkController::class, 'store'])
    ->name('bookmarks.store');

Route::patch('/bookmarks/{identifier}', [BookmarkController::class, 'update'])
    ->name('bookmarks.update');

Route::delete('/bookmarks/{identifier}', [BookmarkController::class, 'destroy'])
    ->name('bookmarks.destroy');
    });

Route::prefix('circulation/reservations')
    ->name('api.circulation.reservations.')
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::post('/{reservation}/allocate-copy', [ReservationController::class, 'allocateCopy'])
            ->name('allocate-copy');

        Route::patch('/{reservation}/status', [ReservationController::class, 'updateStatus'])
            ->name('status');
    });

Route::prefix('staff')
    ->name('api.staff.')
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::get('/creators/search', [StaffAuthorityLookupController::class, 'creators'])->name('creators.search');
        Route::get('/subjects/search', [StaffAuthorityLookupController::class, 'subjects'])->name('subjects.search');
    });

  Route::prefix('staff/collections')
    ->name('api.staff.collections.')
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::get('/', [StaffCollectionController::class, 'index'])
            ->name('index');

        Route::post('/library', [StaffCollectionCreateController::class, 'storeLibrary'])
            ->name('library.store');

        Route::post('/museum', [StaffCollectionCreateController::class, 'storeMuseum'])
            ->name('museum.store');

        // Import routes — harus sebelum /{identifier} catch-all
        Route::get('/import/template/{unitType}', [StaffCollectionImportController::class, 'template'])
            ->name('import.template');

        Route::post('/import/preview', [StaffCollectionImportController::class, 'preview'])
            ->name('import.preview');

        Route::post('/import/execute', [StaffCollectionImportController::class, 'execute'])
            ->name('import.execute');

        Route::post('/{identifier}/digital-assets/upload', [StaffDigitalAssetUploadController::class, 'upload'])
    ->middleware('throttle:api-upload')
    ->name('digital-assets.upload');
        Route::patch('/{identifier}/metadata', [StaffCollectionMaintenanceController::class, 'upsertMetadata'])
    ->name('metadata.upsert');

Route::delete('/{identifier}/metadata/{metadata}', [StaffCollectionMaintenanceController::class, 'deleteMetadata'])
    ->name('metadata.delete');

Route::post('/{identifier}/creators', [StaffCollectionMaintenanceController::class, 'attachCreator'])
    ->name('creators.attach');

Route::delete('/{identifier}/creators/{creator}', [StaffCollectionMaintenanceController::class, 'detachCreator'])
    ->name('creators.detach');

Route::post('/{identifier}/subjects', [StaffCollectionMaintenanceController::class, 'attachSubject'])
    ->name('subjects.attach');

Route::delete('/{identifier}/subjects/{subject}', [StaffCollectionMaintenanceController::class, 'detachSubject'])
    ->name('subjects.detach');

Route::post('/{identifier}/digital-assets', [StaffCollectionMaintenanceController::class, 'registerDigitalAsset'])
    ->name('digital-assets.register');

Route::patch('/{identifier}/digital-assets/{asset}', [StaffCollectionMaintenanceController::class, 'updateDigitalAsset'])
    ->name('digital-assets.update');

Route::delete('/{identifier}/digital-assets/{asset}', [StaffCollectionMaintenanceController::class, 'deleteDigitalAsset'])
    ->name('digital-assets.delete');

        Route::get('/{identifier}/versions', [StaffCollectionController::class, 'versions'])
            ->name('versions');

        Route::get('/{identifier}/audit-logs', [StaffCollectionController::class, 'auditLogs'])
            ->name('audit-logs');

        Route::post('/{identifier}/publish', [StaffCollectionController::class, 'publish'])
            ->name('publish');

        Route::post('/{identifier}/archive', [StaffCollectionController::class, 'archive'])
            ->name('archive');

        Route::post('/{identifier}/restore', [StaffCollectionController::class, 'restore'])
            ->name('restore');

        Route::get('/{identifier}', [StaffCollectionController::class, 'show'])
            ->name('show');

        Route::patch('/{identifier}', [StaffCollectionController::class, 'update'])
            ->name('update');
    });

Route::get('/digital-assets/{asset}/download', [DigitalAssetAccessController::class, 'download'])
    ->middleware('auth:sanctum')
    ->name('api.digital-assets.download');

    Route::get('/dashboard', DashboardController::class)
    ->middleware('auth:sanctum')
    ->name('api.dashboard');