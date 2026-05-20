<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $admin = User::updateOrCreate(
            ['email' => 'admin@simpb.test'],
            [
                'name' => 'Administrator SIMPB',
                'username' => 'admin',
                'password' => Hash::make('password'),
                'user_type' => 'internal',
                'unit' => 'admin',
                'status' => 'active',
                'membership_status' => 'active',
                'email_verified_at' => now(),
            ]
        );
        $admin->syncRoles(['admin']);

        $pustakawan = User::updateOrCreate(
            ['email' => 'pustakawan@simpb.test'],
            [
                'name' => 'Pustakawan Brawijaya',
                'username' => 'pustakawan',
                'password' => Hash::make('password'),
                'user_type' => 'internal',
                'unit' => 'library',
                'status' => 'active',
                'membership_status' => 'active',
                'email_verified_at' => now(),
            ]
        );
        $pustakawan->syncRoles(['pustakawan']);

        $kurator = User::updateOrCreate(
            ['email' => 'kurator@simpb.test'],
            [
                'name' => 'Kurator Museum Brawijaya',
                'username' => 'kurator',
                'password' => Hash::make('password'),
                'user_type' => 'internal',
                'unit' => 'museum',
                'status' => 'active',
                'membership_status' => 'active',
                'email_verified_at' => now(),
            ]
        );
        $kurator->syncRoles(['kurator']);

        $member = User::updateOrCreate(
            ['email' => 'member@simpb.test'],
            [
                'name' => 'Anggota Demo',
                'username' => 'memberdemo',
                'password' => Hash::make('password'),
                'phone' => '081234567890',
                'address' => 'Malang',
                'user_type' => 'member',
                'unit' => 'public',
                'status' => 'active',
                'member_number' => 'MBR-2024-0001',
                'identity_type' => 'nim',
                'identity_number' => '2409117001',
                'member_category' => 'student',
                'membership_status' => 'active',
                'member_active_until' => now()->addYear()->toDateString(),
                'identity_document_path' => null,
                'member_verified_at' => now(),
                'member_verified_by' => $admin->id,
                'email_verified_at' => now(),
            ]
        );
        $member->syncRoles(['member']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}