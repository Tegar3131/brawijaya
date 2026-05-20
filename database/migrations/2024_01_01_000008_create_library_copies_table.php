<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLibraryCopiesTable extends Migration
{
    public function up(): void
    {
        Schema::create('library_copies', function (Blueprint $table) {
            $table->id();

            $table->foreignId('collection_id')
                ->constrained('collections')
                ->cascadeOnDelete()
                ->comment('FK langsung ke collections, bukan ke library_items.');

            $table->string('copy_number', 40)
                ->comment('Nomor eksemplar untuk satu judul/koleksi.');

            $table->string('barcode', 120)
                ->unique()
                ->comment('Barcode unik per eksemplar.');

            $table->string('call_number', 120)
                ->index()
                ->comment('Nomor panggil per eksemplar.');

            $table->foreignId('location_id')
                ->nullable()
                ->constrained('locations')
                ->nullOnDelete();

            $table->enum('condition_grade', [
                'excellent',
                'good',
                'fair',
                'poor',
            ])
                ->default('good')
                ->index()
                ->comment('Kondisi fisik eksemplar perpustakaan.');

            $table->enum('status', [
                'available',
                'borrowed',
                'reserved',
                'repair',
                'lost',
                'archived',
            ])
                ->default('available')
                ->index();

            $table->date('acquired_at')
                ->nullable();

            $table->timestamp('last_inventory_at')
                ->nullable();

            $table->text('notes')
                ->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['collection_id', 'copy_number'],
                'library_copy_collection_copy_unique'
            );

            $table->index(
                ['collection_id', 'status'],
                'library_copy_collection_status_idx'
            );

            $table->index(
                ['status', 'location_id'],
                'library_copy_status_location_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_copies');
    }
}