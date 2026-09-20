<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Models\UserBranchRole;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::query()->updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Admin User',
                'userTypeId' => 1,
                'status' => 'active',
                'password' => Hash::make('12345678'),
            ]
        );

        $superAdminRoleId = Role::query()
            ->where('slug', 'super-admin')
            ->value('id');

        if ($superAdminRoleId !== null) {
            UserBranchRole::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'role_id' => $superAdminRoleId,
                    'branch_id' => null,
                ],
                []
            );
        }
    }
}
