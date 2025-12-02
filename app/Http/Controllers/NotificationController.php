<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Afficher toutes les notifications
     */
    public function index()
    {
        $user = Auth::user();
        $notifications = $user->notifications()->with('project')->paginate(20);
        
        return view('notifications.index', compact('notifications'));
    }

    /**
     * Marquer une notification comme lue
     */
    public function markAsRead(Notification $notification)
    {
        $user = Auth::user();
        
        if ($notification->user_id !== $user->id) {
            abort(403);
        }

        $notification->markAsRead();

        return response()->json(['success' => true]);
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

        return redirect()->back()->with('success', 'Toutes les notifications ont été marquées comme lues.');
    }

    /**
     * Obtenir le nombre de notifications non lues (API)
     */
    public function unreadCount()
    {
        $user = Auth::user();
        return response()->json([
            'count' => $user->unreadNotificationsCount()
        ]);
    }

    /**
     * Obtenir les dernières notifications non lues (API)
     */
    public function latest()
    {
        $user = Auth::user();
        $notifications = $user->unreadNotifications()
            ->with('project')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json($notifications);
    }
}
