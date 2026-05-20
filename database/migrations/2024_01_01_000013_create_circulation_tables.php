<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCirculationTables extends Migration
{
    public function up(): void
    {
        Schema::create('borrowings', function (Blueprint $table) {
            $table->id();

            $table->ulid('ulid')
                ->unique()
                ->comment('Public sortable identifier untuk transaksi peminjaman.');

            $table->string('transaction_code', 80)
                ->unique()
                ->comment('Kode transaksi peminjaman, misalnya BRW-2024-000001.');

            $table->foreignId('member_user_id')
                ->constrained('users')
                ->restrictOnDelete()
                ->comment('User anggota yang meminjam koleksi.');

            $table->foreignId('library_copy_id')
                ->constrained('library_copies')
                ->restrictOnDelete()
                ->comment('Eksemplar fisik yang dipinjam.');

            $table->dateTime('borrowed_at')
                ->index()
                ->comment('Tanggal dan waktu peminjaman.');

            $table->date('due_date')
                ->index()
                ->comment('Tanggal jatuh tempo pengembalian.');

            $table->dateTime('returned_at')
                ->nullable()
                ->index()
                ->comment('Tanggal dan waktu pengembalian.');

            $table->enum('status', [
                'borrowed',
                'returned',
                'overdue',
                'lost',
                'cancelled',
            ])
                ->default('borrowed')
                ->index();

            $table->unsignedTinyInteger('renewal_count')
                ->default(0)
                ->comment('Jumlah perpanjangan yang sudah dilakukan.');

            $table->decimal('fine_amount', 10, 2)
                ->default(0)
                ->comment('Total denda terhitung. Ini single source of truth nilai denda.');

            $table->decimal('fine_paid_amount', 10, 2)
                ->default(0)
                ->comment('Total pembayaran dan waiver yang sudah diterapkan.');

            $table->timestamp('fine_paid_at')
                ->nullable()
                ->comment('Terisi jika denda sudah lunas atau dihapuskan seluruhnya.');

            $table->foreignId('processed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Pustakawan yang memproses peminjaman.');

            $table->foreignId('returned_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Pustakawan yang memproses pengembalian.');

            $table->text('notes')
                ->nullable();

            $table->timestamps();

            $table->index(
                ['member_user_id', 'status'],
                'borrowings_member_status_idx'
            );

            $table->index(
                ['library_copy_id', 'status'],
                'borrowings_copy_status_idx'
            );

            $table->index(
                ['due_date', 'status'],
                'borrowings_due_status_idx'
            );
        });

        Schema::create('borrow_renewals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('borrowing_id')
                ->constrained('borrowings')
                ->cascadeOnDelete();

            $table->foreignId('renewed_by')
                ->constrained('users')
                ->restrictOnDelete()
                ->comment('User yang memproses perpanjangan: member atau pustakawan.');

            $table->date('old_due_date')
                ->comment('Tanggal jatuh tempo sebelum perpanjangan.');

            $table->date('new_due_date')
                ->comment('Tanggal jatuh tempo baru setelah perpanjangan.');

            $table->enum('renewal_method', [
                'online',
                'counter',
            ])
                ->default('counter')
                ->comment('online oleh member, counter oleh pustakawan.');

            $table->text('notes')
                ->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index('borrowing_id');
            $table->index('renewed_by');
        });

        Schema::create('borrow_history', function (Blueprint $table) {
            $table->id();

            $table->foreignId('borrowing_id')
                ->constrained('borrowings')
                ->cascadeOnDelete();

            $table->foreignId('library_copy_id')
                ->nullable()
                ->constrained('library_copies')
                ->nullOnDelete();

            $table->foreignId('member_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->enum('event_type', [
                'borrowed',
                'returned',
                'renewed',
                'fine_calculated',
                'fine_paid',
                'status_changed',
                'lost',
                'cancelled',
            ])
                ->index()
                ->comment('Jenis event dalam siklus sirkulasi.');

            $table->dateTime('event_at')
                ->index();

            $table->string('old_status', 40)
                ->nullable();

            $table->string('new_status', 40)
                ->nullable();

            $table->date('old_due_date')
                ->nullable();

            $table->date('new_due_date')
                ->nullable();

            $table->decimal('amount', 10, 2)
                ->nullable()
                ->comment('Nilai uang terkait event, misalnya denda dihitung/dibayar.');

            $table->foreignId('actor_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('User yang menyebabkan event.');

            $table->text('notes')
                ->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(
                ['borrowing_id', 'event_at'],
                'borrow_history_borrowing_event_idx'
            );
        });

        Schema::create('fines', function (Blueprint $table) {
            $table->id();

            $table->string('fine_code', 80)
                ->unique()
                ->comment('Kode transaksi pembayaran/waiver denda.');

            $table->foreignId('borrowing_id')
                ->constrained('borrowings')
                ->cascadeOnDelete();

            $table->foreignId('member_user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->decimal('paid_amount', 10, 2)
                ->default(0)
                ->comment('Nominal pembayaran per transaksi bayar.');

            $table->decimal('waived_amount', 10, 2)
                ->default(0)
                ->comment('Nominal denda yang dihapuskan pada transaksi ini.');

            $table->enum('status', [
                'unpaid',
                'partially_paid',
                'paid',
                'waived',
            ])
                ->default('unpaid')
                ->index()
                ->comment('Status pembayaran setelah transaksi ini.');

            $table->dateTime('paid_at')
                ->nullable()
                ->index();

            $table->foreignId('processed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('notes')
                ->nullable();

            $table->timestamps();

            $table->index(
                ['borrowing_id', 'status'],
                'fines_borrowing_status_idx'
            );

            $table->index(
                ['member_user_id', 'status'],
                'fines_member_status_idx'
            );
        });

        Schema::create('reservations', function (Blueprint $table) {
            $table->id();

            $table->ulid('ulid')
                ->unique()
                ->comment('Public sortable identifier untuk reservasi.');

            $table->string('code', 80)
                ->unique()
                ->comment('Kode reservasi, misalnya RSV-2024-000001.');

            $table->foreignId('member_user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('Anggota yang membuat reservasi.');

            $table->foreignId('collection_id')
                ->constrained('collections')
                ->cascadeOnDelete()
                ->comment('Reservasi judul/koleksi, bukan library_items.');

            $table->foreignId('library_copy_id')
                ->nullable()
                ->constrained('library_copies')
                ->nullOnDelete()
                ->comment('Diisi jika reservasi sudah dialokasikan ke copy tertentu.');

            $table->unsignedInteger('queue_position')
                ->default(1)
                ->index()
                ->comment('Posisi antrian FIFO.');

            $table->enum('status', [
                'active',
                'notified',
                'fulfilled',
                'cancelled',
                'expired',
            ])
                ->default('active')
                ->index();

            $table->dateTime('reserved_at')
                ->index();

            $table->dateTime('expires_at')
                ->nullable()
                ->index()
                ->comment('Batas waktu pengambilan setelah notifikasi.');

            $table->dateTime('notified_at')
                ->nullable();

            $table->dateTime('fulfilled_at')
                ->nullable();

            $table->timestamps();

            $table->index(
                ['collection_id', 'status', 'queue_position'],
                'reservations_queue_idx'
            );

            $table->index(
                ['member_user_id', 'status'],
                'reservations_member_status_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
        Schema::dropIfExists('fines');
        Schema::dropIfExists('borrow_history');
        Schema::dropIfExists('borrow_renewals');
        Schema::dropIfExists('borrowings');
    }
}