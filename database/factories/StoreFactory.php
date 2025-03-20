<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class StoreFactory extends Factory
{
    protected $model = Store::class;

    /**
     * @return array
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company,
            'latitude' => $this->faker->latitude(50.0, 58.0),
            'longitude' => $this->faker->longitude(-5.0, 1.0),
            'status' => $this->faker->randomElement(['open', 'closed']),
            'type' => $this->faker->randomElement(['takeaway', 'shop', 'restaurant']),
            'max_delivery_distance' => $this->faker->randomFloat(2, 1, 50),
        ];
    }
}
