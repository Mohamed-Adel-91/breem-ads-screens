<?php

namespace App\Helpers;

use App\Models\Setting;

class ComponentHelper
{
    /**
     * Get general reusable components.
     *
     * @return array
     */
    public static function generalComponents()
    {
        $setting = Setting::first();
        return [
            'setting' => $setting,
        ];
    }
}
