<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Notification;
use App\Models\Errand;
use App\Models\ErrandAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class TaskerController extends Controller
{
    public function pendingMetrics(Request $request)
    {
        $metrics = Errand::selectRaw('status, COUNT(*) as total')
            ->where('status', 'pending')
            ->groupBy('status')
            ->pluck('total', 'status');

        $pending = $metrics['pending'] ?? 0;

        return response()->json([
            'success' => true,
            'metrics' => [
                'pending' => $pending,
            ],
        ]);
    }

    public function pendingErrands(Request $request)
    {
        $search = $request->query('search');
        $perPage = (int) $request->query('per_page', 10);

        $allowedPerPage = [5, 10, 25, 50];

        if (!in_array($perPage, $allowedPerPage)) {
            $perPage = 10;
        }

        $query = Errand::where('status', 'pending');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%");
            });
        }

        $errands = $query
            ->latest()
            ->paginate($perPage);

        $formattedErrands = collect($errands->items())->map(function ($errand) {
            return [
                'id' => $errand->id,
                'title' => $errand->title,
                'category' => Str::title(str_replace('_', ' ', $errand->category)),
                'created_at' => $errand->created_at 
                    ? Carbon::parse($errand->created_at)->timezone('Asia/Manila')->format('M d, Y h:i A') 
                    : '—',
                'deadline' => $errand->deadline 
                    ? Carbon::parse($errand->deadline)->timezone('Asia/Manila')->format('M d, Y h:i A') 
                    : '—',
                'fee' => '₱' . number_format($errand->fee, 2),
                'status' => Str::title(str_replace('_', ' ', $errand->status)),
                'status_class' => 'status-badge status-' . $errand->status,
            ];
        });

        return response()->json([
            'success' => true,
            'errands' => $formattedErrands,
            'pagination' => [
                'current_page' => $errands->currentPage(),
                'last_page' => $errands->lastPage(),
                'per_page' => $errands->perPage(),
                'total' => $errands->total(),
                'from' => $errands->firstItem(),
                'to' => $errands->lastItem(),
            ],
        ]);
    }

    public function acceptedMetrics(Request $request)
    {
        $tasker = $request->user();

        $metrics = Errand::selectRaw('status, COUNT(*) as total')
            ->where('tasker_id', $tasker->id)
            ->whereIn('status', ['accepted', 'in_progress', 'awaiting_confirmation', 'dispute'])
            ->groupBy('status')
            ->pluck('total', 'status');

        $accepted = $metrics['accepted'] ?? 0;
        $inProgress = $metrics['in_progress'] ?? 0;
        $awaitingConfirmation = $metrics['awaiting_confirmation'] ?? 0;
        $dispute = $metrics['dispute'] ?? 0;

        $total = $metrics->sum();

        return response()->json([
            'success' => true,
            'metrics' => [
                'accepted' => $accepted,
                'in_progress' => $inProgress,
                'awaiting_confirmation' => $awaitingConfirmation,
                'dispute' => $dispute,
                'total' => $total,
            ],
        ]);
    }

    public function acceptedErrands(Request $request)
    {
        $taskerId = $request->user()->id;

        $search = $request->query('search');
        $perPage = (int) $request->query('per_page', 10);

        $allowedPerPage = [5, 10, 25, 50];

        if (!in_array($perPage, $allowedPerPage)) {
            $perPage = 10;
        }

        $query = Errand::where('tasker_id', $taskerId)
            ->whereIn('status', [
                'accepted',
                'in_progress',
                'awaiting_confirmation',
                'dispute',
            ]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%");
            });
        }

        $errands = $query
            ->orderBy('updated_at', 'desc')
            ->paginate($perPage);

        $formattedErrands = collect($errands->items())->map(function ($errand) {
            return [
                'id' => $errand->id,
                'title' => $errand->title,
                'category' => Str::title(str_replace('_', ' ', $errand->category)),
                'created_at' => $errand->created_at 
                    ? Carbon::parse($errand->created_at)->timezone('Asia/Manila')->format('M d, Y h:i A') 
                    : '—',
                'deadline' => $errand->deadline 
                    ? Carbon::parse($errand->deadline)->timezone('Asia/Manila')->format('M d, Y h:i A') 
                    : '—',
                'fee' => '₱' . number_format($errand->fee, 2),
                'status' => Str::title(str_replace('_', ' ', $errand->status)),
                'raw_status' => $errand->status,
                'status_class' => 'status-badge status-' . $errand->status,
            ];
        });

        return response()->json([
            'success' => true,
            'errands' => $formattedErrands,
            'pagination' => [
                'current_page' => $errands->currentPage(),
                'last_page' => $errands->lastPage(),
                'per_page' => $errands->perPage(),
                'total' => $errands->total(),
                'from' => $errands->firstItem(),
                'to' => $errands->lastItem(),
            ],
        ]);
    }

    public function acceptErrand(Request $request, $id)
    {
        $tasker = $request->user();

        $errand = Errand::where('id', $id)->where('status', 'pending')->first();

        if (!$errand) {
            return response()->json([
                'success' => false,
                'message' => 'Errand not found or is no longer available.',
            ], 404);
        }

        $errand->status = 'accepted';
        $errand->tasker_id = $tasker->id;
        $errand->accepted_at = Carbon::now();
        $errand->save();

        $errand_id = $errand->id;
        $errand_title = $errand->title;
        
        $client_id = $errand->client_id;
        $client = User::find($client_id);
        $client_full_name = $client ? trim("{$client->first_name} " . ($client->middle_name ? "{$client->middle_name} " : "") . "{$client->last_name}") : 'Client';

        $tasker_id = $tasker->id;
        $tasker_full_name = trim("{$tasker->first_name} " . ($tasker->middle_name ? "{$tasker->middle_name} " : "") . "{$tasker->last_name}");

        $client_msg = "Your Errand: \"{$errand_title}\" (ID: {$errand_id}) was Accepted by Tasker: \"{$tasker_full_name}\" (ID: {$tasker_id}).";
        $admin_msg = "Errand Accepted | Errand: \"{$errand_title}\" (ID: {$errand_id}) | Created by Client: \"{$client_full_name}\" (ID: {$client_id}) | Accepted by Tasker: \"{$tasker_full_name}\" (ID: {$tasker_id}).";

        $notif_type = "errand";

        $client_link = route('client.view-errand', ['id' => $errand_id]);
        $admin_link = route('admin.view-errand', ['id' => $errand_id]);

        if ($client_id) {
            Notification::create([
                'user_id' => $client_id,
                'type' => $notif_type,
                'message' => $client_msg,
                'link' => $client_link,
                'is_read' => false,
            ]);
        }

        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'type' => $notif_type,
                'message' => $admin_msg,
                'link' => $admin_link,
                'is_read' => false,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Errand accepted successfully!',
        ]);
    }

    public function getErrandDetails($id, Request $request)
    {
        $taskerId = $request->user()->id;

        $errand = Errand::with(['attachment', 'client'])
            ->where('id', $id)
            ->where(function ($q) use ($taskerId) {
                $q->whereNull('tasker_id')
                    ->orWhere('tasker_id', $taskerId);
            })
            ->first();

        if (!$errand) {
            return response()->json([
                'success' => false, 
                'message' => 'Errand not found or is no longer available.'
            ], 404);
        }

        $finishedAt = $errand->completed_at ?? $errand->failed_at;

        $formattedErrand = [
            'id' => $errand->id,
            'title' => $errand->title,
            'category' => Str::title(str_replace('_', ' ', $errand->category)),
            'description' => $errand->description,
            'location' => $errand->location,
            'contact' => $errand->contact,
            'fee' => '₱' . number_format($errand->fee, 2),
            'status' => Str::title(str_replace('_', ' ', $errand->status)),
            'raw_status' => $errand->status,
            'cancel_requested_by_client' => $errand->cancel_requested_by_client ?? false,
            'status_class' => 'status-field status-' . $errand->status,
            
            'created_at' => $errand->created_at 
                ? Carbon::parse($errand->created_at)->timezone('Asia/Manila')->format('M d, Y h:i A') 
                : 'Not Set',
            'updated_at' => $errand->updated_at 
                ? Carbon::parse($errand->updated_at)->timezone('Asia/Manila')->format('M d, Y h:i A') 
                : 'Not Set',
            'accepted_at' => $errand->accepted_at 
                ? Carbon::parse($errand->accepted_at)->timezone('Asia/Manila')->format('M d, Y h:i A') 
                : 'Not Set',
            'in_progress_at' => $errand->in_progress_at 
                ? Carbon::parse($errand->in_progress_at)->timezone('Asia/Manila')->format('M d, Y h:i A') 
                : 'Not Set',
            'deadline' => $errand->deadline 
                ? Carbon::parse($errand->deadline)->timezone('Asia/Manila')->format('M d, Y h:i A') 
                : 'Not Set',
            'finished_at' => $finishedAt 
                ? Carbon::parse($finishedAt)->timezone('Asia/Manila')->format('M d, Y h:i A') 
                : 'Not Set',
            
            'attachment' => $errand->attachment ? [
                'name' => $errand->attachment->original_name,
                'url' => Storage::url($errand->attachment->file_path)
            ] : null,

            'client' => $errand->client ? [
                'id' => $errand->client->id,
                'name' => trim("{$errand->client->first_name} " . ($errand->client->middle_name ? "{$errand->client->middle_name} " : "") . "{$errand->client->last_name}"),
                'email' => $errand->client->email,
                'contact_number' => $errand->client->contact_number,
                'profile_picture' => $errand->client->profile_picture 
                    ? Storage::url($errand->client->profile_picture) 
                    : asset('storage/uploads/profile_pictures/default.jpg')
            ] : null,
        ];

        return response()->json([
            'success' => true, 
            'errand' => $formattedErrand
        ]);
    }
}