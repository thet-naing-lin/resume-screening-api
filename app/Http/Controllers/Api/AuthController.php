<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Services\AuditLogService;

class AuthController extends Controller
{
    /**
     * REGISTER
     * Creates a new user account and returns a token.
     * Default role is 'hr' — only admins are created via seeder.
     */
    public function register(Request $request)
    {
        // Validate incoming data — throws 422 if fails
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed', // needs password_confirmation field
        ]);

        // Create the user — password auto-hashed via model cast
        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // Assign default 'hr' role
        $hrRole = Role::where('name', 'hr')->first();
        if ($hrRole) {
            $user->roles()->attach($hrRole->id);
        }

        // Create Sanctum token — this is what React will store and send
        $token = $user->createToken('auth_token')->plainTextToken;

        // ── Audit log ──
        AuditLogger::log('auth.register', $user, [
            'email'      => $user->email,
            'role'       => 'hr',
            'registered_user_id' => $user->id, // ← explicit backup in metadata
        ]);

        return response()->json([
            'message' => 'Registration successful.',
            'token'   => $token,
            'user'    => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name'),
            ],
        ], 201);
    }

    /**
     * LOGIN
     * Verifies credentials and returns a Sanctum token.
     * React stores this token and sends it in every request header.
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        // Find the user by email
        $user = User::where('email', $validated['email'])->first();

        // Check if user exists AND password matches
        if (!$user || !Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Delete old tokens (force single session per user)
        $user->tokens()->delete();

        // Create a fresh token
        $token = $user->createToken('auth_token')->plainTextToken;

        // Log the login action
        AuditLogger::log('auth.login', $user, [
            'email' => $user->email,
            'login_user_id' => $user->id, // ← explicit backup in metadata
        ]);

        return response()->json([
            'message' => 'Login successful.',
            'token'   => $token,
            'user'    => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name'),
            ],
        ]);
    }

    /**
     * LOGOUT
     * Deletes the current token so it can't be used again.
     * Requires: Authorization: Bearer {token} header.
     */
    public function logout(Request $request)
    {
        // Log the logout action
        AuditLogger::log('auth.logout', $request->user(), [
            'email' => $request->user()->email,
            'logout_user_id' => $request->user()->id, // ← explicit backup in metadata
        ]);

        // Delete only the current token being used
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * ME
     * Returns the currently authenticated user's data.
     * React calls this on app load to check if user is still logged in.
     */
    public function me(Request $request)
    {
        $user = $request->user()->load('roles');

        return response()->json([
            'user' => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name'),
            ],
        ]);
    }
}
