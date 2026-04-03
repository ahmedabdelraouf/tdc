<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define all permissions
        $permissions = [
            // Users module
            ['name' => 'users.read', 'module' => 'users', 'action' => 'read'],
            ['name' => 'users.create', 'module' => 'users', 'action' => 'create'],
            ['name' => 'users.update', 'module' => 'users', 'action' => 'update'],
            ['name' => 'users.delete', 'module' => 'users', 'action' => 'delete'],
            
            // Roles module
            ['name' => 'roles.read', 'module' => 'roles', 'action' => 'read'],
            ['name' => 'roles.create', 'module' => 'roles', 'action' => 'create'],
            ['name' => 'roles.update', 'module' => 'roles', 'action' => 'update'],
            ['name' => 'roles.delete', 'module' => 'roles', 'action' => 'delete'],
            
            // Cars module
            ['name' => 'cars.read', 'module' => 'cars', 'action' => 'read'],
            ['name' => 'cars.create', 'module' => 'cars', 'action' => 'create'],
            ['name' => 'cars.update', 'module' => 'cars', 'action' => 'update'],
            ['name' => 'cars.delete', 'module' => 'cars', 'action' => 'delete'],
            
            // Expenses module
            ['name' => 'expenses.read', 'module' => 'expenses', 'action' => 'read'],
            ['name' => 'expenses.create', 'module' => 'expenses', 'action' => 'create'],
            ['name' => 'expenses.update', 'module' => 'expenses', 'action' => 'update'],
            ['name' => 'expenses.delete', 'module' => 'expenses', 'action' => 'delete'],
            
            // Static data module (brands, models, fuel types, etc.)
            ['name' => 'static_data.read', 'module' => 'static_data', 'action' => 'read'],
            ['name' => 'static_data.create', 'module' => 'static_data', 'action' => 'create'],
            ['name' => 'static_data.update', 'module' => 'static_data', 'action' => 'update'],
            ['name' => 'static_data.delete', 'module' => 'static_data', 'action' => 'delete'],
            
            // Audit logs module
            ['name' => 'audit_logs.read', 'module' => 'audit_logs', 'action' => 'read'],
        ];

        // Create permissions
        foreach ($permissions as $permissionData) {
            Permission::firstOrCreate(
                ['name' => $permissionData['name']],
                [
                    'module' => $permissionData['module'],
                    'action' => $permissionData['action'],
                ]
            );
        }

        // Create roles
        $superAdmin = Role::firstOrCreate(
            ['name' => 'Super Admin'],
            ['description' => 'Full access to all modules and actions']
        );

        $admin = Role::firstOrCreate(
            ['name' => 'Admin'],
            ['description' => 'Manage most modules except other admins']
        );

        $editor = Role::firstOrCreate(
            ['name' => 'Editor'],
            ['description' => 'Can create and edit content but not delete']
        );

        $viewer = Role::firstOrCreate(
            ['name' => 'Viewer'],
            ['description' => 'Read-only access to all modules']
        );

        // Assign permissions to Super Admin (all permissions)
        $superAdmin->syncPermissions(Permission::pluck('id')->toArray());

        // Assign permissions to Admin (all except roles management)
        $adminPermissions = Permission::whereNotIn('module', ['roles'])
            ->pluck('id')
            ->toArray();
        $admin->syncPermissions($adminPermissions);

        // Assign permissions to Editor (read + create + update, no delete)
        $editorPermissions = Permission::where('action', '!=', 'delete')
            ->whereNotIn('module', ['roles', 'audit_logs'])
            ->pluck('id')
            ->toArray();
        $editor->syncPermissions($editorPermissions);

        // Assign permissions to Viewer (read only)
        $viewerPermissions = Permission::where('action', 'read')
            ->pluck('id')
            ->toArray();
        $viewer->syncPermissions($viewerPermissions);

        // Create default super admin user if not exists
        $superAdminUser = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Super Admin',
                'email' => 'admin@example.com',
                'password' => Hash::make('password123'),
                'phone' => null,
                'is_active' => true,
                'verified_at' => now(),
            ]
        );

        // Assign Super Admin role to the default user
        $superAdminUser->syncRoles([$superAdmin->id]);

        $this->command->info('Roles and permissions seeded successfully!');
        $this->command->info('Default Super Admin: admin@example.com / password123');
    }
}
