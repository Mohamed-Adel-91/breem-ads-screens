<?php

namespace App\Services;

class PagesService
{
    public function home()
    {
        return view('web.pages.index');
    }
    public function whoweare()
    {
        return view('web.pages.whoweare');
    }
    public function contactUs()
    {
        return view('web.pages.contact_us');
    }
}
