<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class IsVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->cookie('jwt');

            if (!$token) {
                return redirect()->route('login')->with('error', 'Token no encontrado. Inicia sesión.');
            }

            JWTAuth::setToken($token);
            $user = JWTAuth::authenticate();            

            if (!$user || !$user->is_verified || !$user->is_active) { 
                return redirect()->route('login')->with('error', 'Acceso denegado.');
            }

        return $next($request);
    }
}
