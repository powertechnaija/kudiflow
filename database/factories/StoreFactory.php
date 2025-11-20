<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Store>
 */
class StoreFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Store $store) {
            Customer::factory()->create([
                'store_id' => $store->id,
                'name' => 'Walk-in Customer',
                'is_default' => true
            ]);
        });
    }
}
