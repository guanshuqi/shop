<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        '/test/',
        '/pay/alipay/notify',
<<<<<<< HEAD
        '/weixin/valid1'
=======
        '/weixin/vaild',
        '/weixin/vaild1'
>>>>>>> weixin
    ];
}
