<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    /**
     * Redirect to Google OAuth page
     */
    public function redirect()
    {
        return Socialite::driver('google')
            ->stateless()           // required for API usage
            ->with([
                'prompt' => 'select_account',
                'access_type' => 'offline'
            ])
            ->redirect();
    }

    /**
     * Handle Google callback, return token to frontend
     */
    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Exception $e) {
            return redirect(env('FRONTEND_URL') . '/login?error=google_failed');
        }

        // Only allow users already in the system (created by admin)
        $user = User::where('email', $googleUser->getEmail())->first();

        if (!$user) {
            // Not in the system — reject
            return redirect(env('FRONTEND_URL') . '/login?error=not_registered');
        }

        // Link google_id on first Google login
        if (!$user->google_id) {
            $user->update([
                'google_id' => $googleUser->getId(),
                'avatar'    => $googleUser->getAvatar(),
            ]);
        }

        $token = $user->createToken('google-auth-token')->plainTextToken;

        AuditLogger::log('user.login', $user, [
            'method' => 'google',
            'email'  => $user->email,
            'name'   => $user->name,
            'ip'     => request()->ip(),
        ]);

        return redirect(env('FRONTEND_URL') . '/auth/google/callback?token=' . $token);
    }
}
