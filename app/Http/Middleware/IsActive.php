<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsActive
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
        // Verifica si el usuario está autenticado
        if (!auth()->check()) {
            return redirect()->route('login'); // Redirige si no está autenticado
        }

        $user = auth()->user();

        // Verifica si el usuario está activo
        if (!$user->is_active) {
            return redirect()->route('verification'); // Redirige si no está activo
        }

        return $next($request); // Deja pasar la solicitud si todo está bien
    }
}
