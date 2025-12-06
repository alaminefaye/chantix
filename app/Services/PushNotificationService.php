<?php

namespace App\Services;

use App\Models\User;
use App\Models\Project;
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
                Log::error('Firebase credentials file not found at: ' . $firebaseCredentialsPath);
                return;
            }

            $factory = (new Factory)
                ->withServiceAccount($firebaseCredentialsPath);

            $this->messaging = $factory->createMessaging();
        } catch (\Exception $e) {
            Log::error('Failed to initialize Firebase: ' . $e->getMessage());
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
        if (!$this->messaging) {
            Log::warning('Firebase messaging not initialized');
            return false;
        }

        $tokens = FcmToken::whereIn('user_id', $userIds)
            ->where('is_active', true)
            ->pluck('token')
            ->toArray();

        if (empty($tokens)) {
            Log::info("No active FCM tokens found for users: " . implode(', ', $userIds));
            return false;
        }

        return $this->sendToTokens($tokens, $title, $body, $data);
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
            return false;
        }

        try {
            $notification = Notification::create($title, $body);
            
            $message = CloudMessage::new()
                ->withNotification($notification)
                ->withData($data);

            $results = [];
            $invalidTokens = [];

            // Envoyer par batch de 500 (limite FCM)
            $chunks = array_chunk($tokens, 500);
            
            foreach ($chunks as $chunk) {
                try {
                    $multicast = $this->messaging->sendMulticast($message, $chunk);
                    
                    if (!$multicast) {
                        Log::warning('Multicast send returned null');
                        continue;
                    }
                    
                    // Obtenir les succès et échecs (méthodes correctes pour la bibliothèque Firebase)
                    $successes = $multicast->successes();
                    $failures = $multicast->failures();
                    
                    // Traiter les succès
                    if ($successes) {
                        foreach ($successes as $success) {
                            try {
                                $token = $success->target()->value();
                                $results[] = $token;
                            } catch (\Exception $e) {
                                Log::warning("Error processing success result: " . $e->getMessage());
                            }
                        }
                    }
                    
                    // Traiter les échecs
                    if ($failures) {
                        foreach ($failures as $failure) {
                            try {
                                $token = $failure->target()->value();
                                $invalidTokens[] = $token;
                                $error = $failure->error();
                                Log::warning("Failed to send notification to token: {$token} - {$error->getMessage()}");
                            } catch (\Exception $e) {
                                Log::warning("Error processing failure result: " . $e->getMessage());
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Error sending multicast: ' . $e->getMessage(), [
                        'trace' => $e->getTraceAsString(),
                    ]);
                    // En cas d'erreur, essayer d'envoyer individuellement
                    foreach ($chunk as $token) {
                        try {
                            $this->messaging->send($message->withChangedTarget('token', $token));
                            $results[] = $token;
                        } catch (\Exception $tokenError) {
                            $invalidTokens[] = $token;
                            Log::warning("Failed to send notification to token: {$token} - {$tokenError->getMessage()}");
                        }
                    }
                }
            }

            // Désactiver les tokens invalides
            if (!empty($invalidTokens)) {
                FcmToken::whereIn('token', $invalidTokens)->update(['is_active' => false]);
            }

            // Mettre à jour last_used_at pour les tokens valides
            if (!empty($results)) {
                FcmToken::whereIn('token', $results)->update(['last_used_at' => now()]);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send push notification: ' . $e->getMessage());
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
            $companyId = $project->company_id;
            $userIds = collect();

            // 1. Récupérer les managers du projet (depuis le champ managers qui est un array JSON)
            if ($project->managers && is_array($project->managers)) {
                $managerIds = array_filter($project->managers, function($id) {
                    return is_numeric($id);
                });
                if (!empty($managerIds)) {
                    $userIds = $userIds->merge($managerIds);
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

                $userIds = $userIds->merge($supervisorIds);
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

                $userIds = $userIds->merge($clientIds);
            }

            // 4. Récupérer les autres utilisateurs de l'entreprise du projet
            $companyUserIds = User::whereHas('companies', function($query) use ($companyId) {
                $query->where('companies.id', $companyId)
                      ->where('company_user.is_active', true);
            })
            ->pluck('id');

            $userIds = $userIds->merge($companyUserIds);

            // Supprimer les doublons et exclure l'utilisateur qui a créé/modifié la dépense
            $userIds = $userIds->unique()->filter(function($userId) use ($excludeUserId) {
                return $userId != $excludeUserId;
            })->values()->toArray();

            if (empty($userIds)) {
                Log::info("No stakeholders found for project {$project->id}");
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
            Log::error("Error in notifyProjectStakeholders: " . $e->getMessage(), [
                'project_id' => $project->id ?? null,
                'trace' => $e->getTraceAsString(),
            ]);
            return [];
        }
    }
}


