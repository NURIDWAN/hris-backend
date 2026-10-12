<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperadminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'superadmin@gajipro.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'is_superadmin' => true,
                'company_id' => null,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}
