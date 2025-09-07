<?php
namespace App\Http\Controllers\Web;
use App\Http\Controllers\Controller;
use App\Services\PagesService;
use Illuminate\Http\Request;

class PagesController extends Controller
{
    public function __construct(
        private PagesService $pages,
    ) {}
    public function index(Request $request)
    {
        return $this->pages->home();
    }
    public function whoweare(Request $request)
    {
        return $this->pages->whoweare();
    }
    public function contactUs(Request $request)
    {
        return $this->pages->contactUs();
    }
}
