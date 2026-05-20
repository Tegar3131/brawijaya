<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ExtendRolesTable extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('roles', 'display_name')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->string('display_name', 120)
                    ->nullable()
                    ->after('guard_name')
                    ->comment('Nama tampilan role, misalnya Administrator Sistem.');
            });
        }

        if (! Schema::hasColumn('roles', 'max_borrow_items')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->unsignedTinyInteger('max_borrow_items')
                    ->default(0)
                    ->after('display_name')
                    ->comment('Batas jumlah item yang boleh dipinjam oleh role ini.');
            });
        }

        if (! Schema::hasColumn('roles', 'borrow_duration_days')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->unsignedTinyInteger('borrow_duration_days')
                    ->default(0)
                    ->after('max_borrow_items')
                    ->comment('Durasi pinjam default berdasarkan role.');
            });
        }

        if (! Schema::hasColumn('roles', 'description')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->text('description')
                    ->nullable()
                    ->after('borrow_duration_days');
            });
        }
    }

    public function down(): void
    {
        $columns = [];

        foreach ([
            'description',
            'borrow_duration_days',
            'max_borrow_items',
            'display_name',
        ] as $column) {
            if (Schema::hasColumn('roles', $column)) {
                $columns[] = $column;
            }
        }

        if (! empty($columns)) {
            Schema::table('roles', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
}