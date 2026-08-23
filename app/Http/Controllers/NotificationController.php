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
                    ? Carbon::parse($notification->created_at)
                        ->timezone('Asia/Manila')
                        ->format('M d, Y h:i A')
                    : '—',
            ];
        });

        return response()->json([
            'success' => true,
            'notifications' => $formattedNotifications,
        ]);
    }
}