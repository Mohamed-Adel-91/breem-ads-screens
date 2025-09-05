<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $validatedData = $request->validated();

        $user = User::create([
            'full_name' => $validatedData['full_name'],
            'nickname'  => $validatedData['nickname'] ?? null,
            'mobile'    => $validatedData['mobile'],
            'address'   => $validatedData['address'] ?? null,
            'email'     => $validatedData['email'],
            'password'  => Hash::make($validatedData['password']),
        ]);

        if ($request->hasFile('image')) {
            $folder   = 'upload/users/' . $user->id;
            $uploaded = $this->uploadFile([
                $request->file('image')
            ], [
                $folder
            ], [
                'image'
            ]);
            $user->image = $uploaded[0] ?? null;
            $user->save();
        }
        $data['token'] = $user->createToken('user_register_token')->plainTextToken;
        $data['full_name'] = $user->full_name;
        $data['nickname'] = $user->nickname;
        $data['image'] = $user->image_path;
        $data['email'] = $user->email;
        $data['mobile'] = $user->mobile;
        $data['address'] = $user->address;

        return ApiResponse::sendResponse(201, 'تم تسجيل الحساب بنجاح', $data);
    }

    public function login(LoginRequest $request)
    {
        if (Auth::guard('user')->attempt($request->only('email', 'password'))) {
            $currentUser = auth()->guard('user')->user();
            $payload = [
                'token'     => $currentUser->createToken('user_login_token')->plainTextToken,
                'full_name' => $currentUser->full_name,
                'nickname'  => $currentUser->nickname,
                'image'     => $currentUser->image_path,
                'email'     => $currentUser->email,
                'mobile'    => $currentUser->mobile,
                'address'   => $currentUser->address,
            ];
            return ApiResponse::sendResponse(200, 'تم تسجيل الدخول بنجاح', $payload);
        }
        if (!$request->expectsJson() && !$request->is('api/*')) {
            return back()->withErrors(['email' => 'بيانات الدخول غير صحيحة'])->withInput();
        }
        return ApiResponse::sendResponse(401, 'بيانات الدخول غير صحيحة', null);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();

        // Revoke Sanctum token if present
        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        } else {
            if ($bearer = $request->bearerToken()) {
                PersonalAccessToken::findToken($bearer)?->delete();
            }
        }

        // Explicitly log out the user guard
        if (Auth::guard('user')->check()) {
            Auth::guard('user')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        if (!$request->expectsJson() && !$request->is('api/*')) {
            return redirect()->route('web.login')->with('success', 'تم تسجيل الخروج بنجاح');
        }
        return ApiResponse::sendResponse(200, 'تم تسجيل الخروج بنجاح', null);
    }
}
