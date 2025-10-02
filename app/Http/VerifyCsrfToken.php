<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * Les URI qui devraient être exclues de la vérification CSRF.
     *
     * @var array
     */
    protected $except = [
        //
        // iba
        'api/v1/payments/stripe/webhook',
        'payments/stripe/webhook', // selon ton chemin exact
    ];

/*Il est également possible que VerifyCsrfToken soit référencé dans un tableau $middlewareGroups comme dans ce cas pour le groupe web :
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\VerifyCsrfToken::class,
            // autres middlewares du groupe 'web'
        ],
    ];
    */
}
