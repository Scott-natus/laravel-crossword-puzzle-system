<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class HandleOptionsRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // OPTIONS 요청인 경우 즉시 200 OK 응답
        if ($request->getMethod() === 'OPTIONS') {
            return response()->json([], 200)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN, Accept, Origin, X-Auth-Token, X-API-Key')
                ->header('Access-Control-Max-Age', '86400');
        }

        return $next($request);
    }
} 