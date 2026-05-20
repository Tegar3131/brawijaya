<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCategoriesTable extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('categories')
                ->nullOnDelete()
                ->comment('Parent category untuk browse hierarkis.');

            $table->string('name', 140);

            $table->string('slug', 160)
                ->unique()
                ->comment('Slug unik untuk URL dan query kategori.');

            $table->enum('type', [
                'library',
                'museum',
                'general',
                'special_collection',
            ])
                ->default('general')
                ->index()
                ->comment('Domain kategori.');

            $table->text('description')->nullable();

            $table->unsignedSmallInteger('sort_order')
                ->default(0)
                ->comment('Urutan tampil pada browse category.');

            $table->boolean('is_active')
                ->default(true)
                ->index();

            $table->timestamps();

            $table->index(['parent_id', 'type'], 'categories_parent_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
}