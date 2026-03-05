<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUser extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //Create a superadmin seeder

        User::create([
            'name' => 'adminx',
            'email' => 'fb.admin87@protonmail.com',
            'password' => Hash::make('8sz125jNm643Ppq$$'),
            'is_admin' => true,
        ]);
    }
}
