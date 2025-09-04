<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\News;

class NewsController extends Controller
{
    public function index()
    {
        $news = News::with('writer')->published()->orderBy('scheduale_date', 'desc')
            ->where('id', '!=', News::with('writer')->published()->orderBy('scheduale_date', 'desc')->first()->id)
            ->paginate(6);
        $last_news = News::with('writer')->published()->orderBy('scheduale_date', 'desc')->first();
        return view('web.pages.news.news')->with([
            'news' => $news,
            'last_news' => $last_news,
        ]);
    }

    public function details($id, $slug)
    {
        $news = News::with('writer')->published()->findOrFail($id);
        $canonical = \unicode_slug($news->title, '-');
        if ($slug !== $canonical) {
            return redirect()->route('web.news.details', [$id, $canonical], 301);
        }
        return view('web.pages.news.news-details', [
            'news' => $news,
        ]);
    }
}
