<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Liste des notifications
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = $user->notifications()->with('project');

        // Filtrer par statut (lu/non lu)
        if ($request->has('unread_only') && $request->unread_only == 'true') {
            $query->where('is_read', false);
        }

        // Limite
        $limit = $request->get('limit', 50);
        $notifications = $query->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $notifications,
        ], 200);
    }

    /**
     * Nombre de notifications non lues
     */
    public function unreadCount()
    {
        $user = Auth::user();
        $count = $user->unreadNotificationsCount();

        return response()->json([
            'success' => true,
            'count' => $count,
        ], 200);
    }

    /**
     * Dernières notifications
     */
    public function latest(Request $request)
    {
        $user = Auth::user();
        $limit = $request->get('limit', 10);

        $notifications = $user->notifications()
            ->with('project')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $notifications,
        ], 200);
    }

    /**
     * Marquer une notification comme lue
     */
    public function markAsRead($id)
    {
        $user = Auth::user();
        $notification = Notification::find($id);

        if (!$notification || $notification->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Notification non trouvée.',
            ], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notification marquée comme lue.',
            'data' => $notification,
        ], 200);
    }

    /**
     * Marquer toutes les notifications comme lues
     */
    public function markAllAsRead()
    {
        $user = Auth::user();

        $user->unreadNotifications()->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Toutes les notifications ont été marquées comme lues.',
        ], 200);
    }

    /**
     * Supprimer une notification
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $notification = Notification::find($id);

        if (!$notification || $notification->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Notification non trouvée.',
            ], 404);
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification supprimée avec succès.',
        ], 200);
    }
}

