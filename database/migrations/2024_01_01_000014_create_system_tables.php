<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateSystemTables extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('User pelaku aktivitas. Null untuk aktivitas sistem.');

            $table->string('auditable_type', 160)
                ->nullable()
                ->comment('Nama model/entity yang diaudit, contoh App\\Models\\Collection.');

            $table->unsignedBigInteger('auditable_id')
                ->nullable()
                ->comment('ID entity yang diaudit.');

            $table->string('module', 80)
                ->index()
                ->comment('Modul: library, museum, circulation, user, role, setting, search.');

            $table->string('action', 80)
                ->index()
                ->comment('Aksi: create, update, delete, login, logout, export, import, denied.');

            $table->string('event', 120)
                ->index()
                ->comment('Nama event yang lebih spesifik.');

            $table->json('old_values')
                ->nullable()
                ->comment('Nilai sebelum perubahan.');

            $table->json('new_values')
                ->nullable()
                ->comment('Nilai setelah perubahan.');

            $table->string('ip_address', 45)
                ->nullable();

            $table->text('user_agent')
                ->nullable();

            $table->string('url', 1000)
                ->nullable();

            $table->json('metadata')
                ->nullable()
                ->comment('Data tambahan, misalnya request id, export format, atau alasan akses ditolak.');

            $table->timestamp('created_at')->useCurrent();

            $table->index(
                ['auditable_type', 'auditable_id'],
                'audit_auditable_idx'
            );

            $table->index(
                ['user_id', 'created_at'],
                'audit_user_created_idx'
            );

            $table->index(
                ['module', 'action', 'created_at'],
                'audit_module_action_created_idx'
            );
        });

        DB::unprepared("
            CREATE TRIGGER audit_logs_prevent_update
            BEFORE UPDATE ON audit_logs FOR EACH ROW
            BEGIN
                SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'audit_logs: immutable, update not allowed';
            END
        ");

        DB::unprepared("
            CREATE TRIGGER audit_logs_prevent_delete
            BEFORE DELETE ON audit_logs FOR EACH ROW
            BEGIN
                SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'audit_logs: immutable, delete not allowed';
            END
        ");

        Schema::create('collection_versions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('collection_id')
                ->constrained('collections')
                ->cascadeOnDelete();

            $table->unsignedInteger('version_no')
                ->comment('Nomor versi koleksi.');

            $table->foreignId('changed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('User yang melakukan perubahan.');

            $table->string('change_reason', 255)
                ->nullable()
                ->comment('Alasan perubahan metadata/koleksi.');

            $table->json('snapshot_json')
                ->comment('Snapshot lengkap record koleksi/metadata.');

            $table->json('diff_json')
                ->nullable()
                ->comment('Perbedaan old/new value.');

            $table->timestamp('created_at')->useCurrent();

            $table->unique(
                ['collection_id', 'version_no'],
                'collection_version_unique'
            );

            $table->index(
                ['collection_id', 'created_at'],
                'collection_version_date_idx'
            );
        });

        Schema::create('import_jobs', function (Blueprint $table) {
            $table->id();

            $table->ulid('ulid')
                ->unique()
                ->comment('Public sortable identifier untuk job import.');

            $table->enum('type', [
                'library_csv',
                'library_marc',
                'museum_csv',
                'metadata_xml',
            ])
                ->index()
                ->comment('Jenis import data.');

            $table->string('source_file_path', 1000)
                ->comment('Path file sumber import.');

            $table->string('original_filename', 255)
                ->comment('Nama file asli saat upload.');

            $table->enum('status', [
                'uploaded',
                'validating',
                'validated',
                'processing',
                'completed',
                'failed',
            ])
                ->default('uploaded')
                ->index();

            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('valid_rows')->default(0);
            $table->unsignedInteger('error_rows')->default(0);
            $table->unsignedInteger('duplicate_rows')->default(0);

            $table->json('summary_json')
                ->nullable()
                ->comment('Ringkasan validasi/import.');

            $table->string('error_report_path', 1000)
                ->nullable()
                ->comment('Path file laporan error import.');

            $table->foreignId('started_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->dateTime('started_at')->nullable();
            $table->dateTime('finished_at')->nullable();

            $table->timestamps();

            $table->index(
                ['type', 'status'],
                'import_jobs_type_status_idx'
            );
        });

        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();

            $table->string('key', 160)
                ->unique()
                ->comment('Key konfigurasi, contoh circulation.default_fine_per_day.');

            $table->string('group', 80)
                ->index()
                ->comment('Grup konfigurasi: circulation, watermark, notification, metadata.');

            $table->json('value_json')
                ->comment('Nilai konfigurasi dalam JSON.');

            $table->text('description')->nullable();

            $table->boolean('is_public')
                ->default(false)
                ->comment('True jika konfigurasi boleh dibaca publik.');

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('type');

            $table->morphs('notifiable');

            $table->text('data');

            $table->timestamp('read_at')
                ->nullable();

            $table->timestamps();

            $table->index('read_at');
        });

        Schema::create('user_bookmarks', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('collection_id')
                ->constrained('collections')
                ->cascadeOnDelete();

            $table->string('folder_name', 120)
                ->nullable()
                ->comment('Folder bookmark opsional.');

            $table->text('notes')
                ->nullable();

            $table->timestamps();

            $table->primary(['user_id', 'collection_id']);
        });

        Schema::create('search_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Null untuk guest/pengunjung publik.');

            $table->string('query', 500)
                ->comment('Keyword pencarian.');

            $table->string('filters', 1000)
                ->nullable()
                ->comment('JSON encoded filter pencarian.');

            $table->unsignedInteger('results_count')
                ->default(0);

            $table->string('collection_type', 20)
                ->nullable()
                ->comment('library, museum, atau tipe koleksi tertentu.');

            $table->string('ip_address', 45)
                ->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index('query');
            $table->index('created_at');

            $table->index(
                ['collection_type', 'created_at'],
                'search_logs_type_date_idx'
            );
        });
    }

    public function down(): void
    {
        DB::unprepared("DROP TRIGGER IF EXISTS audit_logs_prevent_update");
        DB::unprepared("DROP TRIGGER IF EXISTS audit_logs_prevent_delete");

        Schema::dropIfExists('search_logs');
        Schema::dropIfExists('user_bookmarks');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('import_jobs');
        Schema::dropIfExists('collection_versions');
        Schema::dropIfExists('audit_logs');
    }
}