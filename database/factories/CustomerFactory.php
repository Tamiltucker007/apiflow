<?php

namespace Database\Factories;

use App\Models\Merchant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'merchant_id' => Merchant::factory(),
            'name' => fake()->unique()->company(),
            'email' => fake()->unique()->companyEmail(),
            'phone' => null,
            'metadata' => null,
            'is_active' => true,
        ];
    }
}
