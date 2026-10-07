<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'name' => ucwords($name),
            'slug' => str($name)->slug(),
            'category' => fake()->randomElement(['Ayurvedic Medicine', 'Hair Oil', 'Mass Gainer', 'Pain Relief Oil', 'Herbal Syrup']),
            'description' => fake()->paragraph(),
            'image_path' => null,
            'price' => fake()->randomFloat(2, 100, 2000),
            'is_active' => true,
        ];
    }
}
