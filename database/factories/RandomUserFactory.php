<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RandomUserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'first_name' => fake()->firstName(),
            'middle_name' => fake()->optional(0.6)->lastName(),
            'last_name' => fake()->lastName(),
            'password' => Hash::make('password'),
            'profile_picture' => 'uploads/profile_pictures/default.jpg',
            'contact_number' => '09' . fake()->numerify('#########'),
            'role' => fake()->randomElement(['client', 'tasker']),
            'remember_token' => Str::random(10),
        ];
    }

    public function client(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'client',
        ]);
    }

    public function tasker(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'tasker',
        ]);
    }
}