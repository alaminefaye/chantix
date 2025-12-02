<?php

namespace Database\Factories;

use App\Models\Material;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class MaterialFactory extends Factory
{
    protected $model = Material::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'unit' => fake()->randomElement(['kg', 'm²', 'm³', 'pièce', 'm', 'L']),
            'unit_price' => fake()->randomFloat(2, 1, 1000),
            'category' => fake()->randomElement(['ciment', 'acier', 'bois', 'électricité', 'plomberie', 'peinture']),
            'min_stock' => fake()->numberBetween(10, 100),
            'stock_quantity' => fake()->numberBetween(0, 500),
            'is_active' => true,
            'supplier' => fake()->company(),
            'reference' => fake()->bothify('MAT-####'),
        ];
    }
}
