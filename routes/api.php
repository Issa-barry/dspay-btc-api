<?php

use App\Http\Controllers\Agence\AgenceCreateController;
use App\Http\Controllers\Agence\AgenceDeleteController;
use App\Http\Controllers\Agence\AgenceShowController;
use App\Http\Controllers\Agence\AgenceUpdateController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use Illuminate\Auth\Events\Verified;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\DeviseController;
use App\Http\Controllers\AgenceController;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\User\DeleteUserController;
use App\Http\Controllers\User\ShowUserController;
use App\Http\Controllers\User\updateUserController;
use App\Http\Controllers\User\CreateUserController;

use App\Http\Controllers\ConversionController;
use App\Http\Controllers\Devises\DeviseCreateController;
use App\Http\Controllers\Devises\DeviseDeleteController;
use App\Http\Controllers\Devises\DeviseShowController;
use App\Http\Controllers\Devises\DeviseUpdateController;
use App\Http\Controllers\Frais\FraisCreateController;
use App\Http\Controllers\Frais\FraisDeleteController;
use App\Http\Controllers\Frais\FraisShowController;
use App\Http\Controllers\Frais\FraisUpdateController;
use App\Http\Controllers\Permissions\PermissionController;
use App\Http\Controllers\Roles\RoleAssigneController;
use App\Http\Controllers\Roles\RoleCreateController;
use App\Http\Controllers\Roles\RoleDeleteController;
use App\Http\Controllers\Roles\RoleListeUsersDuRoleController;
use App\Http\Controllers\Roles\RoleShowController;
use App\Http\Controllers\Roles\RoleUpdateController;
use App\Http\Controllers\Transfert\TransfertAnnulerController;
use App\Http\Controllers\Transfert\TransfertDeleteController;
use App\Http\Controllers\Transfert\TransfertEnvoieController;
use App\Http\Controllers\Transfert\TransfertRetraitController;
use App\Http\Controllers\Transfert\TransfertShowController;
use App\Http\Controllers\Transfert\TransfertUpdateController;
use App\Http\Controllers\Roles\RolePermissions\RolePermissionsAssignPermissionController;
use App\Http\Controllers\Roles\RolePermissions\RolePermissionsRevokePermissionController;
use App\Http\Controllers\Roles\RolePermissions\RolePermissionsShowController;
use App\Http\Controllers\Taux\TauxCreateController;
use App\Http\Controllers\Taux\TauxDeleteController;
use App\Http\Controllers\Taux\TauxShowController;
use App\Http\Controllers\Taux\TauxUpdateController;
use App\Http\Controllers\User\UserAffecterAgenceController;
use App\Http\Controllers\User\UserDesacfecterAgenceController;

use App\Http\Controllers\Transfert\TransfertStatistiqueController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/resend-verification-email', [AuthController::class, 'resendVerificationEmail'])->middleware('auth:sanctum');
Route::post('/ResetPassword', [AuthController::class, 'resetPassword']);
Route::post('/sendResetPasswordLink', [AuthController::class, 'sendResetPasswordLink']);
Route::get('/verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail'])->name('verification.verify');
// Route::post('resend-verification-email', [AuthController::class, 'resendVerificationEmail']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/check-token-header', [AuthController::class, 'checkTokenInHeader']);
});

// Route::middleware('auth:sanctum')->group(function () {

// });


Route::get('conversions', [ConversionController::class, 'index']);
Route::post('conversions', [ConversionController::class, 'store']);
Route::get('conversions/{conversion}', [ConversionController::class, 'show']);
Route::put('conversions/{conversion}', [ConversionController::class, 'update']);
Route::delete('conversions/{conversion}', [ConversionController::class, 'destroy']);


/**********************************************************
 *   
 * USER  
 * 
 * ********************************************************/
use App\Http\Controllers\User\Employe\EmployeCreateController;
use App\Http\Controllers\User\Client\ClientCreateController;



