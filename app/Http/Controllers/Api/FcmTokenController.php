<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FcmToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class FcmTokenController extends Controller
{
    /**
     * Enregistrer ou mettre à jour un token FCM
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'device_id' => 'nullable|string|max:255',
            'device_type' => 'nullable|string|in:android,ios,web',
            'device_name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Vérifier si le token existe déjà pour cet utilisateur
        $fcmToken = FcmToken::where('user_id', $user->id)
            ->where('token', $request->token)
            ->first();

        if ($fcmToken) {
            // Mettre à jour le token existant
            $fcmToken->update([
                'device_id' => $request->device_id ?? $fcmToken->device_id,
                'device_type' => $request->device_type ?? $fcmToken->device_type,
                'device_name' => $request->device_name ?? $fcmToken->device_name,
                'is_active' => true,
                'last_used_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Token FCM mis à jour avec succès.',
                'data' => $fcmToken,
            ], 200);
        }

        // Vérifier si le token existe pour un autre utilisateur (désactiver l'ancien)
        $existingToken = FcmToken::where('token', $request->token)
            ->where('user_id', '!=', $user->id)
            ->first();

        if ($existingToken) {
            $existingToken->deactivate();
        }

        // Créer un nouveau token
        $fcmToken = FcmToken::create([
            'user_id' => $user->id,
            'token' => $request->token,
            'device_id' => $request->device_id,
            'device_type' => $request->device_type ?? 'android',
            'device_name' => $request->device_name,
            'is_active' => true,
            'last_used_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Token FCM enregistré avec succès.',
            'data' => $fcmToken,
        ], 201);
    }

    /**
     * Supprimer un token FCM
     */
    public function destroy(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        $fcmToken = FcmToken::where('user_id', $user->id)
            ->where('token', $request->token)
            ->first();

        if (!$fcmToken) {
            return response()->json([
                'success' => false,
                'message' => 'Token FCM non trouvé.',
            ], 404);
        }

        // Désactiver plutôt que supprimer
        $fcmToken->deactivate();

        return response()->json([
            'success' => true,
            'message' => 'Token FCM supprimé avec succès.',
        ], 200);
    }

    /**
     * Obtenir tous les tokens actifs de l'utilisateur
     */
    public function index()
    {
        $user = Auth::user();

        $tokens = FcmToken::where('user_id', $user->id)
            ->where('is_active', true)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $tokens,
        ], 200);
    }
}
