<?php

namespace App\Http\Controllers\Admin;

use App\Exports\NewsletterExport;
use App\Http\Controllers\Controller;
use App\Models\NewsLetter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class NewsLetterController extends Controller
{
    public function index(Request $request)
    {
        $query = NewsLetter::query();

        if ($request->boolean('today')) {
            $query->whereDate('updated_at', Carbon::today());
        }elseif ($request->has('from_date') && $request->has('to_date')) {
            $query->whereDate('updated_at', '>=', $request->from_date)
                ->whereDate('updated_at', '<=', $request->to_date);
        }

        $data = $query->latest()->orderBy('created_at', 'desc')->paginate(25);

        return view('admin.newsletter.index')->with([
            'pageName' => 'قائمة النشرة الإخبارية',
            'data' => $data,
            'filters' => $request->only(['from_date', 'to_date', 'today']),
        ]);
    }

    public function download()
    {
        activity()
            ->causedBy(Auth::guard('admin')->user())
            ->log('Downloaded Newsletter');
        return Excel::download(new NewsletterExport, 'newsletter.xlsx');
    }

    public function destroy(NewsLetter $newsLetter)
    {
        $newsLetter->delete();
        activity()
            ->performedOn($newsLetter)
            ->causedBy(Auth::guard('admin')->user())
            ->withProperties(['email' => $newsLetter->email])
            ->log('Deleted News Letter');
        return redirect()->route('admin.newsletter.index')->with('success', 'تم حذف النشرة الإخبارية بنجاح.');
    }
}
