<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            $table->string('name', 160);
            $table->string('username', 80)->unique();
            $table->string('email', 160)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');

            $table->string('phone', 40)->nullable()->index();
            $table->text('address')->nullable();

            $table->enum('user_type', ['internal', 'member', 'guest'])
                ->default('member')
                ->index()
                ->comment('internal untuk staf, member untuk anggota, guest untuk akun publik terbatas bila diperlukan.');

            $table->enum('unit', ['library', 'museum', 'admin', 'public'])
                ->default('public')
                ->index()
                ->comment('Unit kerja atau konteks utama user.');

            $table->enum('status', ['pending', 'active', 'inactive', 'blocked', 'rejected'])
                ->default('pending')
                ->index()
                ->comment('Status login/akses user.');

            $table->string('member_number', 60)
                ->nullable()
                ->unique()
                ->comment('Nomor anggota perpustakaan. Null untuk user internal/non-member.');

            $table->enum('identity_type', ['nim', 'nik', 'nip', 'passport', 'other'])
                ->nullable()
                ->comment('Jenis identitas anggota.');

            $table->string('identity_number', 80)
                ->nullable()
                ->index()
                ->comment('NIM/NIK/NIP/passport anggota.');

            $table->enum('member_category', ['student', 'lecturer', 'staff', 'researcher', 'public'])
                ->nullable()
                ->index()
                ->comment('Kategori keanggotaan untuk kebijakan layanan.');

            $table->enum('membership_status', [
                'pending_verification',
                'active',
                'expired',
                'blocked',
                'rejected',
            ])->default('pending_verification')->index();

            $table->date('member_active_until')
                ->nullable()
                ->index()
                ->comment('Tanggal akhir masa aktif anggota.');

            $table->string('identity_document_path')
                ->nullable()
                ->comment('Path dokumen/foto identitas anggota.');

            $table->timestamp('member_verified_at')->nullable();

            $table->unsignedBigInteger('member_verified_by')
                ->nullable()
                ->comment('Admin/staf yang memverifikasi anggota.');

            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_enabled_at')->nullable();

            $table->timestamp('last_login_at')->nullable()->index();

            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index('name');
            $table->index(['status', 'user_type'], 'users_status_type_idx');
            $table->index(['membership_status', 'member_category'], 'users_membership_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('member_verified_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['member_verified_by']);
        });

        Schema::dropIfExists('users');
    }
}