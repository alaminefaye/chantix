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

        // Vérifier si le token existe déjà pour cet utilisateur
        $existingToken = FcmToken::where('user_id', $user->id)
            ->where('token', $request->token)
            ->first();

        if ($existingToken) {
            // Mettre à jour le token existant pour cet utilisateur
            \Log::info('🔄 Updating existing FCM token', [
                'token_id' => $existingToken->id,
                'user_id' => $user->id,
            ]);
            
            $existingToken->update([
                'device_id' => $request->device_id ?? $existingToken->device_id,
                'device_type' => $request->device_type ?? $existingToken->device_type,
                'device_name' => $request->device_name ?? $existingToken->device_name,
                'is_active' => true,
                'last_used_at' => now(),
            ]);
            $fcmToken = $existingToken;
        } else {
            // Vérifier si le token existe pour un autre utilisateur
            $tokenForOtherUser = FcmToken::where('token', $request->token)
                ->where('user_id', '!=', $user->id)
                ->first();

            if ($tokenForOtherUser) {
                // Mettre à jour le token existant pour le nouvel utilisateur
                \Log::info('🔄 Transferring token to new user', [
                    'old_user_id' => $tokenForOtherUser->user_id,
                    'new_user_id' => $user->id,
                    'token_id' => $tokenForOtherUser->id,
                ]);
                
                $tokenForOtherUser->update([
                    'user_id' => $user->id,
                    'device_id' => $request->device_id ?? $tokenForOtherUser->device_id,
                    'device_type' => $request->device_type ?? $tokenForOtherUser->device_type,
                    'device_name' => $request->device_name ?? $tokenForOtherUser->device_name,
                    'is_active' => true,
                    'last_used_at' => now(),
                ]);
                $fcmToken = $tokenForOtherUser;
            } else {
                // Créer un nouveau token pour cet utilisateur
                \Log::info('➕ Creating new FCM token', [
                    'user_id' => $user->id,
                ]);
                
                try {
                    $fcmToken = FcmToken::create([
                        'user_id' => $user->id,
                        'token' => $request->token,
                        'device_id' => $request->device_id,
                        'device_type' => $request->device_type ?? 'android',
                        'device_name' => $request->device_name,
                        'is_active' => true,
                        'last_used_at' => now(),
                    ]);
                } catch (\Illuminate\Database\QueryException $e) {
                    // Si erreur de duplication (race condition), récupérer le token existant
                    if ($e->getCode() == 23000) {
                        \Log::warning('⚠️ Duplicate token detected (race condition), fetching existing token', [
                            'user_id' => $user->id,
                        ]);
                        $fcmToken = FcmToken::where('token', $request->token)->first();
                        if ($fcmToken) {
                            // Mettre à jour le token pour cet utilisateur
                            $fcmToken->update([
                                'user_id' => $user->id,
                                'device_id' => $request->device_id ?? $fcmToken->device_id,
                                'device_type' => $request->device_type ?? $fcmToken->device_type,
                                'device_name' => $request->device_name ?? $fcmToken->device_name,
                                'is_active' => true,
                                'last_used_at' => now(),
                            ]);
                        } else {
                            throw $e;
                        }
                    } else {
                        throw $e;
                    }
                }
            }
        }

        \Log::info('✅ FCM Token saved successfully', [
            'fcm_token_id' => $fcmToken->id,
            'user_id' => $user->id,
            'is_active' => $fcmToken->is_active,
            'device_type' => $fcmToken->device_type,
            'token_preview' => substr($fcmToken->token, 0, 50) . '...',
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
