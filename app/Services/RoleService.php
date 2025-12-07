<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Collection;

class RoleService
{
    public function createRole(array $data): Role
    {
        return Role::create($data);
    }

    public function updateRole(Role $role, array $data): Role
    {
        if ($role->is_system) {
            throw new \InvalidArgumentException('Cannot modify system roles.');
        }

        $role->update($data);

        return $role;
    }

    public function deleteRole(Role $role): bool
    {
        if ($role->is_system) {
            throw new \InvalidArgumentException('Cannot delete system roles.');
        }

        return $role->delete();
    }

    public function createPermission(array $data): Permission
    {
        return Permission::create($data);
    }

    public function updatePermission(Permission $permission, array $data): Permission
    {
        if ($permission->is_system) {
            throw new \InvalidArgumentException('Cannot modify system permissions.');
        }

        $permission->update($data);

        return $permission;
    }

    public function deletePermission(Permission $permission): bool
    {
        if ($permission->is_system) {
            throw new \InvalidArgumentException('Cannot delete system permissions.');
        }

        return $permission->delete();
    }

    public function assignPermissionToRole(Role $role, Permission|string $permission): Role
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)->firstOrFail();
        }

        return $role->givePermission($permission);
    }

    public function revokePermissionFromRole(Role $role, Permission|string $permission): Role
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)->firstOrFail();
        }

        return $role->revokePermission($permission);
    }

    public function syncPermissionsToRole(Role $role, array $permissions): Role
    {
        $permissionIds = [];

        foreach ($permissions as $permission) {
            if (is_string($permission)) {
                $perm = Permission::where('name', $permission)->first();
                if ($perm) {
                    $permissionIds[] = $perm->id;
                }
            } elseif ($permission instanceof Permission) {
                $permissionIds[] = $permission->id;
            } else {
                $permissionIds[] = $permission;
            }
        }

        return $role->syncPermissions($permissionIds);
    }

    public function getSystemRoles(): Collection
    {
        return Role::where('is_system', true)->get();
    }

    public function getCustomRoles(): Collection
    {
        return Role::where('is_system', false)->get();
    }

    public function getSystemPermissions(): Collection
    {
        return Permission::where('is_system', true)->get();
    }

    public function getCustomPermissions(): Collection
    {
        return Permission::where('is_system', false)->get();
    }

    public function getRolePermissions(Role $role): Collection
    {
        return $role->permissions;
    }

    public function getPermissionRoles(Permission $permission): Collection
    {
        return $permission->roles;
    }

    public function roleHasPermission(Role $role, string $permissionName): bool
    {
        return $role->hasPermission($permissionName);
    }

    public function createSystemRolesAndPermissions(): void
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

        foreach ($resources as $resource) {
            foreach ($actions as $action) {
                $permissionName = "{$action}_{$resource}";

                Permission::firstOrCreate(
                    ['name' => $permissionName],
                    [
                        'name' => $permissionName,
                        'resource' => $resource,
                        'action' => $action,
                        'description' => ucfirst($action) . ' ' . ucfirst($resource),
                        'is_system' => true,
                    ]
                );
            }
        }

        $allPermissions = Permission::where('is_system', true)->get();

        $superAdminRole->syncPermissions($allPermissions);

        $adminPermissions = Permission::where('is_system', true)
            ->where('name', '!=', 'delete_settings')
            ->where('name', '!=', 'manage_system')
            ->get();

        $adminRole->syncPermissions($adminPermissions);

        $moderatorPermissions = Permission::where('is_system', true)
            ->whereIn('action', ['read', 'update'])
            ->orWhere(function ($query) {
                $query->where('name', 'like', 'read_%')
                      ->orWhere('name', 'like', 'update_%')
                      ->orWhere('name', 'manage_posts')
                      ->orWhere('name', 'manage_comments');
            })
            ->get();

        $moderatorRole->syncPermissions($moderatorPermissions);

        $userPermissions = Permission::where('is_system', true)
            ->where('name', 'like', 'read_%')
            ->orWhere('name', 'create_posts')
            ->orWhere('name', 'update_posts')
            ->orWhere('name', 'delete_posts')
            ->orWhere('name', 'create_comments')
            ->orWhere('name', 'update_comments')
            ->orWhere('name', 'delete_comments')
            ->get();

        $userRole->syncPermissions($userPermissions);
    }
}