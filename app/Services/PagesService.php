<?php

namespace App\Services;

class PagesService
{
    public function home()
    {
        return view('web.pages.index');
    }
}
