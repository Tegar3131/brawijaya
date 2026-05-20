<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndSettingSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'library.create',
            'library.read',
            'library.update',
            'library.delete',
            'library.import',
            'library.export',

            'museum.create',
            'museum.read',
            'museum.update',
            'museum.delete',
            'museum.import',
            'museum.export',

            'circulation.borrow',
            'circulation.return',
            'circulation.renew',
            'circulation.reserve',
            'circulation.fine.manage',

            'metadata.manage',

            'digital_asset.upload',
            'digital_asset.manage',

            'report.read',
            'report.export',

            'user.manage',
            'role.manage',
            'setting.manage',
            'audit.read',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $admin = Role::firstOrCreate(
            ['name' => 'admin', 'guard_name' => 'web'],
            [
                'display_name' => 'Administrator Sistem',
                'max_borrow_items' => 0,
                'borrow_duration_days' => 0,
                'description' => 'Akses penuh ke seluruh modul SIMPB.',
            ]
        );

        $pustakawan = Role::firstOrCreate(
            ['name' => 'pustakawan', 'guard_name' => 'web'],
            [
                'display_name' => 'Pustakawan',
                'max_borrow_items' => 0,
                'borrow_duration_days' => 0,
                'description' => 'Operator modul perpustakaan dan sirkulasi.',
            ]
        );

        $kurator = Role::firstOrCreate(
            ['name' => 'kurator', 'guard_name' => 'web'],
            [
                'display_name' => 'Kurator Museum',
                'max_borrow_items' => 0,
                'borrow_duration_days' => 0,
                'description' => 'Operator modul museum.',
            ]
        );

        $member = Role::firstOrCreate(
            ['name' => 'member', 'guard_name' => 'web'],
            [
                'display_name' => 'Member',
                'max_borrow_items' => 3,
                'borrow_duration_days' => 7,
                'description' => 'Anggota perpustakaan.',
            ]
        );

        $guest = Role::firstOrCreate(
            ['name' => 'guest', 'guard_name' => 'web'],
            [
                'display_name' => 'Guest',
                'max_borrow_items' => 0,
                'borrow_duration_days' => 0,
                'description' => 'Pengunjung publik.',
            ]
        );

        $admin->syncPermissions(Permission::all());

        $pustakawan->syncPermissions([
            'library.create',
            'library.read',
            'library.update',
            'library.delete',
            'library.import',
            'library.export',
            'circulation.borrow',
            'circulation.return',
            'circulation.renew',
            'circulation.reserve',
            'circulation.fine.manage',
            'metadata.manage',
            'digital_asset.upload',
            'digital_asset.manage',
            'report.read',
            'report.export',
        ]);

        $kurator->syncPermissions([
            'museum.create',
            'museum.read',
            'museum.update',
            'museum.delete',
            'museum.import',
            'museum.export',
            'metadata.manage',
            'digital_asset.upload',
            'digital_asset.manage',
            'report.read',
            'report.export',
        ]);

        $member->syncPermissions([
            'library.read',
            'museum.read',
            'circulation.renew',
            'circulation.reserve',
        ]);

        $guest->syncPermissions([
            'library.read',
            'museum.read',
        ]);

        SystemSetting::updateOrCreate(
            ['key' => 'circulation.default_fine_per_day'],
            [
                'group' => 'circulation',
                'value_json' => [
                    'amount' => 1000,
                    'currency' => 'IDR',
                ],
                'description' => 'Tarif denda keterlambatan per hari.',
                'is_public' => false,
                'updated_by' => null,
            ]
        );

        SystemSetting::updateOrCreate(
            ['key' => 'circulation.reservation_pickup_days'],
            [
                'group' => 'circulation',
                'value_json' => [
                    'days' => 2,
                ],
                'description' => 'Batas hari pengambilan reservasi setelah notifikasi.',
                'is_public' => false,
                'updated_by' => null,
            ]
        );

        SystemSetting::updateOrCreate(
            ['key' => 'watermark.default'],
            [
                'group' => 'watermark',
                'value_json' => [
                    'text' => 'Museum dan Perpustakaan Brawijaya',
                    'position' => 'bottom-right',
                    'opacity' => 0.35,
                ],
                'description' => 'Konfigurasi watermark default untuk aset publik.',
                'is_public' => false,
                'updated_by' => null,
            ]
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}