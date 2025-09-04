<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\NewsLetter;
use Illuminate\Http\Request;

class NewsLetterController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email|unique:news_letter,email',
        ]);

        NewsLetter::create($data);

        return response()->json([
            'message' => 'تم الاشتراك بنجاح',
        ]);
    }
}
