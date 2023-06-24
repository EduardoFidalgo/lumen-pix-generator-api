<?php

namespace App\Http\Middleware;

use Closure;

class CorsMiddleware
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        $response->header('Access-Control-Allow-Origin', '*');
        $response->header('Access-Control-Allow-Methods', 'GET, POST');
        $response->header('Access-Control-Allow-Headers', 'Origin, Content-Type, X-Requested-With, Accept');

        return $response;
    }
}
