<?php

namespace Database\Factories;

use App\Models\Package;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Package>
 */
class PackageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true).' kit';

        return [
            'name' => ucwords($name),
            'slug' => str($name)->slug(),
            'description' => fake()->paragraph(),
            'image_path' => null,
            'price' => fake()->randomFloat(2, 500, 5000),
            'is_active' => true,
        ];
    }
}
