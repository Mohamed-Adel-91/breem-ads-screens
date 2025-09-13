<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;


class DashboardController extends Controller
{
    public function index(string $lang)
    {
        $pageName = 'لوحة التحكم';
        return view('admin.main', [
            'pageName' => $pageName,
            'lang' => $lang,
        ]);
    }
}