// affectation
Route::post('/users/affecterByReference/{id}', [UserAffecterAgenceController::class, 'affecterParReferenceAgence']);
Route::post('/users/affecter-agence/{id}', [UserAffecterAgenceController::class, 'affecterAgence']);
Route::delete('/users/desaffecter-agence/{id}', [UserDesacfecterAgenceController::class, 'desaffecterAgence']);
// Crud
Route::prefix('users')->group(function () {
Route::post('/clients/create', [ClientCreateController::class, 'store']);//client
Route::post('/employes/create', [EmployeCreateController::class, 'store']);//Employe
Route::get('/all', [ShowUserController::class, 'index']);
Route::get('/getById/{id}', [ShowUserController::class, 'getById']);
Route::put('/updateById/{id}', [updateUserController::class, 'updateById']);
Route::delete('/delateById/{id}', [DeleteUserController::class, 'delateById']);
});



use App\Http\Controllers\User\UserStatutController;

Route::patch('/users/{id}/statutUpdate', [UserStatutController::class, 'updateStatut']);

/**********************************************************
 *   
 * BENEFICIAIRE  
 * 
 * ********************************************************/
  use App\Http\Controllers\Beneficiaire\{
    BeneficiaireDeleteController,
    BeneficiaireIndexController,
    BeneficiaireStoreController,
    BeneficiaireUpdateController
};
 
 
Route::middleware(['auth:sanctum', 'throttle:60,1'])->prefix('beneficiaires')->group(function () {
        Route::get('all',   [BeneficiaireIndexController::class, 'index'])->name('index');
        Route::get('getById/{id}', [BeneficiaireIndexController::class, 'getById']);
        Route::post('create', [BeneficiaireStoreController::class, 'store']);
        Route::put('updateById/{id}', [BeneficiaireUpdateController::class, 'updateById']);
        Route::delete('deleteById/{id}', [BeneficiaireDeleteController::class, 'deleteById']);
    });
/**********************************************************
 *   
 * AGENCE 
 * 
 * ********************************************************/
Route::post('/agences/create', [AgenceCreateController::class, 'store']);
Route::get('/agences/all', [AgenceShowController::class, 'index']);
Route::get('/agences/getById/{id}', [AgenceShowController::class, 'show']);
Route::get('/agences/getByReference/{reference}', [AgenceShowController::class, 'showByReference']);
Route::put('/agences/updateById/{id}', [AgenceUpdateController::class, 'updateById']);
Route::delete('/agences/deleteById/{id}', [AgenceDeleteController::class, 'deleteById']);

use App\Http\Controllers\Agence\AgenceStatutController;

Route::patch('/agences/{id}/statutUpdate', [AgenceStatutController::class, 'updateStatut']);



/**********************************************************
 *   
 * DEVISE 
 * 
 * ********************************************************/
Route::post('/devises/create', [DeviseCreateController::class, 'store']);
Route::get('/devises/all', [DeviseShowController::class, 'index']);
Route::get('/devises/getById/{id}', [DeviseShowController::class, 'getById']);
Route::put('/devises/updateById/{id}', [DeviseUpdateController::class, 'updateById']);
Route::delete('/devises/deleteById/{id}', [DeviseDeleteController::class, 'deleteById']);

/**********************************************************
 *   
 * PERMISSIONS 
 * 
 * ********************************************************/
Route::get('/permissions', [PermissionController::class, 'index']);
Route::post('/permissions', [PermissionController::class, 'create']);
Route::get('/permissions/{id}', [PermissionController::class, 'show']);
Route::put('/permissions/{id}', [PermissionController::class, 'update']);
Route::delete('/permissions/{id}', [PermissionController::class, 'destroy']);
//Role permissions : 
Route::post('roles/{roleId}/assign-permissions', [RolePermissionsAssignPermissionController::class, 'assignPermissionsToRole']); // Assigner une ou plusieurs permissions à un rôle
Route::post('roles/{roleId}/revoke-permission', [RolePermissionsRevokePermissionController::class, 'revokePermissionFromRole']); // Retirer une permission d'un rôle
Route::get('/roles-permissions-liste', [RolePermissionsShowController::class, 'listRolesPermissions']); // Lister rôles et permissions
Route::get('/role/{roleId}/oneRolePermissions', [RolePermissionsShowController::class, 'getRolePermissions']); // Route pour récupérer les permissions d'un rôle spécifique


