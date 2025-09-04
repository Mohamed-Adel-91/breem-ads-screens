<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserAuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::guard('user')->check()) {
            return redirect()->route('web.profile');
        }
        return view('web.pages.auth.login');
    }

    public function showSignUpForm()
    {
        if (Auth::guard('user')->check()) {
            return redirect()->route('web.profile');
        }
        return view('web.pages.auth.signup');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'full_name'            => ['required', 'string', 'max:255'],
            'nickname'             => ['nullable', 'string', 'max:255'],
            'email'                => ['required', 'email', 'max:255', 'unique:users,email'],
            'mobile'               => ['required', 'string', 'max:255', 'unique:users,mobile'],
            'password'             => ['required', 'string', 'min:8', 'confirmed'],
        ]);
        $user = User::create([
            'full_name' => $data['full_name'],
            'nickname'  => $data['nickname'] ?? null,
            'email'     => $data['email'],
            'mobile'    => $data['mobile'],
            'password'  => Hash::make($data['password']),
        ]);
        Auth::guard('user')->login($user);
        $request->session()->regenerate();
        $request->session()->regenerateToken();
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'تم إنشاء الحساب وتسجيل الدخول بنجاح',
                'data' => [
                    'full_name' => $user->full_name,
                    'email' => $user->email,
                    'image' => $user->image,
                ],
            ]);
        }
        return redirect()->intended(route('web.profile'))->with('success', 'تم إنشاء الحساب وتسجيل الدخول بنجاح');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);
        if (Auth::guard('user')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            $request->session()->regenerateToken();
            $user = Auth::guard('user')->user();
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'تم تسجيل الدخول بنجاح',
                    'data' => [
                        'full_name' => $user->full_name,
                        'email' => $user->email,
                        'image' => $user->image,
                    ],
                ]);
            }
            return redirect()->intended(route('web.profile'))->with('success', 'تم تسجيل الدخول بنجاح');
        }
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'بيانات الدخول غير صحيحة',
                'errors' => [
                    'email' => ['بيانات الدخول غير صحيحة'],
                ],
            ], 422);
        }
        return back()->withErrors(['email' => 'بيانات الدخول غير صحيحة'])->withInput();
    }

    public function logout(Request $request)
    {
        Auth::guard('user')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('web.login')->with('success', 'تم تسجيل الخروج بنجاح');
    }
}
