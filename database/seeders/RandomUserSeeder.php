<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Factories\RandomUserFactory;

class RandomUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        RandomUserFactory::new()->count(10)->client()->create();
        RandomUserFactory::new()->count(10)->tasker()->create();
    }
}