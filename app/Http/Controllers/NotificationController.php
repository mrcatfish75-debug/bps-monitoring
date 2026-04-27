<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;

class NotificationController extends Controller
{
    /**
     * MARK 1 NOTIFICATION AS READ
     */
    public function markAsRead($id)
    {
        $notif = Notification::findOrFail($id);

        // 🔒 SECURITY CHECK
        if ($notif->user_id !== auth()->id()) {
            abort(403, 'Akses Ditolak');
        }

        // 🔥 UPDATE STATUS
        $notif->update([
            'is_read' => true
        ]);

        return back()->with('success', 'Notifikasi dibaca');
    }

    /**
     * MARK ALL AS READ
     */
    public function markAllAsRead()
    {
        Notification::where('user_id', auth()->id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return back()->with('success', 'Semua notifikasi dibaca');
    }

    /**
     * GET USER NOTIFICATIONS (untuk nanti Vue / AJAX)
     */
    public function index()
    {
        $notifications = Notification::where('user_id', auth()->id())
            ->latest()
            ->get();

        return response()->json($notifications);
    }

    /**
     * GET UNREAD COUNT (badge navbar)
     */
    public function unreadCount()
    {
        $count = Notification::where('user_id', auth()->id())
            ->where('is_read', false)
            ->count();

        return response()->json([
            'unread' => $count
        ]);
    }
}