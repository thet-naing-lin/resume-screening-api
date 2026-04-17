<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    // GET /api/admin/users
    public function index()
    {
        $users = User::with('roles')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn($user) => [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'role'       => $user->roles->first()?->name ?? 'no role',
                'created_at' => $user->created_at->format('d M Y'),
            ]);

        $roles = Role::pluck('name');

        return response()->json([
            'users' => $users,
            'roles' => $roles,
        ]);
    }

    // PATCH /api/admin/users/{user}/role
    public function assignRole(Request $request, User $user)
    {
        $request->validate([
            'role' => 'required|string|exists:roles,name',
        ]);

        // Prevent changing your own role
        if ($user->id === auth()->id()) {
            return response()->json([
                'message' => 'You cannot change your own role.'
            ], 403);
        }

        $user->syncRoles([$request->role]);

        return response()->json([
            'message' => "{$user->name}'s role updated to {$request->role}.",
            'user'    => [
                'id'   => $user->id,
                'name' => $user->name,
                'role' => $request->role,
            ],
        ]);
    }

    // DELETE /api/admin/users/{user}
    public function destroy(User $user)
    {
        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return response()->json([
                'message' => 'You cannot delete your own account.'
            ], 403);
        }

        $user->delete();

        return response()->json([
            'message' => "{$user->name} has been deleted."
        ]);
    }
}
