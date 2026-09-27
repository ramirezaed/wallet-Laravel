<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdministradorMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user('api');

        if ($usuario?->rol !== 'administrador') {
            return new JsonResponse([
                'message' => 'No autorizado. Se requiere rol de administrador.',
                'status' => 403,
                'error' => (object) [],
            ], 403);
        }

        return $next($request);
    }
}
