<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Str;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $query = Notification::where('user_id', $request->user()->id);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('message', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%");
            });
        }

        $notifications = $query
            ->latest('created_at')
            ->get();

        $formattedNotifications = $notifications->map(function ($notification) {
            return [
                'id' => $notification->id,
                'type' => Str::title($notification->type),
                'message' => $notification->message,
                'link' => $notification->link,
                'is_read' => $notification->is_read,
                'created_at' => $notification->created_at 
                    ? Carbon::parse($notification->created_at)->timezone('Asia/Manila')->format('M d, Y h:i A') 
                    : '—',
            ];
        });

        return response()->json([
            'success' => true,
            'notifications' => $formattedNotifications,
        ]);
    }

    public function unreadCount(Request $request)
    {
        $count = Notification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'success' => true,
            'count' => $count,
        ]);
    }

    public function markAsRead(Request $request)
    {
        Notification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'success' => true
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $notification = Notification::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found.'
            ], 404);
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted successfully.'
        ]);
    }

    public function destroyAll(Request $request)
    {
        Notification::where('user_id', $request->user()->id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'All notifications deleted successfully.'
        ]);
    }
}