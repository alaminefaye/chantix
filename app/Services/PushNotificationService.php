<?php

namespace App\Services;

use App\Models\User;
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
                $multicast = $this->messaging->sendMulticast($message, $chunk);
                
                // Traiter les résultats
                foreach ($multicast->getResponses() as $index => $response) {
                    if ($response->isSuccess()) {
                        $results[] = $chunk[$index];
                    } else {
                        $invalidTokens[] = $chunk[$index];
                        Log::warning("Failed to send notification to token: " . $chunk[$index] . " - " . $response->error());
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
}


