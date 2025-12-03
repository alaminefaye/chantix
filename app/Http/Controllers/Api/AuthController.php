<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Connexion via API
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Les données fournies sont invalides.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Vérifier si l'utilisateur existe
        $user = User::where('email', $request->email)->first();

        // Si l'utilisateur existe mais n'est pas vérifié
        if ($user && !$user->isVerified() && !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Votre compte n\'a pas encore été validé par l\'administrateur. Veuillez patienter jusqu\'à ce que votre compte soit activé.',
            ], 403);
        }

        // Tenter la connexion
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'success' => false,
                'message' => 'Les identifiants fournis ne correspondent pas à nos enregistrements.',
            ], 401);
        }

        $user = Auth::user();
        
        // Définir l'entreprise actuelle si l'utilisateur en a une
        if (!$user->current_company_id && $user->companies()->exists()) {
            $user->current_company_id = $user->companies()->first()->id;
            $user->save();
        }

        // Créer un token Sanctum
        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
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

    /**
     * Inscription via API
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'company_name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Les données fournies sont invalides.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Créer l'utilisateur (non vérifié par défaut)
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_verified' => false, // Doit être validé par le super admin
        ]);

        // Créer l'entreprise
        $company = Company::create([
            'name' => $request->company_name,
            'email' => $request->email,
            'is_active' => true,
        ]);

        // Attacher l'utilisateur à l'entreprise avec le rôle admin
        $adminRole = \App\Models\Role::where('name', 'admin')->first();
        if ($adminRole) {
            $user->companies()->attach($company->id, [
                'role_id' => $adminRole->id,
                'is_active' => true,
                'joined_at' => now(),
            ]);
        }

        $user->current_company_id = $company->id;
        $user->save();

        // Créer un token Sanctum
        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Votre compte a été créé avec succès. Il sera activé après validation par l\'administrateur.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_super_admin' => $user->is_super_admin,
                'is_verified' => $user->is_verified,
                'current_company_id' => $user->current_company_id,
            ],
        ], 201);
    }

    /**
     * Déconnexion via API
     */
    public function logout(Request $request)
    {
        // Supprimer le token actuel
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Déconnexion réussie.',
        ], 200);
    }

    /**
     * Mot de passe oublié
     */
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Les données fournies sont invalides.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // TODO: Implémenter la logique de réinitialisation de mot de passe
        return response()->json([
            'success' => true,
            'message' => 'Un email de réinitialisation a été envoyé.',
        ], 200);
    }

    /**
     * Réinitialiser le mot de passe
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Les données fournies sont invalides.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // TODO: Implémenter la logique de réinitialisation de mot de passe
        return response()->json([
            'success' => true,
            'message' => 'Votre mot de passe a été réinitialisé avec succès.',
        ], 200);
    }
}

