<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $default_users = [
            [
                'email' => 'admin@gmail.com',
                'first_name' => 'Super',
                'middle_name' => null,
                'last_name' => 'Admin',
                'contact_number' => '09942215875',
                'role' => 'admin'
            ],
            [
                'email' => 'tasker@gmail.com',
                'first_name' => 'Christian',
                'middle_name' => 'Luego',
                'last_name' => 'Mejorada',
                'contact_number' => '09101644142',
                'role' => 'tasker'
            ],
            [
                'email' => 'client@gmail.com',
                'first_name' => 'Iries',
                'middle_name' => 'Fra',
                'last_name' => 'Ra',
                'contact_number' => '09000000000',
                'role' => 'client'
            ]
        ];

        foreach ($default_users as $user) {
            User::create([
                'email'          => $user['email'],
                'first_name'     => $user['first_name'],
                'middle_name'    => $user['middle_name'],
                'last_name'      => $user['last_name'],
                'password'       => Hash::make('123'), 
                'profile_picture'=> 'uploads/profile_pictures/default.jpg',
                'contact_number' => $user['contact_number'],
                'role'           => $user['role'],
            ]);
        }
    }
}