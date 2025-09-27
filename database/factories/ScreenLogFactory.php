<?php

namespace Database\Factories;

use App\Enums\ScreenStatus;
use App\Models\ScreenLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\ScreenLog>
 */
class ScreenLogFactory extends Factory
{
    protected $model = ScreenLog::class;

    public function definition(): array
    {
        return [
            'screen_id' => ScreenFactory::new(),
            'current_ad_code' => $this->faker->optional()->bothify('AD-####'),
            'status' => $this->faker->randomElement(ScreenStatus::cases())->value,
            'reported_at' => now(),
        ];
    }
}