/**********************************************************
 *   
 * TAUX 
 * 
 * ********************************************************/
Route::get('/taux/all', [TauxShowController::class, 'index']);
Route::get('/taux/getById/{id}', [TauxShowController::class, 'getById']);
Route::put('/taux/updateById/{id}', [TauxUpdateController::class, 'updateById']);
Route::delete('/taux/deleteById/{id}', [TauxDeleteController::class, 'deleteById']);
Route::post('/taux/createById', [TauxCreateController::class, 'createById']);
Route::post('/taux/createByName', [TauxCreateController::class, 'storeByName']);

/**********************************************************
 *   
 * ROLE 
 * 
 * ********************************************************/
//partie 1 :
Route::post('/roles/create', [RoleCreateController::class, 'store']);
Route::get('/roles/all', [RoleShowController::class, 'index']);
Route::get('/roles/getById/{id}', [RoleShowController::class, 'getById']);
Route::get('/roles/getByName/{name}', [RoleShowController::class, 'getByName']);
Route::put('/roles/updateById/{id}', [RoleUpdateController::class, 'updateById']);
Route::delete('/roles/deleteById/{id}', [RoleDeleteController::class, 'destroy']);
// Route::apiResource('roles', RoleController::class);

Route::post('/roles/assigne-role', [RoleAssigneController::class, 'assigneRole']);
Route::get('/roles/{id}/all-users-du-role', [RoleListeUsersDuRoleController::class, 'checkRoleUsers']);// fonctionne pas
//Revoke ne marche pas
// Route::post('users/{userId}/revoke-role', [RoleController::class, 'revokeRole']);// Retirer un rôle d'un utilisateur
 

/**********************************************************
 *   
 * TRANSFERT 
 * 
 * ********************************************************/
// Route::post('/transferts/envoie', [TransfertEnvoieController::class, 'store']);
Route::post('/transferts/annuler/{id}', [TransfertAnnulerController::class, 'annulerTransfert']);
Route::post('/transferts/retrait', [TransfertRetraitController::class, 'validerRetrait']);
Route::get('/transferts/all', [TransfertShowController::class, 'index']);
Route::get('/transferts/showById/{id}', [TransfertShowController::class, 'show']);
Route::get('/transferts/showByCode/{code}', [TransfertShowController::class, 'showByCode']);
Route::put('/transferts/updateByCode/{code}', [TransfertUpdateController::class, 'updateByCode']);
Route::put('/transferts/updateById/{id}', [TransfertUpdateController::class, 'updateById']);
Route::put('/transferts/updateByCode/{code}', [TransfertUpdateController::class, 'updateByCode']);
Route::delete('/transferts/deleteById/{id}', [TransfertDeleteController::class, 'deleteById']);
Route::delete('/transferts/deleteByCode/{id}', [TransfertDeleteController::class, 'deleteByCode']);

Route::get('/transferts/statistiques/agence/{agenceId}', [TransfertStatistiqueController::class, 'getSommeTransfertsParAgence']);
Route::get('/transferts/statistiques/globales', [TransfertStatistiqueController::class, 'getStatistiquesGlobales']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/transferts/envoie', [TransfertEnvoieController::class, 'store']);
});


/**********************************************************
 *   
 * FRAIS 
 * 
 * ********************************************************/
Route::get('/frais/all', [FraisShowController::class, 'index']);
Route::get('/frais/getById/{id}', [FraisShowController::class, 'show']);
Route::post('/frais/create', [FraisCreateController::class, 'create']);
Route::put('/frais/updateById/{id}', [FraisUpdateController::class, 'updateById']);
Route::delete('/frais/deleteById/{id}', [FraisDeleteController::class, 'deleteById']);