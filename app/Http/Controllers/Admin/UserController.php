<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Lang;
use Carbon\Carbon;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request, string $lang)
    {
        $query = User::query();

        if ($request->boolean('today')) {
            $query->whereDate('updated_at', Carbon::today());
        } elseif ($request->has('from_date') && $request->has('to_date')) {
            $query->whereDate('updated_at', '>=', $request->input('from_date'))
                ->whereDate('updated_at', '<=', $request->input('to_date'));
        }

        $data = $query->orderByDesc('created_at')->paginate(25)->appends(['lang' => $lang]);

        return view('admin.users.index')->with([
            'pageName' => Lang::t('admin.pages.users.index', 'U,OO�U.Oc OU,U.O3O�OrO_U.USU+'),
            'data' => $data,
            'filters' => $request->only(['from_date', 'to_date', 'today']),
            'lang' => $lang,
        ]);
    }

    public function create(string $lang)
    {
        return view('admin.users.create')->with([
            'pageName' => Lang::t('admin.pages.users.create', 'Create User'),
            'lang' => $lang,
        ]);
    }

    public function store(Request $request, string $lang)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nickname' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'mobile' => ['required', 'string', 'max:255', 'unique:users,mobile'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        User::create([
            'name' => $validated['full_name'],
            'nickname' => $validated['nickname'] ?? null,
            'email' => $validated['email'],
            'mobile' => $validated['mobile'],
            'password' => $validated['password'],
        ]);

        return redirect()
            ->route('admin.users.index', ['lang' => $lang])
            ->with('success', Lang::t('admin.flash.users.created', 'User created successfully.'));
    }
}

