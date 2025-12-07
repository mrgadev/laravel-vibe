<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RBACSeeder extends Seeder
{
    public function run(): void
    {
        $superAdminRole = Role::firstOrCreate(
            ['name' => 'super_admin'],
            [
                'display_name' => 'Super Admin',
                'description' => 'Super Administrator with full access',
                'is_system' => true,
            ]
        );

        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            [
                'display_name' => 'Administrator',
                'description' => 'Administrator with most permissions',
                'is_system' => true,
            ]
        );

        $moderatorRole = Role::firstOrCreate(
            ['name' => 'moderator'],
            [
                'display_name' => 'Moderator',
                'description' => 'Moderator with limited admin permissions',
                'is_system' => true,
            ]
        );

        $userRole = Role::firstOrCreate(
            ['name' => 'user'],
            [
                'display_name' => 'User',
                'description' => 'Regular user with basic permissions',
                'is_system' => true,
            ]
        );

        $resources = [
            'users', 'roles', 'permissions', 'posts', 'categories',
            'comments', 'settings', 'analytics', 'reports', 'messages'
        ];

        $actions = [
            'create', 'read', 'update', 'delete', 'manage', 'access'
        ];

        $permissions = [];

        foreach ($resources as $resource) {
            foreach ($actions as $action) {
                $permissionName = "{$action}_{$resource}";

                $permission = Permission::firstOrCreate(
                    ['name' => $permissionName],
                    [
                        'name' => $permissionName,
                        'resource' => $resource,
                        'action' => $action,
                        'description' => ucfirst($action) . ' ' . ucfirst($resource),
                        'is_system' => true,
                    ]
                );

                $permissions[] = $permission;
            }
        }

        $systemPermissions = Permission::where('is_system', true)->get();

        $superAdminRole->syncPermissions($systemPermissions->pluck('id')->toArray());

        $adminPermissions = Permission::where('is_system', true)
            ->where('name', '!=', 'delete_settings')
            ->where('name', '!=', 'manage_system')
            ->get();

        $adminRole->syncPermissions($adminPermissions->pluck('id')->toArray());

        $moderatorPermissions = Permission::where('is_system', true)
            ->whereIn('action', ['read', 'update'])
            ->orWhere(function ($query) {
                $query->where('name', 'like', 'read_%')
                      ->orWhere('name', 'like', 'update_%')
                      ->orWhere('name', 'manage_posts')
                      ->orWhere('name', 'manage_comments');
            })
            ->get();

        $moderatorRole->syncPermissions($moderatorPermissions->pluck('id')->toArray());

        $userPermissions = Permission::where('is_system', true)
            ->where('name', 'like', 'read_%')
            ->orWhere('name', 'create_posts')
            ->orWhere('name', 'update_posts')
            ->orWhere('name', 'delete_posts')
            ->orWhere('name', 'create_comments')
            ->orWhere('name', 'update_comments')
            ->orWhere('name', 'delete_comments')
            ->get();

        $userRole->syncPermissions($userPermissions->pluck('id')->toArray());

        if ($user = User::where('email', 'admin@example.com')->first()) {
            $user->giveRole($superAdminRole);
        }

        if ($user = User::where('email', 'user@example.com')->first()) {
            $user->giveRole($userRole);
        }
    }
}