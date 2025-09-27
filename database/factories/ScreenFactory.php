<?php

namespace Database\Factories;

use App\Enums\ScreenStatus;
use App\Models\Screen;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Screen>
 */
class ScreenFactory extends Factory
{
    protected $model = Screen::class;

    public function definition(): array
    {
        return [
            'place_id' => PlaceFactory::new(),
            'code' => 'SCR-' . $this->faker->unique()->bothify('####'),
            'device_uid' => $this->faker->uuid(),
            'status' => ScreenStatus::Online->value,
            'last_heartbeat' => now(),
        ];
    }
}

