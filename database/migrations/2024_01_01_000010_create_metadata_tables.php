<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMetadataTables extends Migration
{
    public function up(): void
    {
        Schema::create('creators', function (Blueprint $table) {
            $table->id();

            $table->string('name', 255)
                ->comment('Nama asli creator/author/maker/contributor.');

            $table->string('normalized_name', 255)
                ->index()
                ->comment('Nama ternormalisasi untuk pencarian dan deduplikasi.');

            $table->string('authority_source', 40)
                ->nullable()
                ->index()
                ->comment('LCNAF, ULAN, VIAF, LOCAL.');

            $table->string('authority_uri', 500)
                ->nullable()
                ->index()
                ->comment('URI authority file jika tersedia.');

            $table->string('birth_death_dates', 120)
                ->nullable()
                ->comment('Contoh: 1930-1985.');

            $table->text('biography')
                ->nullable();

            $table->timestamps();

            $table->fullText(
                ['name', 'normalized_name', 'biography'],
                'creators_fulltext_idx'
            );
        });

        Schema::create('collection_creator', function (Blueprint $table) {
            $table->foreignId('collection_id')
                ->constrained('collections')
                ->cascadeOnDelete();

            $table->foreignId('creator_id')
                ->constrained('creators')
                ->cascadeOnDelete();

            $table->string('role', 80)
                ->default('author')
                ->index()
                ->comment('author, editor, translator, maker, photographer, supervisor, contributor.');

            $table->unsignedSmallInteger('sort_order')
                ->default(0)
                ->comment('Urutan tampil creator.');

            $table->boolean('is_primary')
                ->default(false)
                ->comment('Penanda creator utama.');

            $table->text('notes')
                ->nullable();

            $table->timestamps();

            $table->primary(['collection_id', 'creator_id', 'role']);
            $table->index(['creator_id', 'role'], 'collection_creator_creator_role_idx');
        });

        Schema::create('subjects', function (Blueprint $table) {
            $table->id();

            $table->string('term', 255)
                ->comment('Istilah subjek/tajuk/topik.');

            $table->string('slug', 280)
                ->index();

            $table->string('vocabulary_source', 40)
                ->default('LOCAL')
                ->index()
                ->comment('LCSH, AAT, TGN, LOCAL.');

            $table->string('authority_uri', 500)
                ->nullable()
                ->index()
                ->comment('URI authority jika subjek berasal dari controlled vocabulary.');

            $table->foreignId('broader_id')
                ->nullable()
                ->constrained('subjects')
                ->nullOnDelete()
                ->comment('Relasi broader-narrower antar subjek.');

            $table->enum('type', [
                'topic',
                'geographic',
                'temporal',
                'person',
                'organization',
                'event',
            ])
                ->default('topic')
                ->index();

            $table->text('scope_note')
                ->nullable();

            $table->timestamps();

            $table->unique(
                ['vocabulary_source', 'authority_uri'],
                'subjects_source_uri_unique'
            );

            $table->fullText(
                ['term', 'scope_note'],
                'subjects_fulltext_idx'
            );
        });

        Schema::create('collection_subject', function (Blueprint $table) {
            $table->foreignId('collection_id')
                ->constrained('collections')
                ->cascadeOnDelete();

            $table->foreignId('subject_id')
                ->constrained('subjects')
                ->cascadeOnDelete();

            $table->string('subject_type', 60)
                ->default('primary')
                ->index()
                ->comment('primary, secondary, local, geographic, temporal.');

            $table->unsignedSmallInteger('sort_order')
                ->default(0);

            $table->timestamps();

            $table->primary(['collection_id', 'subject_id']);
            $table->index(['subject_id', 'subject_type'], 'collection_subject_subject_type_idx');
        });

        Schema::create('metadata_elements', function (Blueprint $table) {
            $table->id();

            $table->string('standard', 40)
                ->index()
                ->comment('dc, marc21, mods, cdwa_lite, vra_core, isad_g, mets, premis.');

            $table->string('element_key', 120)
                ->comment('Contoh: dc.title, marc.245.a, cdwa.material.medium.');

            $table->string('label', 160)
                ->comment('Nama label yang tampil di form.');

            $table->enum('data_type', [
                'string',
                'text',
                'integer',
                'decimal',
                'date',
                'datetime',
                'json',
                'boolean',
                'uri',
            ])
                ->default('string');

            $table->boolean('is_repeatable')
                ->default(false)
                ->comment('True jika elemen boleh muncul lebih dari satu kali.');

            $table->boolean('is_required')
                ->default(false)
                ->comment('True jika wajib diisi untuk standar/tipe terkait.');

            $table->enum('applies_to', [
                'all',
                'collection',
                'library',
                'museum',
                'digital_asset',
            ])
                ->default('all')
                ->index();

            $table->string('vocabulary_source', 80)
                ->nullable()
                ->comment('Authority vocabulary jika elemen memakai controlled vocabulary.');

            $table->unsignedSmallInteger('sort_order')
                ->default(0);

            $table->text('help_text')
                ->nullable();

            $table->timestamps();

            $table->unique(
                ['standard', 'element_key', 'applies_to'],
                'metadata_element_unique'
            );

            $table->index(
                ['standard', 'applies_to'],
                'metadata_standard_applies_idx'
            );
        });

        Schema::create('item_metadata', function (Blueprint $table) {
            $table->id();

            $table->foreignId('collection_id')
                ->constrained('collections')
                ->cascadeOnDelete();

            $table->foreignId('metadata_element_id')
                ->constrained('metadata_elements')
                ->cascadeOnDelete();

            $table->boolean('is_repeatable_field')
                ->default(true)
                ->comment('Denormalized dari metadata_elements.is_repeatable untuk validasi aplikasi.');

            $table->string('value_string', 1000)
                ->nullable();

            $table->longText('value_text')
                ->nullable();

            $table->bigInteger('value_integer')
                ->nullable();

            $table->decimal('value_decimal', 18, 4)
                ->nullable();

            $table->date('value_date')
                ->nullable();

            $table->dateTime('value_datetime')
                ->nullable();

            $table->json('value_json')
                ->nullable();

            $table->string('language_code', 10)
                ->nullable()
                ->index()
                ->comment('Kode bahasa nilai metadata, jika relevan.');

            $table->string('authority_uri', 500)
                ->nullable()
                ->index()
                ->comment('URI authority untuk value metadata, jika ada.');

            $table->unsignedSmallInteger('sort_order')
                ->default(0)
                ->comment('Urutan untuk metadata repeatable.');

            $table->string('source', 80)
                ->nullable()
                ->comment('manual, import_marc, import_csv, auto_dc_mapping.');

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->unique(
                ['collection_id', 'metadata_element_id', 'sort_order'],
                'item_metadata_unique_sort'
            );

            $table->index(
                ['collection_id', 'metadata_element_id'],
                'item_metadata_lookup_idx'
            );

            $table->index(
                ['metadata_element_id', 'value_date'],
                'item_metadata_date_idx'
            );

            $table->index(
                ['metadata_element_id', 'value_integer'],
                'item_metadata_integer_idx'
            );

            $table->fullText(
                ['value_string', 'value_text'],
                'item_metadata_fulltext_idx'
            );
        });

        Schema::create('materials', function (Blueprint $table) {
            $table->id();

            $table->string('name', 200)
                ->comment('Nama material/teknik dalam bahasa Indonesia.');

            $table->string('name_en', 200)
                ->nullable()
                ->comment('Nama material/teknik dalam bahasa Inggris.');

            $table->string('authority_uri', 500)
                ->nullable()
                ->comment('URI Getty AAT jika tersedia.');

            $table->enum('type', ['material', 'technique'])
                ->comment('Material fisik atau teknik pembuatan.');

            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();

            $table->index(['name', 'type'], 'materials_name_type_idx');
            $table->index('authority_uri');
        });

        Schema::create('museum_item_materials', function (Blueprint $table) {
            $table->foreignId('museum_item_id')
                ->constrained('museum_items')
                ->cascadeOnDelete();

            $table->foreignId('material_id')
                ->constrained('materials')
                ->cascadeOnDelete();

            $table->boolean('is_primary')
                ->default(false)
                ->comment('True jika material utama artefak.');

            $table->primary(['museum_item_id', 'material_id']);
            $table->index(['material_id', 'is_primary'], 'museum_item_materials_material_primary_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('museum_item_materials');
        Schema::dropIfExists('materials');
        Schema::dropIfExists('item_metadata');
        Schema::dropIfExists('metadata_elements');
        Schema::dropIfExists('collection_subject');
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('collection_creator');
        Schema::dropIfExists('creators');
    }
}