<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ForceRefreshProjects
{
    /**
     * Handle an incoming request.
     * Force le rafraîchissement des projets des invitations pour éviter les problèmes de cache
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Si on est sur une page d'invitations, forcer le rafraîchissement
        if ($request->is('companies/*/invitations*')) {
            // Vider le cache de la requête si possible
            if (method_exists($request, 'flush')) {
                $request->flush();
            }
        }

        return $response;
    }
}
