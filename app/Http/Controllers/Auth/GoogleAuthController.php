<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        $googleUser = Socialite::driver('google')->user();

        $user = User::where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        $isNewUser = ! $user;

        if ($isNewUser) {
            $user = User::create([
                'google_id' => $googleUser->getId(),
                'nama_lengkap' => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
                'avatar' => $googleUser->getAvatar(),
                'id_instansi' => null,
                'nim' => null,
                'sekolah' => null,
                'no_hp' => null,
            ]);
        } else {
            $user->update([
                'google_id' => $user->google_id ?? $googleUser->getId(),
                'avatar' => $googleUser->getAvatar() ?? $user->avatar,
            ]);
        }

        // Assign student role to new users without a role
        if ($isNewUser || $user->roles->isEmpty()) {
            $user->assignRole('student');
        }

        Auth::login($user);

        // Redirect based on role
        if ($user->hasRole('super_admin')) {
            return redirect()->route('superadmin.dashboard');
        } elseif ($user->hasRole('agency_admin')) {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->route('home');
    }
}
