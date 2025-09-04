<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Exports\ActivityLogsExport;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = Activity::with('causer');

        if ($request->boolean('today')) {
            $query->whereDate('updated_at', Carbon::today());
        }elseif ($request->has('from_date') && $request->has('to_date')) {
            $query->whereDate('updated_at', '>=', $request->from_date)
                ->whereDate('updated_at', '<=', $request->to_date);
        }


        $data = $query->orderByDesc('created_at')
            ->paginate(25);

        return view('admin.activity_logs.index')->with([
            'pageName' => 'سجلات النشاط',
            'data' => $data,
            'filters' => [
                'from_date' => $request->from_date,
                'to_date' => $request->to_date,
                'today' => $request->today,
            ],
        ]);
    }

    public function download(Request $request)
    {
        activity()
            ->causedBy(Auth::guard('admin')->user())
            ->log('Downloaded Activity Logs');

        $filters = $request->only(['from_date', 'to_date', 'today']);

        return Excel::download(new ActivityLogsExport($filters), 'activity_logs.xlsx');
    }
}
