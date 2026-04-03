<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        $query = User::with('roles');

        // Filters
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->has('role')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        if ($request->has('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $users = $query->orderBy('created_at', 'desc')
                       ->paginate($request->get('per_page', 15));

        return response()->json($users);
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'role_ids' => 'required|array|min:1',
            'role_ids.*' => 'exists:roles,id',
            'is_active' => 'boolean',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'is_active' => $validated['is_active'] ?? true,
        ]);

        $user->syncRoles($validated['role_ids']);

        AuditLog::log(
            'create',
            'users',
            auth()->id(),
            'users',
            $user->id,
            null,
            $user->toArray()
        );

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user->load('roles')
        ], 201);
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        return response()->json($user->load('roles'));
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => ['sometimes', 'required', 'email', Rule::unique('users')->ignore($user->id)],
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8|confirmed',
            'role_ids' => 'sometimes|required|array|min:1',
            'role_ids.*' => 'exists:roles,id',
            'is_active' => 'boolean',
            'verified_at' => 'nullable|date',
        ]);

        $oldValues = $user->toArray();

        if (isset($validated['password'])) {
            $user->password = Hash::make($validated['password']);
            unset($validated['password']);
        }

        if (isset($validated['role_ids'])) {
            $user->syncRoles($validated['role_ids']);
            unset($validated['role_ids']);
        }

        $user->update($validated);

        AuditLog::log(
            'update',
            'users',
            auth()->id(),
            'users',
            $user->id,
            $oldValues,
            $user->fresh()->toArray()
        );

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user->fresh()->load('roles')
        ]);
    }

    /**
     * Remove the specified user (soft delete).
     */
    public function destroy(User $user)
    {
        $oldValues = $user->toArray();

        $user->delete();

        AuditLog::log(
            'delete',
            'users',
            auth()->id(),
            'users',
            $user->id,
            $oldValues,
            null
        );

        return response()->json([
            'message' => 'User deleted successfully'
        ]);
    }

    /**
     * Reset user password.
     */
    public function resetPassword(Request $request, User $user)
    {
        $validated = $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->password = Hash::make($validated['password']);
        $user->save();

        AuditLog::log(
            'reset_password',
            'users',
            auth()->id(),
            'users',
            $user->id
        );

        return response()->json([
            'message' => 'Password reset successfully'
        ]);
    }

    /**
     * Bulk delete users.
     */
    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $users = User::whereIn('id', $validated['user_ids'])->get();

        foreach ($users as $user) {
            AuditLog::log(
                'bulk_delete',
                'users',
                auth()->id(),
                'users',
                $user->id,
                $user->toArray(),
                null
            );
            $user->delete();
        }

        return response()->json([
            'message' => count($validated['user_ids']) . ' users deleted successfully'
        ]);
    }

    /**
     * Export users to CSV.
     */
    public function export(Request $request)
    {
        $query = User::with('roles');

        // Apply same filters as index
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $users = $query->get();

        $csvData = "ID,Name,Email,Phone,Roles,Status,Created At\n";

        foreach ($users as $user) {
            $roles = $user->roles->pluck('name')->join(', ');
            $status = $user->is_active ? 'Active' : 'Inactive';
            $csvData .= "{$user->id},\"{$user->name}\",{$user->email},{$user->phone},\"{$roles}\",{$status},{$user->created_at}\n";
        }

        return response($csvData)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="users_' . date('Y-m-d') . '.csv"');
    }
}
