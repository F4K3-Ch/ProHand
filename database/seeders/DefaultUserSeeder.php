<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DefaultUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $default_users = [
            [
                'email' => 'admin@example-email.com',
                'first_name' => 'Super',
                'middle_name' => null,
                'last_name' => 'Admin',
                'contact_number' => '09000000001',
                'role' => 'admin'
            ],
            [
                'email' => 'admin2@example-email.com',
                'first_name' => 'Super',
                'middle_name' => null,
                'last_name' => 'C',
                'contact_number' => '09000000002',
                'role' => 'admin'
            ],
            [
                'email' => 'tasker@example-email.com',
                'first_name' => 'Christian',
                'middle_name' => 'Luego',
                'last_name' => 'Mejorada',
                'contact_number' => '09942215875',
                'role' => 'tasker'
            ],
            [
                'email' => 'tasker2@example-email.com',
                'first_name' => 'Christian',
                'middle_name' => 'Medavillo',
                'last_name' => 'Luego',
                'contact_number' => '09101644142',
                'role' => 'tasker'
            ],
            [
                'email' => 'client@example-email.com',
                'first_name' => 'Iries',
                'middle_name' => 'Fra',
                'last_name' => 'Ra',
                'contact_number' => '09000000000',
                'role' => 'client'
            ],
            [
                'email' => 'client2@example-email.com',
                'first_name' => 'Irish',
                'middle_name' => 'Rue',
                'last_name' => 'La',
                'contact_number' => '09000000003',
                'role' => 'client'
            ],
        ];

        foreach ($default_users as $user) {
            User::create([
                'email'          => $user['email'],
                'email_verified_at' => now(),
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
