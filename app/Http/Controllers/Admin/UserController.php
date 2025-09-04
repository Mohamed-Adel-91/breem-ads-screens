<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->boolean('today')) {
            $query->whereDate('updated_at', Carbon::today());
        } elseif ($request->has('from_date') && $request->has('to_date')) {
            $query->whereDate('updated_at', '>=', $request->input('from_date'))
                ->whereDate('updated_at', '<=', $request->input('to_date'));
        }

        $data = $query->orderByDesc('created_at')->paginate(25);

        return view('admin.users.index')->with([
            'pageName' => 'قائمة المستخدمين',
            'data' => $data,
            'filters' => $request->only(['from_date', 'to_date', 'today']),
        ]);
    }
}
