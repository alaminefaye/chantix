<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckUserVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            
            // Le super admin peut toujours accéder
            if ($user->isSuperAdmin()) {
                return $next($request);
            }
            
            // Vérifier si l'utilisateur est vérifié
            if (!$user->isVerified()) {
                Auth::logout();
                return redirect()->route('login')
                    ->with('error', 'Votre compte n\'a pas encore été validé par l\'administrateur. Veuillez patienter.');
            }
        }

        return $next($request);
    }
}
