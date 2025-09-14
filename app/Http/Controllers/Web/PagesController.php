<?php
namespace App\Http\Controllers\Web;
use App\Http\Controllers\Controller;
use App\Services\HomepageService;
use App\Services\WhoWeArePageServices;
use App\Services\ContactUsServices;
use Illuminate\Http\Request;

class PagesController extends Controller
{
    public function __construct(
        private HomepageService $homeItems,
        private WhoWeArePageServices $whoWeAreItems,
        private ContactUsServices $contactUsItems,
    ) {}
    public function index(Request $request)
    {
        return $this->homeItems->index();
    }
    public function whoweare(Request $request)
    {
        return $this->whoWeAreItems->whoweare();
    }
    public function contactUs(Request $request)
    {
        return $this->contactUsItems->contactUs();
    }
}
