<?php

namespace App\Services;

use App\Models\Color;
use App\Models\BookingCarClone;

class BookingColorService
{
    public function update(int $bookingCloneId, int $colorId, bool $isSecond = false): array
    {
        $color = Color::findOrFail($colorId);
        $bookingClone = BookingCarClone::findOrFail($bookingCloneId);

        if ($isSecond) {
            $bookingClone->second_color_id = $color->id;
            $bookingClone->second_color_name = $color->name;
        } else {
            $bookingClone->color_id = $color->id;
            $bookingClone->color_name = $color->name;
        }

        $bookingClone->save();

        return ['color_name' => $color->name, 'color_id' => $color->id];
    }
}
