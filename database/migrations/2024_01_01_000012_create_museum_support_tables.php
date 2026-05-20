<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMuseumSupportTables extends Migration
{
    public function up(): void
    {
        Schema::create('condition_reports', function (Blueprint $table) {
            $table->id();

            $table->foreignId('museum_item_id')
                ->constrained('museum_items')
                ->cascadeOnDelete()
                ->comment('Artefak museum yang diperiksa.');

            $table->enum('condition_grade', [
                'excellent',
                'good',
                'fair',
                'poor',
                'critical',
            ])
                ->index()
                ->comment('Kondisi artefak saat pemeriksaan. Tidak memakai tabel conditions.');

            $table->foreignId('inspected_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Kurator/staf yang melakukan pemeriksaan.');

            $table->dateTime('inspected_at')
                ->index()
                ->comment('Tanggal dan waktu pemeriksaan kondisi.');

            $table->longText('description')
                ->nullable()
                ->comment('Deskripsi kondisi fisik artefak.');

            $table->longText('recommendation')
                ->nullable()
                ->comment('Rekomendasi konservasi/perawatan.');

            $table->enum('priority', [
                'normal',
                'maintenance',
                'urgent',
            ])
                ->default('normal')
                ->index()
                ->comment('Prioritas tindak lanjut konservasi.');

            $table->date('next_review_at')
                ->nullable()
                ->index()
                ->comment('Jadwal pemeriksaan berikutnya.');

            $table->foreignId('asset_id')
                ->nullable()
                ->constrained('digital_assets')
                ->nullOnDelete()
                ->comment('Foto/dokumen pendukung condition report.');

            $table->timestamps();

            $table->index(
                ['museum_item_id', 'inspected_at'],
                'condition_report_item_date_idx'
            );
        });

        Schema::create('location_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('collection_id')
                ->constrained('collections')
                ->cascadeOnDelete()
                ->comment('Koleksi yang dipindahkan.');

            $table->foreignId('from_location_id')
                ->nullable()
                ->constrained('locations')
                ->nullOnDelete()
                ->comment('Lokasi asal.');

            $table->foreignId('to_location_id')
                ->nullable()
                ->constrained('locations')
                ->nullOnDelete()
                ->comment('Lokasi tujuan.');

            $table->foreignId('moved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('User yang mencatat/melakukan perpindahan.');

            $table->dateTime('moved_at')
                ->index()
                ->comment('Tanggal dan waktu perpindahan.');

            $table->string('reason', 255)
                ->nullable()
                ->comment('Alasan perpindahan lokasi.');

            $table->text('notes')
                ->nullable();

            $table->timestamps();

            $table->index(
                ['collection_id', 'moved_at'],
                'location_history_collection_date_idx'
            );
        });

        Schema::create('exhibitions', function (Blueprint $table) {
            $table->id();

            $table->ulid('ulid')
                ->unique()
                ->comment('Public sortable identifier untuk pameran.');

            $table->string('name', 255);

            $table->string('slug', 280)
                ->unique()
                ->comment('Slug unik untuk URL pameran.');

            $table->string('theme', 255)
                ->nullable()
                ->comment('Tema pameran.');

            $table->date('start_date')
                ->nullable()
                ->index();

            $table->date('end_date')
                ->nullable()
                ->index();

            $table->foreignId('location_id')
                ->nullable()
                ->constrained('locations')
                ->nullOnDelete()
                ->comment('Lokasi pameran.');

            $table->longText('description')
                ->nullable();

            $table->enum('status', [
                'planned',
                'active',
                'completed',
                'cancelled',
            ])
                ->default('planned')
                ->index();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(
                ['status', 'start_date', 'end_date'],
                'exhibitions_status_date_idx'
            );
        });

        Schema::create('exhibition_items', function (Blueprint $table) {
            $table->foreignId('exhibition_id')
                ->constrained('exhibitions')
                ->cascadeOnDelete();

            $table->foreignId('museum_item_id')
                ->constrained('museum_items')
                ->cascadeOnDelete();

            $table->enum('status', [
                'displayed',
                'in_transit',
                'stored',
            ])
                ->default('stored')
                ->index()
                ->comment('Status artefak dalam konteks pameran.');

            $table->dateTime('start_at')
                ->nullable()
                ->comment('Mulai artefak dikaitkan/dipamerkan.');

            $table->dateTime('end_at')
                ->nullable()
                ->comment('Akhir artefak dikaitkan/dipamerkan.');

            $table->text('notes')
                ->nullable();

            $table->timestamps();

            $table->primary(['exhibition_id', 'museum_item_id']);

            $table->index(
                ['museum_item_id', 'status'],
                'exhibition_items_museum_status_idx'
            );
        });

        Schema::create('museum_item_relations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('source_museum_item_id')
                ->constrained('museum_items')
                ->cascadeOnDelete()
                ->comment('Artefak sumber relasi.');

            $table->foreignId('target_museum_item_id')
                ->constrained('museum_items')
                ->cascadeOnDelete()
                ->comment('Artefak target relasi.');

            $table->enum('relation_type', [
                'part_of',
                'has_part',
                'related_to',
                'replica_of',
                'replicated_by',
                'same_set_as',
            ])
                ->index()
                ->comment('Jenis relasi antar artefak.');

            $table->string('inverse_relation_type', 80)
                ->nullable()
                ->comment('Tipe relasi balik jika dibuat otomatis di service.');

            $table->text('notes')
                ->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->unique(
                ['source_museum_item_id', 'target_museum_item_id', 'relation_type'],
                'museum_relation_unique'
            );

            $table->index(
                ['target_museum_item_id', 'relation_type'],
                'museum_relation_target_type_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('museum_item_relations');
        Schema::dropIfExists('exhibition_items');
        Schema::dropIfExists('exhibitions');
        Schema::dropIfExists('location_histories');
        Schema::dropIfExists('condition_reports');
    }
}