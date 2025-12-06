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
        
        \Log::info('FCM Token registration attempt', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'request_data' => $request->all(),
        ]);

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

        // Vérifier si le token existe déjà (peu importe l'utilisateur)
        $existingToken = FcmToken::where('token', $request->token)->first();

        if ($existingToken) {
            // Si le token appartient à un autre utilisateur, le désactiver
            if ($existingToken->user_id != $user->id) {
                $existingToken->deactivate();
                // Créer un nouveau token pour cet utilisateur
                $fcmToken = FcmToken::create([
                    'user_id' => $user->id,
                    'token' => $request->token,
                    'device_id' => $request->device_id,
                    'device_type' => $request->device_type ?? 'android',
                    'device_name' => $request->device_name,
                    'is_active' => true,
                    'last_used_at' => now(),
                ]);
            } else {
                // Mettre à jour le token existant pour cet utilisateur
                $existingToken->update([
                    'device_id' => $request->device_id ?? $existingToken->device_id,
                    'device_type' => $request->device_type ?? $existingToken->device_type,
                    'device_name' => $request->device_name ?? $existingToken->device_name,
                    'is_active' => true,
                    'last_used_at' => now(),
                ]);
                $fcmToken = $existingToken;
            }
        } else {
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
        }

        \Log::info('FCM Token created successfully', [
            'fcm_token_id' => $fcmToken->id,
            'user_id' => $user->id,
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
