<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentCompany
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
            
            // Si l'utilisateur change d'entreprise via la requête
            if ($request->has('company_id')) {
                $companyId = $request->get('company_id');
                
                // Vérifier que l'utilisateur appartient à cette entreprise
                if ($user->companies()->where('companies.id', $companyId)->exists()) {
                    $user->current_company_id = $companyId;
                    $user->save();
                }
            }
            
            // Si l'utilisateur n'a pas d'entreprise actuelle, prendre la première
            if (!$user->current_company_id && $user->companies()->exists()) {
                $firstCompany = $user->companies()->first();
                if ($firstCompany) {
                    $user->current_company_id = $firstCompany->id;
                    $user->save();
                }
            }
        }

        return $next($request);
    }
}
