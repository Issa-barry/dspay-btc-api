 <?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    require __DIR__.'/api/auth.php';
    require __DIR__.'/api/users.php';
    require __DIR__.'/api/beneficiaires.php';
    require __DIR__.'/api/agences.php';
    require __DIR__.'/api/devises.php';
    require __DIR__.'/api/permissions.php';
    require __DIR__.'/api/roles.php';
    require __DIR__.'/api/taux.php';
    require __DIR__.'/api/transferts.php';
    require __DIR__.'/api/frais.php';
    require __DIR__.'/api/conversions.php';
    require __DIR__.'/api/payment.php';
});
