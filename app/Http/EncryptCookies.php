<?php

namespace App\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies as BaseEncryptCookies;

class EncryptCookies extends BaseEncryptCookies
{
    /**
     * Les cookies qui ne doivent pas être encryptés.
     *
     * @var array
     */
    protected $except = [
        //
    ];
}
