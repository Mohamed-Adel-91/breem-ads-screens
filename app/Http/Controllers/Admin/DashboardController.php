<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Lang;


class DashboardController extends Controller
{
    public function index(string $lang)
    {
        $pageName = Lang::t('admin.pages.dashboard.title', 'لوحة التحكم');
        return view('admin.main', [
            'pageName' => $pageName,
            'lang' => $lang,
        ]);
    }
}
