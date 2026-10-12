<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // El usuario con rol 'admin' tiene acceso total a todos los módulos
        if ($user->isAdmin()) {
            return $next($request);
        }

        // Si se requieren roles específicos (ej: 'admin', 'teacher')
        if (! empty($roles) && in_array($user->role, $roles, true)) {
            return $next($request);
        }

        abort(403, 'Acceso restringido: No posees los permisos necesarios para realizar esta acción.');
    }
}
