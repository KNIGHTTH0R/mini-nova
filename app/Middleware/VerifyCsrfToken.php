<?php

namespace App\Middleware;

use Mini\Foundation\Http\Middleware\VerifyCsrfToken as BaseVerifier;


class VerifyCsrfToken extends BaseVerifier
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = array(
        //
    );
}