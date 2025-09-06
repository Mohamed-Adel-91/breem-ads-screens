<?php

namespace App\Services;


class PagesService
{
    public function home()
    {
        $locale = app()->getLocale();
        return view('web.pages.index')->with('locale', $locale);
    }
}
