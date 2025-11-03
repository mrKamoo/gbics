<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserApprovalController extends Controller
{
    /**
     * Get all pending approval users.
     */
    public function pending()
    {
        $users = User::pendingApproval()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'users' => $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'created_at' => $user->created_at,
                ];
            }),
        ]);
    }

    /**
     * Get all users.
     */
    public function index()
    {
        $users = User::with(['roles', 'approvedByUser'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_approved' => $user->is_approved,
                    'approved_at' => $user->approved_at,
                    'approved_by_user' => $user->approvedByUser ? [
                        'id' => $user->approvedByUser->id,
                        'name' => $user->approvedByUser->name,
                    ] : null,
                    'roles' => $user->getRoleNames(),
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ];
            }),
        ]);
    }

    /**
     * Approve a user.
     */
    public function approve(Request $request, User $user)
    {
        if ($user->is_approved) {
            return response()->json([
                'message' => 'User is already approved',
            ], 400);
        }

        $request->validate([
            'role' => 'sometimes|string|exists:roles,name',
        ]);

        $user->update([
            'is_approved' => true,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        // Assign role (default to 'reader' if not specified)
        $role = $request->input('role', 'reader');
        $user->assignRole($role);

        return response()->json([
            'message' => 'User approved successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_approved' => $user->is_approved,
                'role' => $role,
            ],
        ]);
    }

    /**
     * Reject a user (delete).
     */
    public function reject(User $user)
    {
        if ($user->is_approved) {
            return response()->json([
                'message' => 'Cannot reject an approved user',
            ], 400);
        }

        $user->delete();

        return response()->json([
            'message' => 'User rejected and deleted successfully',
        ]);
    }

    /**
     * Update user role.
     */
    public function updateRole(Request $request, User $user)
    {
        $request->validate([
            'role' => 'required|string|exists:roles,name',
        ]);

        $user->syncRoles([$request->role]);

        return response()->json([
            'message' => 'User role updated successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
            ],
        ]);
    }

    /**
     * Delete a user.
     */
    public function destroy(User $user)
    {
        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return response()->json([
                'message' => 'You cannot delete your own account',
            ], 400);
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully',
        ]);
    }
}
