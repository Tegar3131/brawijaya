<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCollectionsTable extends Migration
{
    public function up(): void
    {
        Schema::create('collections', function (Blueprint $table) {
            $table->id();

            $table->ulid('ulid')
                ->unique()
                ->comment('Public sortable identifier. Menggantikan UUID.');

            $table->string('record_code', 80)
                ->unique()
                ->comment('Kode internal: LIB-BK-2024-0001 / MUS-ART-SJT-0001.');

            $table->enum('unit_type', ['library', 'museum'])
                ->index()
                ->comment('Unit pemilik koleksi.');

            $table->string('collection_type', 80)
                ->index()
                ->comment('book, journal, thesis, artifact, historical_photo, archive_document, multimedia.');

            $table->string('title', 500);
            $table->string('subtitle', 500)->nullable();
            $table->longText('description')->nullable();

            $table->string('language_code', 10)
                ->nullable()
                ->index()
                ->comment('Kode bahasa ISO 639-2, contoh: ind, eng, ara, jav.');

            $table->string('rights_status', 120)
                ->nullable()
                ->index()
                ->comment('Status hak akses/hak cipta koleksi.');

            $table->string('date_display', 120)
                ->nullable()
                ->comment('Tampilan tanggal bebas, misalnya ca. 1942-1945.');

            $table->smallInteger('year_start')
                ->nullable()
                ->index()
                ->comment('Tahun awal untuk filter rentang tahun.');

            $table->smallInteger('year_end')
                ->nullable()
                ->index()
                ->comment('Tahun akhir untuk filter rentang tahun.');

            $table->foreignId('category_id')
                ->nullable()
                ->constrained('categories')
                ->nullOnDelete();

            $table->foreignId('current_location_id')
                ->nullable()
                ->constrained('locations')
                ->nullOnDelete();

            $table->enum('publication_status', [
                'draft',
                'published',
                'restricted',
                'archived',
            ])
                ->default('draft')
                ->index();

            $table->enum('visibility', [
                'public',
                'member',
                'internal',
                'restricted',
            ])
                ->default('public')
                ->index();

            $table->boolean('is_featured')
                ->default(false)
                ->index();

            $table->unsignedSmallInteger('featured_order')
                ->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('deleted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('archived_reason')
                ->nullable()
                ->comment('Wajib diisi di service saat koleksi diarsipkan.');

            $table->timestamp('archived_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(
                ['unit_type', 'collection_type', 'publication_status'],
                'collections_type_status_idx'
            );

            $table->index(
                ['visibility', 'publication_status'],
                'collections_visibility_status_idx'
            );

            $table->index(
                ['category_id', 'unit_type'],
                'collections_category_unit_idx'
            );

            $table->fullText(
                ['record_code', 'title', 'subtitle', 'description'],
                'collections_fulltext_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collections');
    }
}