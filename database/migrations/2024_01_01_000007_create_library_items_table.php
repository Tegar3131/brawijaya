<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLibraryItemsTable extends Migration
{
    public function up(): void
    {
        Schema::create('library_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('collection_id')
                ->unique()
                ->constrained('collections')
                ->cascadeOnDelete()
                ->comment('Relasi 1:1 ke collections untuk koleksi unit perpustakaan.');

            $table->enum('bibliographic_level', [
                'monograph',
                'serial',
                'article',
                'thesis',
                'map',
                'manuscript',
            ])
                ->default('monograph')
                ->index()
                ->comment('Level bibliografis MARC/MODS.');

            $table->string('isbn13', 20)
                ->nullable()
                ->unique();

            $table->string('isbn10', 20)
                ->nullable()
                ->index();

            $table->string('issn', 20)
                ->nullable()
                ->index();

            $table->string('doi', 160)
                ->nullable()
                ->index();

            $table->string('publisher_name', 255)
                ->nullable()
                ->index();

            $table->string('publisher_place', 160)
                ->nullable();

            $table->string('edition', 120)
                ->nullable();

            $table->smallInteger('publication_year')
                ->nullable()
                ->index();

            $table->date('publication_date')
                ->nullable();

            $table->string('ddc_classification', 60)
                ->nullable()
                ->index()
                ->comment('Nomor klasifikasi DDC.');

            $table->string('call_number', 120)
                ->nullable()
                ->index()
                ->comment('Nomor panggil rak.');

            $table->string('marc_leader', 40)
                ->nullable();

            $table->string('marc_control_number', 120)
                ->nullable()
                ->index();

            $table->json('marc_raw_json')
                ->nullable()
                ->comment('Representasi MARC 21 hasil parsing.');

            $table->longText('mods_xml')
                ->nullable()
                ->comment('MODS XML untuk interoperabilitas API/export.');

            $table->string('physical_extent', 160)
                ->nullable()
                ->comment('Contoh: xii, 250 halaman.');

            $table->string('physical_dimensions', 120)
                ->nullable()
                ->comment('Contoh: 24 cm.');

            $table->unsignedSmallInteger('pages')
                ->nullable();

            $table->string('illustrations', 255)
                ->nullable();

            $table->string('series_title', 255)
                ->nullable();

            $table->string('source_acquisition', 120)
                ->nullable()
                ->comment('Pembelian, hibah, sumbangan, pertukaran.');

            $table->date('acquired_at')
                ->nullable()
                ->index();

            $table->timestamps();

            $table->index(
                ['publication_year', 'publisher_name'],
                'library_pub_year_publisher_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_items');
    }
}