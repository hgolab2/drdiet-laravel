<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{

    protected $addHttpCookie = true;

   protected $except = [
    'essays/store',
    'save_game_result',
   'login',
    'logout',
    'verifymobile',
    'register',
    'profileedit'
    ];
}
