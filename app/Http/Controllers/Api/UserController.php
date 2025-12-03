<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Obtenir l'utilisateur actuellement connecté
     */
    public function getCurrentUser(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_super_admin' => $user->is_super_admin,
                'is_verified' => $user->is_verified,
                'current_company_id' => $user->current_company_id,
            ],
        ], 200);
    }
}

