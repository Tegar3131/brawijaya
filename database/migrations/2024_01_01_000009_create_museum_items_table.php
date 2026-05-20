<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMuseumItemsTable extends Migration
{
    public function up(): void
    {
        Schema::create('museum_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('collection_id')
                ->unique()
                ->constrained('collections')
                ->cascadeOnDelete()
                ->comment('Relasi 1:1 ke collections untuk koleksi unit museum.');

            $table->string('inventory_number', 100)
                ->unique()
                ->comment('Nomor inventaris museum yang unik dan permanen.');

            $table->string('object_name', 255);

            $table->string('object_type_label', 160)
                ->index()
                ->comment('Label display controlled vocabulary.');

            $table->string('object_type_uri', 500)
                ->nullable()
                ->index()
                ->comment('URI Getty AAT atau vocabulary lokal.');

            $table->string('classification', 120)
                ->index()
                ->comment('Senjata, Seragam, Piagam, Foto, Dokumen, Multimedia.');

            $table->string('maker_name', 255)
                ->nullable()
                ->index()
                ->comment('Pembuat/produsen artefak jika diketahui.');

            $table->string('maker_uri', 500)
                ->nullable();

            $table->string('culture', 160)
                ->nullable()
                ->comment('Budaya/bangsa asal artefak.');

            $table->string('period_display', 160)
                ->nullable();

            $table->smallInteger('made_year_start')
                ->nullable()
                ->index();

            $table->smallInteger('made_year_end')
                ->nullable()
                ->index();

            $table->string('material_summary', 500)
                ->nullable()
                ->comment('Cache display material. Filter material memakai tabel pivot pada tahap berikutnya.');

            $table->string('technique_summary', 500)
                ->nullable();

            $table->decimal('height_cm', 10, 2)
                ->nullable();

            $table->decimal('width_cm', 10, 2)
                ->nullable();

            $table->decimal('length_depth_cm', 10, 2)
                ->nullable();

            $table->decimal('weight_gram', 12, 2)
                ->nullable();

            $table->enum('condition_current', [
                'excellent',
                'good',
                'fair',
                'poor',
                'critical',
            ])
                ->default('good')
                ->index()
                ->comment('Kondisi terkini artefak museum.');

            $table->date('condition_checked_at')
                ->nullable()
                ->index();

            $table->text('condition_notes')
                ->nullable();

            $table->longText('provenance_history')
                ->nullable()
                ->comment('Riwayat kepemilikan/perolehan/provenance.');

            $table->string('acquisition_method', 120)
                ->nullable()
                ->index()
                ->comment('Hibah, pembelian, transfer internal, temuan, lainnya.');

            $table->string('acquisition_source', 255)
                ->nullable();

            $table->date('acquisition_date')
                ->nullable()
                ->index();

            $table->boolean('is_sensitive')
                ->default(false)
                ->index()
                ->comment('True untuk artefak/dokumen terbatas.');

            $table->timestamps();

            $table->fullText(
                [
                    'inventory_number',
                    'object_name',
                    'material_summary',
                    'provenance_history',
                ],
                'museum_items_fulltext_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('museum_items');
    }
}