<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;

class RoleSeeder extends Seeder
{
    public function run()
    {
        // ایجاد نقش‌ها
        $roles = [
            'super_admin',
            'nutrition_expert',
            'sales_expert',
            'support',
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        // تخصیص نقش ادمین اصلی به اولین کاربر
        $user = User::first();
        if ($user) {
            $user->assignRole('super_admin');
        }
    }
}
