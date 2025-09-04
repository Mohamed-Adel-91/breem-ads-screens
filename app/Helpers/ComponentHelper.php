<?php

namespace App\Helpers;

use App\Models\Brand;
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
        $navbarBrands = Brand::where('show_status', true)
            ->orderBy('name')
            ->get(['id', 'name']);
        return [
            'setting' => $setting,
            'navbarBrands' => $navbarBrands
        ];
    }
}
