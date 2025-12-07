<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['web'])->group(function () {
    Route::get('/test-rbac', function () {
        if (!Auth::check()) {
            return 'Please log in first';
        }

        $user = Auth::user();

        $output = "Hello {$user->name}!\n\n";
        $output .= "Your roles:\n";
        foreach ($user->roles as $role) {
            $output .= "- {$role->display_name} ({$role->name})\n";
        }

        $output .= "\nYour permissions (first 20):\n";
        foreach ($user->permissions()->take(20) as $permission) {
            $output .= "- {$permission->name}\n";
        }

        $output .= "\nPermission checks:\n";
        $output .= "- Has 'manage_users' permission: " . ($user->hasPermission('manage_users') ? 'Yes' : 'No') . "\n";
        $output .= "- Has 'admin' role: " . ($user->hasRole('admin') ? 'Yes' : 'No') . "\n";
        $output .= "- Is Super Admin: " . ($user->isSuperAdmin() ? 'Yes' : 'No') . "\n";

        return nl2br($output);
    })->name('test.rbac');

    Route::get('/admin-only', function () {
        return 'Welcome Admin! You have access to this protected area.';
    })->middleware('role:admin,super_admin')->name('admin.only');

    Route::get('/manage-users-test', function () {
        return 'You can manage users!';
    })->middleware('permission:manage_users')->name('test.manage_users');

    Route::get('/super-admin-only', function () {
        return 'Welcome Super Admin!';
    })->middleware('role:super_admin')->name('superadmin.only');
});
