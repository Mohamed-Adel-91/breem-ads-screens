<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class SocialAuthController extends Controller
{
    // =============== Google ===============
    public function redirectToGoogle()
    {
        return Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email'])
            ->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $g = Socialite::driver('google')->user();
        } catch (InvalidStateException $e) {
            $g = Socialite::driver('google')->stateless()->user();
        }

        $providerId = (string) ($g->id ?? $g->getId());
        $name       = $g->name ?? $g->getName() ?? 'User';
        $email      = $g->email ?? $g->getEmail();

        if (empty($email)) {
            $email = $providerId.'@google.local';
        }

        $user = User::where('email', $email)->first();

        if ($user) {
            if (empty($user->google_id)) {
                $user->google_id = $providerId;
                $user->save();
            }
        } else {
            $user = User::create([
                'full_name'  => $name,
                'email'      => $email,
                'google_id'  => $providerId,
                'password'   => Hash::make(Str::random(16)),
                'mobile' => null,
            ]);
        }

        Auth::guard('user')->login($user, remember: true);

        return redirect()->route('web.profile');
    }

    // =============== Facebook ===============
    public function redirectToFacebook()
    {
        return Socialite::driver('facebook')
            ->scopes(['email'])
            ->redirect();
    }

    public function handleFacebookCallback()
    {
        try {
            $f = Socialite::driver('facebook')->user();
        } catch (InvalidStateException $e) {
            $f = Socialite::driver('facebook')->stateless()->user();
        }

        $providerId = (string) ($f->id ?? $f->getId());
        $name       = $f->name ?? $f->getName() ?? 'User';
        $email      = $f->email ?? $f->getEmail();

        if (empty($email)) {
            $email = $providerId.'@facebook.local';
        }

        $user = User::where('email', $email)->first();

        if ($user) {
            if (empty($user->facebook_id)) {
                $user->facebook_id = $providerId;
                $user->save();
            }
        } else {
            $user = User::create([
                'full_name'   => $name,
                'email'       => $email,
                'facebook_id' => $providerId,
                'password'    => Hash::make(Str::random(16)),
                'mobile' => null,
            ]);
        }

        Auth::guard('user')->login($user, remember: true);
        return redirect()->route('web.profile');
    }
}
