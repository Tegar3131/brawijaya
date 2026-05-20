<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLocationsTable extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('locations')
                ->nullOnDelete()
                ->comment('Parent location untuk struktur lokasi hierarkis.');

            $table->string('code', 80)
                ->unique()
                ->comment('Kode lokasi fisik, misalnya LIB-RK-A1 atau MUS-GD1-RG2-LM3.');

            $table->string('name', 160);

            $table->enum('location_type', [
                'building',
                'room',
                'rack',
                'cabinet',
                'drawer',
                'shelf',
                'box',
                'display_case',
            ])
                ->index()
                ->comment('Jenis lokasi fisik.');

            $table->text('description')->nullable();

            $table->boolean('is_public')
                ->default(false)
                ->comment('True jika lokasi boleh ditampilkan di portal publik.');

            $table->timestamps();

            $table->index(['parent_id', 'location_type'], 'locations_parent_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
}