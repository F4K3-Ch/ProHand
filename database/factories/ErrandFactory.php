<?php

namespace Database\Factories;

use App\Models\Errand;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Errand>
 */
class ErrandFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Errand::class;

    public function definition(): array
    {
        return [
            'client_id' => 3,
            'tasker_id' => null,
            'title' => $this->faker->sentence(4),
            'category' => $this->faker->randomElement(['delivery', 'moving', 'cleaning', 'repair', 'grocery', 'laundry', 'project', 'other']),
            'description' => $this->faker->paragraph(),
            'fee' => $this->faker->randomFloat(2, 100, 2500),
            'deadline' => $this->faker->dateTimeBetween('+1 days', '+14 days'),
            'location' => $this->faker->address(),
            'contact' => $this->faker->phoneNumber(),
            'status' => 'pending', 
            'cancel_requested_by_tasker' => false,
            'cancel_requested_by_client' => false,
        ];
    }
}
