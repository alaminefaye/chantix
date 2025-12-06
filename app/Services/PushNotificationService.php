<?php

namespace App\Services;

use App\Models\User;
use App\Models\Project;
use App\Models\Company;
use App\Models\Role;
use App\Models\FcmToken;
use App\Models\Notification as NotificationModel;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    protected $messaging;

    public function __construct()
    {
        try {
            $firebaseCredentialsPath = config('services.firebase.credentials_path');
            
            if (!$firebaseCredentialsPath || !file_exists($firebaseCredentialsPath)) {
                Log::error('❌ Firebase credentials file not found at: ' . $firebaseCredentialsPath);
                return;
            }

            Log::info('🔧 Initializing Firebase Messaging', [
                'credentials_path' => $firebaseCredentialsPath,
                'file_exists' => file_exists($firebaseCredentialsPath),
            ]);

            $factory = (new Factory)
                ->withServiceAccount($firebaseCredentialsPath);

            $this->messaging = $factory->createMessaging();
            
            if ($this->messaging) {
                Log::info('✅ Firebase Messaging initialized successfully');
            } else {
                Log::error('❌ Firebase Messaging initialization returned null');
            }
        } catch (\Exception $e) {
            Log::error('❌ Failed to initialize Firebase: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Envoyer une notification à un utilisateur
     */
    public function sendToUser($userId, $title, $body, $data = [])
    {
        if (!$this->messaging) {
            Log::warning('Firebase messaging not initialized');
            return false;
        }

        $tokens = FcmToken::where('user_id', $userId)
            ->where('is_active', true)
            ->pluck('token')
            ->toArray();

        if (empty($tokens)) {
            Log::info("No active FCM tokens found for user {$userId}");
            return false;
        }

        return $this->sendToTokens($tokens, $title, $body, $data);
    }

    /**
     * Envoyer une notification à plusieurs utilisateurs
     */
    public function sendToUsers(array $userIds, $title, $body, $data = [])
    {
        if (empty($userIds)) {
            Log::warning("📭 sendToUsers called with empty user IDs");
            return false;
        }

        if (!$this->messaging) {
            Log::error('❌ Firebase messaging not initialized - cannot send push notifications');
            return false;
        }

        Log::info("🔍 Looking for FCM tokens for users: " . implode(', ', $userIds));

        // Récupérer tous les tokens (actifs et inactifs) pour debug
        $allTokens = FcmToken::whereIn('user_id', $userIds)->get();
        Log::info("📱 Total FCM tokens found (all status): " . $allTokens->count(), [
            'tokens' => $allTokens->map(function($token) {
                return [
                    'user_id' => $token->user_id,
                    'is_active' => $token->is_active,
                    'token_preview' => substr($token->token, 0, 50) . '...',
                ];
            })->toArray(),
        ]);

        // Récupérer uniquement les tokens actifs
        $tokens = FcmToken::whereIn('user_id', $userIds)
            ->where('is_active', true)
            ->pluck('token')
            ->toArray();

        Log::info("✅ Active FCM tokens found: " . count($tokens), [
            'token_count' => count($tokens),
            'user_ids' => $userIds,
        ]);

        if (empty($tokens)) {
            Log::warning("⚠️ No active FCM tokens found for users: " . implode(', ', $userIds));
            // Ne pas retourner false ici, car les notifications en base ont été créées
            // On retourne true pour indiquer que le processus s'est bien déroulé
            return true;
        }

        Log::info("📤 Sending push notifications to " . count($tokens) . " tokens");
        $result = $this->sendToTokens($tokens, $title, $body, $data);
        Log::info("📬 Push notification send result: " . ($result ? 'success' : 'failed'));
        
        return $result;
    }

    /**
     * Envoyer une notification à tous les utilisateurs d'une entreprise
     */
    public function sendToCompany($companyId, $title, $body, $data = [])
    {
        $userIds = User::where('current_company_id', $companyId)
            ->orWhereHas('companies', function($query) use ($companyId) {
                $query->where('companies.id', $companyId);
            })
            ->pluck('id')
            ->toArray();

        if (empty($userIds)) {
            Log::info("No users found for company {$companyId}");
            return false;
        }

        return $this->sendToUsers($userIds, $title, $body, $data);
    }

    /**
     * Envoyer une notification à des tokens spécifiques
     */
    protected function sendToTokens(array $tokens, $title, $body, $data = [])
    {
        if (empty($tokens)) {
            Log::warning("📭 sendToTokens called with empty tokens array");
            return false;
        }

        Log::info("🚀 Starting to send push notifications", [
            'token_count' => count($tokens),
            'title' => $title,
            'body' => $body,
        ]);

        try {
            $notification = Notification::create($title, $body);
            
            // Créer le message avec notification et données
            $message = CloudMessage::new()
                ->withNotification($notification)
                ->withData($data);
            
            // Configuration Android (les méthodes acceptent directement un tableau)
            try {
                $message = $message->withAndroidConfig([
                    'priority' => 'high',
                    'notification' => [
                        'sound' => 'default',
                        'channel_id' => 'chantix_notifications',
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    ],
                ]);
            } catch (\Exception $e) {
                Log::warning("⚠️ Error setting Android config: " . $e->getMessage());
            }
            
            // Configuration iOS (APNS) - Important pour que les notifications s'affichent
            // Note: Ne pas mettre 'alert' dans le payload car withNotification() le gère déjà
            // Pour iOS, on doit spécifier apns-push-type: 'alert' dans les headers
            try {
                $message = $message->withApnsConfig([
                    'headers' => [
                        'apns-priority' => '10',
                        'apns-push-type' => 'alert', // Important pour iOS 13+
                    ],
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                            'badge' => 1,
                            'content-available' => 1,
                        ],
                    ],
                ]);
                Log::info("✅ APNS config set successfully with push-type: alert");
            } catch (\Exception $e) {
                Log::warning("⚠️ Error setting APNS config: " . $e->getMessage());
            }
            
            Log::info("📨 Message prepared", [
                'title' => $title,
                'body' => $body,
                'data_keys' => array_keys($data),
            ]);

            $results = [];
            $invalidTokens = [];

            // Envoyer par batch de 500 (limite FCM)
            $chunks = array_chunk($tokens, 500);
            Log::info("📦 Split tokens into " . count($chunks) . " chunks");
            
            foreach ($chunks as $chunkIndex => $chunk) {
                try {
                    Log::info("📤 Sending chunk " . ($chunkIndex + 1) . " with " . count($chunk) . " tokens");
                    $multicast = $this->messaging->sendMulticast($message, $chunk);
                    
                    if (!$multicast) {
                        Log::error('❌ Multicast send returned null');
                        continue;
                    }
                    
                    // Obtenir les succès et échecs (méthodes correctes pour la bibliothèque Firebase)
                    $successes = $multicast->successes();
                    $failures = $multicast->failures();
                    
                    Log::info("📊 Chunk " . ($chunkIndex + 1) . " results", [
                        'successes' => count($successes),
                        'failures' => count($failures),
                    ]);
                    
                    // Traiter les succès
                    if ($successes && count($successes) > 0) {
                        foreach ($successes as $index => $success) {
                            try {
                                // Essayer différentes méthodes pour obtenir le token
                                $token = null;
                                
                                // Méthode 1: target()->value()
                                try {
                                    $token = $success->target()->value();
                                } catch (\Exception $e1) {
                                    // Méthode 2: target()->token()
                                    try {
                                        $token = $success->target()->token();
                                    } catch (\Exception $e2) {
                                        // Méthode 3: Utiliser l'index du chunk
                                        if (isset($chunk[$index])) {
                                            $token = $chunk[$index];
                                        }
                                    }
                                }
                                
                                if ($token) {
                                    $results[] = $token;
                                    Log::info("✅ Successfully sent to token: " . substr($token, 0, 50) . "...");
                                } else {
                                    Log::warning("⚠️ Could not extract token from success result at index {$index}");
                                    // Utiliser le token du chunk comme fallback
                                    if (isset($chunk[$index])) {
                                        $results[] = $chunk[$index];
                                    }
                                }
                            } catch (\Exception $e) {
                                Log::warning("⚠️ Error processing success result at index {$index}: " . $e->getMessage());
                                // Utiliser le token du chunk comme fallback
                                if (isset($chunk[$index])) {
                                    $results[] = $chunk[$index];
                                }
                            }
                        }
                    }
                    
                    // Traiter les échecs
                    if ($failures && count($failures) > 0) {
                        foreach ($failures as $index => $failure) {
                            try {
                                // Essayer différentes méthodes pour obtenir le token
                                $token = null;
                                
                                try {
                                    $token = $failure->target()->value();
                                } catch (\Exception $e1) {
                                    try {
                                        $token = $failure->target()->token();
                                    } catch (\Exception $e2) {
                                        // Utiliser l'index pour trouver le token dans le chunk
                                        // Les échecs sont dans le même ordre que les tokens envoyés
                                        // On doit trouver l'index dans le chunk original
                                        // Note: Cette logique peut être complexe, on utilise le chunk comme fallback
                                    }
                                }
                                
                                if ($token) {
                                    $invalidTokens[] = $token;
                                    $error = $failure->error();
                                    Log::error("❌ Failed to send notification to token: " . substr($token, 0, 50) . "... - " . $error->getMessage());
                                } else {
                                    Log::warning("⚠️ Could not extract token from failure result at index {$index}");
                                }
                            } catch (\Exception $e) {
                                Log::warning("⚠️ Error processing failure result at index {$index}: " . $e->getMessage());
                            }
                        }
                    }
                    
                    // Si on a des succès mais qu'on n'a pas pu extraire les tokens, utiliser les tokens du chunk
                    if (count($successes) > 0 && count($results) == 0) {
                        Log::warning("⚠️ Could not extract tokens from success results, using chunk tokens as fallback");
                        // Si on a des succès, on assume que tous les tokens du chunk ont réussi
                        // (c'est une approximation, mais mieux que rien)
                        $results = array_merge($results, $chunk);
                    }
                } catch (\Exception $e) {
                    Log::error('❌ Error sending multicast: ' . $e->getMessage(), [
                        'trace' => $e->getTraceAsString(),
                    ]);
                    // En cas d'erreur, essayer d'envoyer individuellement
                    Log::info("🔄 Trying to send individually for chunk " . ($chunkIndex + 1));
                    foreach ($chunk as $token) {
                        try {
                            $this->messaging->send($message->withChangedTarget('token', $token));
                            $results[] = $token;
                            Log::info("✅ Successfully sent to token: " . substr($token, 0, 50) . "...");
                        } catch (\Exception $tokenError) {
                            $invalidTokens[] = $token;
                            Log::error("❌ Failed to send notification to token: " . substr($token, 0, 50) . "... - " . $tokenError->getMessage());
                        }
                    }
                }
            }

            // Désactiver les tokens invalides
            if (!empty($invalidTokens)) {
                Log::warning("🔴 Deactivating " . count($invalidTokens) . " invalid tokens");
                FcmToken::whereIn('token', $invalidTokens)->update(['is_active' => false]);
            }

            // Mettre à jour last_used_at pour les tokens valides
            if (!empty($results)) {
                Log::info("✅ Updating last_used_at for " . count($results) . " successful tokens");
                FcmToken::whereIn('token', $results)->update(['last_used_at' => now()]);
            }

            Log::info("📬 Push notification sending completed", [
                'successful' => count($results),
                'failed' => count($invalidTokens),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('❌ Failed to send push notification: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Créer une notification dans la base de données et envoyer une push
     */
    public function createAndSend($userId, $type, $title, $message, $projectId = null, $data = [])
    {
        // Créer la notification dans la base de données
        $notification = NotificationModel::create([
            'user_id' => $userId,
            'project_id' => $projectId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'is_read' => false,
        ]);

        // Envoyer la notification push
        $pushData = array_merge($data, [
            'notification_id' => $notification->id,
            'type' => $type,
            'project_id' => $projectId,
        ]);

        $this->sendToUser($userId, $title, $message, $pushData);

        return $notification;
    }

    /**
     * Créer des notifications dans la base de données pour tous les utilisateurs d'une entreprise et envoyer des push
     */
    public function createAndSendToCompany($companyId, $type, $title, $message, $projectId = null, $data = [])
    {
        try {
            $userIds = User::where('current_company_id', $companyId)
                ->orWhereHas('companies', function($query) use ($companyId) {
                    $query->where('companies.id', $companyId);
                })
                ->pluck('id')
                ->toArray();

            if (empty($userIds)) {
                Log::info("No users found for company {$companyId}");
                return [];
            }

            $notifications = [];
            $pushData = array_merge($data, [
                'type' => $type,
                'project_id' => $projectId,
            ]);

            // Créer une notification en base pour chaque utilisateur
            foreach ($userIds as $userId) {
                try {
                    $notification = NotificationModel::create([
                        'user_id' => $userId,
                        'project_id' => $projectId,
                        'type' => $type,
                        'title' => $title,
                        'message' => $message,
                        'data' => $data,
                        'is_read' => false,
                    ]);

                    $notifications[] = $notification;
                } catch (\Exception $e) {
                    Log::warning("Failed to create notification for user {$userId}: " . $e->getMessage());
                    // Continuer avec les autres utilisateurs
                }
            }

            // Envoyer les push notifications en une seule fois (plus efficace)
            try {
                $this->sendToUsers($userIds, $title, $message, $pushData);
            } catch (\Exception $e) {
                Log::warning("Failed to send push notifications: " . $e->getMessage());
                // Ne pas faire échouer la méthode si l'envoi de push échoue
            }

            return $notifications;
        } catch (\Exception $e) {
            Log::error("Error in createAndSendToCompany: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Notifier les utilisateurs concernés par un projet (superviseurs, clients, autres utilisateurs)
     * 
     * @param \App\Models\Project $project Le projet concerné
     * @param string $type Type de notification (ex: 'expense_created', 'expense_updated')
     * @param string $title Titre de la notification
     * @param string $message Message de la notification
     * @param array $data Données supplémentaires pour la notification
     * @param int|null $excludeUserId ID de l'utilisateur à exclure (celui qui a créé/modifié la dépense)
     * @return array Liste des notifications créées
     */
    public function notifyProjectStakeholders($project, $type, $title, $message, $data = [], $excludeUserId = null)
    {
        try {
            Log::info("🔔 notifyProjectStakeholders called", [
                'project_id' => $project->id,
                'project_name' => $project->name,
                'company_id' => $project->company_id,
                'type' => $type,
                'exclude_user_id' => $excludeUserId,
            ]);

            $companyId = $project->company_id;
            $userIds = collect();

            // 1. Récupérer les managers du projet (depuis le champ managers qui est un array JSON)
            if ($project->managers && is_array($project->managers)) {
                $managerIds = array_filter($project->managers, function($id) {
                    return is_numeric($id);
                });
                if (!empty($managerIds)) {
                    $userIds = $userIds->merge($managerIds);
                    Log::info("📋 Found managers: " . implode(', ', $managerIds));
                }
            }

            // 2. Récupérer les superviseurs (utilisateurs avec le rôle "superviseur" dans l'entreprise)
            $supervisorRole = Role::where('name', 'superviseur')->first();
            if ($supervisorRole) {
                $supervisorIds = User::whereHas('companies', function($query) use ($companyId, $supervisorRole) {
                    $query->where('companies.id', $companyId)
                          ->where('company_user.is_active', true)
                          ->where('company_user.role_id', $supervisorRole->id);
                })
                ->pluck('id');

                if ($supervisorIds->isNotEmpty()) {
                    $userIds = $userIds->merge($supervisorIds);
                    Log::info("👔 Found supervisors: " . $supervisorIds->implode(', '));
                }
            } else {
                Log::info("⚠️ Role 'superviseur' not found in database");
            }

            // 3. Récupérer les clients (utilisateurs avec le rôle "client" dans l'entreprise)
            $clientRole = Role::where('name', 'client')->first();
            if ($clientRole) {
                $clientIds = User::whereHas('companies', function($query) use ($companyId, $clientRole) {
                    $query->where('companies.id', $companyId)
                          ->where('company_user.is_active', true)
                          ->where('company_user.role_id', $clientRole->id);
                })
                ->pluck('id');

                if ($clientIds->isNotEmpty()) {
                    $userIds = $userIds->merge($clientIds);
                    Log::info("👤 Found clients: " . $clientIds->implode(', '));
                }
            } else {
                Log::info("⚠️ Role 'client' not found in database");
            }

            // 4. Récupérer les autres utilisateurs de l'entreprise du projet
            // Méthode alternative via la relation directe de Company
            $company = Company::find($companyId);
            if ($company) {
                $companyUserIds = $company->users()
                    ->wherePivot('is_active', true)
                    ->pluck('users.id');
                
                if ($companyUserIds->isNotEmpty()) {
                    $userIds = $userIds->merge($companyUserIds);
                    Log::info("🏢 Found company users via Company model: " . $companyUserIds->implode(', '));
                } else {
                    // Essayer avec whereHas si la méthode précédente ne fonctionne pas
                    $companyUserIds = User::whereHas('companies', function($query) use ($companyId) {
                        $query->where('companies.id', $companyId)
                              ->where('company_user.is_active', true);
                    })
                    ->pluck('id');
                    
                    if ($companyUserIds->isNotEmpty()) {
                        $userIds = $userIds->merge($companyUserIds);
                        Log::info("🏢 Found company users via whereHas: " . $companyUserIds->implode(', '));
                    } else {
                        Log::warning("⚠️ No users found for company {$companyId}");
                    }
                }
            } else {
                Log::error("❌ Company {$companyId} not found");
            }

            // Supprimer les doublons et exclure l'utilisateur qui a créé/modifié la dépense
            $userIds = $userIds->unique()->filter(function($userId) use ($excludeUserId) {
                return $userId != $excludeUserId;
            })->values()->toArray();

            Log::info("👥 Total unique users after filtering: " . count($userIds), [
                'user_ids' => $userIds,
            ]);

            if (empty($userIds)) {
                Log::warning("❌ No stakeholders found for project {$project->id} (company: {$companyId})");
                return [];
            }

            $notifications = [];
            $pushData = array_merge($data, [
                'type' => $type,
                'project_id' => $project->id,
            ]);

            // Créer une notification en base pour chaque utilisateur
            foreach ($userIds as $userId) {
                try {
                    $notification = NotificationModel::create([
                        'user_id' => $userId,
                        'project_id' => $project->id,
                        'type' => $type,
                        'title' => $title,
                        'message' => $message,
                        'data' => $data,
                        'is_read' => false,
                    ]);

                    $notifications[] = $notification;
                    Log::info("✅ Notification created for user {$userId} (ID: {$notification->id})");
                } catch (\Exception $e) {
                    Log::error("❌ Failed to create notification for user {$userId}: " . $e->getMessage(), [
                        'trace' => $e->getTraceAsString(),
                    ]);
                    // Continuer avec les autres utilisateurs
                }
            }

            Log::info("📬 Created " . count($notifications) . " notifications in database");

            // Envoyer les push notifications en une seule fois (plus efficace)
            try {
                $this->sendToUsers($userIds, $title, $message, $pushData);
            } catch (\Exception $e) {
                Log::warning("Failed to send push notifications: " . $e->getMessage());
                // Ne pas faire échouer la méthode si l'envoi de push échoue
            }

            return $notifications;
        } catch (\Exception $e) {
            Log::error("Error in notifyProjectStakeholders: " . $e->getMessage(), [
                'project_id' => $project->id ?? null,
                'trace' => $e->getTraceAsString(),
            ]);
            return [];
        }
    }
}


