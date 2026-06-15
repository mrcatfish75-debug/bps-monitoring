<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * NOTIFICATION PAGE
     */
    public function index(Request $request)
    {
        $request->validate([
            'filter' => 'nullable|string|in:all,unread,read',
        ]);

        $filter = $request->filter ?? 'all';

        $query = Notification::where('user_id', auth()->id())
            ->latest();

        if ($filter === 'unread') {
            $query->where('is_read', false);
        }

        if ($filter === 'read') {
            $query->where('is_read', true);
        }

        $notifications = $query
            ->paginate(10)
            ->withQueryString();

        $unreadCount = Notification::where('user_id', auth()->id())
            ->where('is_read', false)
            ->count();

        $totalCount = Notification::where('user_id', auth()->id())
            ->count();

        return view('notification.index', compact(
            'notifications',
            'unreadCount',
            'totalCount',
            'filter'
        ));
    }

    /**
     * MARK 1 NOTIFICATION AS READ
     */
    public function markAsRead($id)
    {
        $notification = Notification::where('user_id', auth()->id())
            ->findOrFail($id);

        $notification->markAsRead();

        if ($notification->url) {
            return redirect($notification->url);
        }

        return back()->with('success', 'Notifikasi sudah dibaca.');
    }

    /**
     * MARK ALL AS READ
     */
    public function markAllAsRead()
    {
        Notification::where('user_id', auth()->id())
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return back()->with('success', 'Semua notifikasi sudah dibaca.');
    }

    /**
     * GET UNREAD COUNT FOR BADGE
     */
    public function unreadCount()
    {
        $count = Notification::where('user_id', auth()->id())
            ->where('is_read', false)
            ->count();

        return response()->json([
            'unread' => $count,
        ]);
    }
}