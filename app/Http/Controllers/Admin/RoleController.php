<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    /**
     * Display a listing of roles.
     */
    public function index()
    {
        $roles = Role::with('permissions')->get();
        return response()->json($roles);
    }

    /**
     * Store a newly created role.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name',
            'description' => 'nullable|string',
            'permission_ids' => 'array',
            'permission_ids.*' => 'exists:permissions,id',
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        if (isset($validated['permission_ids'])) {
            $role->syncPermissions($validated['permission_ids']);
        }

        AuditLog::log(
            'create',
            'roles',
            auth()->id(),
            'roles',
            $role->id,
            null,
            $role->toArray()
        );

        return response()->json([
            'message' => 'Role created successfully',
            'role' => $role->load('permissions')
        ], 201);
    }

    /**
     * Display the specified role.
     */
    public function show(Role $role)
    {
        return response()->json($role->load('permissions', 'users'));
    }

    /**
     * Update the specified role.
     */
    public function update(Request $request, Role $role)
    {
        $oldValues = $role->toArray();

        $validated = $request->validate([
            'name' => 'sometimes|required|string|unique:roles,name,' . $role->id,
            'description' => 'nullable|string',
            'permission_ids' => 'array',
            'permission_ids.*' => 'exists:permissions,id',
        ]);

        if (isset($validated['name'])) {
            $role->name = $validated['name'];
        }

        if (isset($validated['description'])) {
            $role->description = $validated['description'];
        }

        $role->save();

        if (isset($validated['permission_ids'])) {
            $role->syncPermissions($validated['permission_ids']);
        }

        AuditLog::log(
            'update',
            'roles',
            auth()->id(),
            'roles',
            $role->id,
            $oldValues,
            $role->fresh()->toArray()
        );

        return response()->json([
            'message' => 'Role updated successfully',
            'role' => $role->fresh()->load('permissions')
        ]);
    }

    /**
     * Remove the specified role.
     */
    public function destroy(Role $role)
    {
        // Prevent deleting Super Admin role
        if ($role->name === 'Super Admin') {
            return response()->json([
                'message' => 'Cannot delete Super Admin role'
            ], 403);
        }

        $oldValues = $role->toArray();

        $role->delete();

        AuditLog::log(
            'delete',
            'roles',
            auth()->id(),
            'roles',
            $role->id,
            $oldValues,
            null
        );

        return response()->json([
            'message' => 'Role deleted successfully'
        ]);
    }

    /**
     * Get all permissions.
     */
    public function permissions()
    {
        $permissions = Permission::all();
        return response()->json($permissions);
    }

    /**
     * Assign permissions to a role.
     */
    public function assignPermissions(Request $request, Role $role)
    {
        $validated = $request->validate([
            'permission_ids' => 'required|array',
            'permission_ids.*' => 'exists:permissions,id',
        ]);

        $oldPermissionIds = $role->permissions->pluck('id')->toArray();

        $role->syncPermissions($validated['permission_ids']);

        AuditLog::log(
            'update_permissions',
            'roles',
            auth()->id(),
            'roles',
            $role->id,
            ['permission_ids' => $oldPermissionIds],
            ['permission_ids' => $validated['permission_ids']]
        );

        return response()->json([
            'message' => 'Permissions assigned successfully',
            'role' => $role->load('permissions')
        ]);
    }
}
