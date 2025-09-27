<?php

namespace Database\Factories;

use App\Enums\PlaceType;
use App\Models\Place;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Place>
 */
class PlaceFactory extends Factory
{
    protected $model = Place::class;

    public function definition(): array
    {
        return [
            'name' => [
                'en' => $this->faker->company(),
                'ar' => 'AR ' . $this->faker->company(),
            ],
            'address' => [
                'en' => $this->faker->streetAddress(),
                'ar' => 'AR ' . $this->faker->streetAddress(),
            ],
            'type' => PlaceType::Cafe->value,
        ];
    }
}

