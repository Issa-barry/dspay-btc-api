<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Roles\{
    RoleAssigneController,
    RoleCreateController,
    RoleDeleteController,
    RoleListeUsersDuRoleController,
    RoleShowController,
    RoleUpdateController
};
use App\Http\Controllers\Roles\RolePermissions\{
    RolePermissionsAssignPermissionController,
    RolePermissionsRevokePermissionController,
    RolePermissionsShowController
};

Route::middleware(['auth:sanctum', 'throttle:60,1'])
    ->prefix('roles')->name('roles.')
    ->group(function () {
        // CRUD
        Route::post('/create', [RoleCreateController::class, 'store'])->name('store');
        Route::get('/all', [RoleShowController::class, 'index'])->name('index');
        Route::get('/getById/{id}', [RoleShowController::class, 'getById'])->name('show');
        Route::get('/getByName/{name}', [RoleShowController::class, 'getByName'])->name('showByName');
        Route::put('/updateById/{id}', [RoleUpdateController::class, 'updateById'])->name('update');
        Route::delete('/deleteById/{id}', [RoleDeleteController::class, 'destroy'])->name('delete');

        // Assignations rôle ↔ user
        Route::post('/assigne-role', [RoleAssigneController::class, 'assigneRole'])->name('assign');
        Route::get('/{id}/all-users-du-role', [RoleListeUsersDuRoleController::class, 'checkRoleUsers'])->name('users');

        // Rôle ↔ permissions
        Route::post('/{roleId}/assign-permissions', [RolePermissionsAssignPermissionController::class, 'assignPermissionsToRole'])->name('perm.assign');
        Route::post('/{roleId}/revoke-permission', [RolePermissionsRevokePermissionController::class, 'revokePermissionFromRole'])->name('perm.revoke');
        Route::get('/permissions-liste', [RolePermissionsShowController::class, 'listRolesPermissions'])->name('perm.list');
        Route::get('/{roleId}/permissions', [RolePermissionsShowController::class, 'getRolePermissions'])->name('perm.ofRole');
    });
