<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVocabularyTermsTable extends Migration
{
    public function up(): void
    {
        Schema::create('vocabulary_terms', function (Blueprint $table) {
            $table->id();

            $table->string('authority_source', 40)
                ->index()
                ->comment('Sumber vocabulary: LCSH, AAT, TGN, ULAN, LCNAF, ISO639-2, LOCAL.');

            $table->string('term_code', 120)
                ->nullable()
                ->index()
                ->comment('Kode istilah jika authority memiliki kode resmi.');

            $table->string('term_uri', 500)
                ->nullable()
                ->index()
                ->comment('URI authority, misalnya Getty AAT atau LCNAF.');

            $table->string('preferred_label', 255);

            $table->json('alt_labels')
                ->nullable()
                ->comment('Label alternatif/sinonim dalam format JSON.');

            $table->text('scope_note')
                ->nullable()
                ->comment('Catatan cakupan penggunaan istilah.');

            $table->foreignId('broader_id')
                ->nullable()
                ->constrained('vocabulary_terms')
                ->nullOnDelete()
                ->comment('Relasi broader-narrower untuk vocabulary hierarkis.');

            $table->json('related_terms')
                ->nullable()
                ->comment('Istilah terkait dalam format JSON.');

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(
                ['authority_source', 'term_uri'],
                'vocab_source_uri_unique'
            );

            $table->index(
                ['authority_source', 'preferred_label'],
                'vocab_source_label_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vocabulary_terms');
    }
}