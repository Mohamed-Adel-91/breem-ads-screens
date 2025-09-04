<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use App\Models\Admin;
use App\Models\AdminOtp;
use App\Mail\OTPMail;

class AuthController extends Controller
{
    public function index()
    {
        if (auth()->guard('admin')->check()) {
            return redirect()->route('admin.index');
        }
        return view('admin.login');
    }

    // public function login(Request $request)
    // {
    //     $this->validate($request, [
    //         'email' => 'required|email',
    //         'password' => 'required',
    //     ]);
    //     $admin = Admin::where('email', $request->email)->first();
    //     if ($admin && Hash::check($request->password, $admin->password)) {
    //         $otp = random_int(100000, 999999);
    //         AdminOtp::updateOrCreate(
    //             ['admin_id' => $admin->id],
    //             [
    //                 'otp_code'   => $otp,
    //                 'expires_at' => Carbon::now()->addMinutes(10),
    //             ]
    //         );
    //         Mail::to($admin->email)->send(new OTPMail($otp));
    //         session(['otp_admin_id' => $admin->id]);
    //         return view('admin.otp');
    //     }
    //     session()->flash('error', 'Credentials are incorrect.');
    //     return back();
    // }

    public function verifyOtp(Request $request)
    {
        $request->validate(['otp' => 'required']);
        $adminId = session('otp_admin_id');
        if (!$adminId) {
            return redirect()->route('admin.login_page');
        }
        $record = AdminOtp::where('admin_id', $adminId)->first();
        if ($record && $record->otp_code === $request->otp) {
            Auth::guard('admin')->loginUsingId($adminId);
            $request->session()->regenerate();
            $request->session()->regenerateToken();
            AdminOtp::where('admin_id', $adminId)->delete();
            session()->forget('otp_admin_id');
            return redirect()->route('admin.index');
        }
        return back()->with('error', 'رمز التحقق غير صحيح.');
    }

    public function login(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required',
        ]);
        if (auth()->guard('admin')->attempt(['email' => $request['email'], 'password' => $request['password']])) {
            $request->session()->regenerate();
            $request->session()->regenerateToken();
            return redirect()->route('admin.index');
        } else {
            session()->flash('error', 'بيانات المدخلة غير صحيحة.');
            return back();
        }
    }

    public function logout(Request $request)
    {
        if (!auth()->guard('admin')->check()) {
            return redirect()->route('admin.login_page');
        }
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login_page');
    }
}
