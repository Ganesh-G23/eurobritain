<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'user_level' => 1,
            'name' => 'Super Admin',
            'email' => 'admin@gmail.com',
            'password' => '123456',
            'p' => '123456',
            'force_password_change' => false,
        ]);
    }
}
