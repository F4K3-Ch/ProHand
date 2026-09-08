<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Notification;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use App\Models\Errand;
use App\Models\ErrandAttachment;
use App\Models\History;
use App\Models\HistoryAttachment;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function allActiveMetrics()
    {
        $metrics = Errand::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $pending = $metrics['pending'] ?? 0;
        $accepted = $metrics['accepted'] ?? 0;
        $inProgress = $metrics['in_progress'] ?? 0;
        $awaitingConfirmation = $metrics['awaiting_confirmation'] ?? 0;
        $dispute = $metrics['dispute'] ?? 0;

        $total = $metrics->sum();

        return response()->json([
            'success' => true,
            'metrics' => [
                'pending' => $pending,
                'accepted' => $accepted,
                'in_progress' => $inProgress,
                'awaiting_confirmation' => $awaitingConfirmation,
                'dispute' => $dispute,
                'total' => $total,
            ],
        ]);
    }

    public function allActiveErrands(Request $request)
    {
        $search = $request->query('search');
        $perPage = (int) $request->query('per_page', 8);

        $allowedPerPage = [4, 8, 16, 32];

        if (!in_array($perPage, $allowedPerPage)) {
            $perPage = 8;
        }

        $query = Errand::query();

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
                'location' => $errand->location,
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

    public function allHistoryMetrics()
    {
        $metrics = History::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $completed = $metrics['completed'] ?? 0;
        $failed = $metrics['failed'] ?? 0;
        $total = $metrics->sum();

        return response()->json([
            'success' => true,
            'metrics' => [
                'completed' => $completed,
                'failed' => $failed,
                'total' => $total,
            ],
        ]);
    }

    public function allHistoryErrands(Request $request)
    {
        $search = $request->query('search');
        $perPage = (int) $request->query('per_page', 8);

        $allowedPerPage = [4, 8, 16, 32];

        if (!in_array($perPage, $allowedPerPage)) {
            $perPage = 8;
        }

        $query = History::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%");
            });
        }

        $errands = $query
            ->orderBy('finished_at', 'desc')
            ->paginate($perPage);

        $formattedErrands = collect($errands->items())->map(function ($errand) {
            return [
                'id' => $errand->errand_id,
                'title' => $errand->title,
                'location' => $errand->location,
                'category' => Str::title(str_replace('_', ' ', $errand->category)),
                'created_at' => $errand->created_at 
                    ? Carbon::parse($errand->created_at)->timezone('Asia/Manila')->format('M d, Y h:i A') 
                    : '—',
                'finished_at' => $errand->finished_at 
                    ? Carbon::parse($errand->finished_at)->timezone('Asia/Manila')->format('M d, Y h:i A') 
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

    public function getErrandDetails($id)
    {
        $errand = Errand::with(['attachment', 'client', 'tasker'])
            ->where('id', $id)
            ->first();

        if ($errand) {
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
                'cancel_requested_by_client' => $errand->cancel_requested_by_client,
                'cancel_requested_by_tasker' => $errand->cancel_requested_by_tasker,
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
                'finished_at' => $errand->finished_at
                    ? Carbon::parse($errand->finished_at)->timezone('Asia/Manila')->format('M d, Y h:i A')
                    : 'Not Set',

                'attachment' => $errand->attachment ? [
                    'name' => $errand->attachment->original_name,
                    'url' => Storage::url($errand->attachment->file_path)
                ] : null,

                'client' => $errand->client ? [
                    'id' => $errand->client->id,
                    'name' => trim(
                        "{$errand->client->first_name} " .
                        ($errand->client->middle_name ? "{$errand->client->middle_name} " : "") .
                        $errand->client->last_name
                    ),
                    'email' => $errand->client->email,
                    'contact_number' => $errand->client->contact_number,
                    'profile_picture' => $errand->client->profile_picture
                        ? Storage::url($errand->client->profile_picture)
                        : asset('storage/uploads/profile_pictures/default.jpg')
                ] : null,

                'tasker' => $errand->tasker ? [
                    'id' => $errand->tasker->id,
                    'name' => trim(
                        "{$errand->tasker->first_name} " .
                        ($errand->tasker->middle_name ? "{$errand->tasker->middle_name} " : "") .
                        $errand->tasker->last_name
                    ),
                    'email' => $errand->tasker->email,
                    'contact_number' => $errand->tasker->contact_number,
                    'profile_picture' => $errand->tasker->profile_picture
                        ? Storage::url($errand->tasker->profile_picture)
                        : asset('storage/uploads/profile_pictures/default.jpg')
                ] : null,
            ];

            return response()->json([
                'success' => true,
                'errand' => $formattedErrand
            ]);
        }

        $history = History::with(['attachment'])
            ->where('errand_id', $id)
            ->first();

        if (!$history) {
            return response()->json([
                'success' => false,
                'message' => 'Errand not found.'
            ], 404);
        }

        $formattedErrand = [
            'id' => $history->errand_id,
            'title' => $history->title,
            'category' => Str::title(str_replace('_', ' ', $history->category)),
            'description' => $history->description,
            'location' => $history->location,
            'contact' => $history->contact,
            'fee' => '₱' . number_format($history->fee, 2),
            'status' => Str::title(str_replace('_', ' ', $history->status)),
            'raw_status' => $history->status,
            'status_class' => 'status-field status-' . $history->status,

            'created_at' => $history->created_at
                ? Carbon::parse($history->created_at)->timezone('Asia/Manila')->format('M d, Y h:i A')
                : 'Not Set',
            'updated_at' => $history->updated_at
                ? Carbon::parse($history->updated_at)->timezone('Asia/Manila')->format('M d, Y h:i A')
                : 'Not Set',
            'accepted_at' => $history->accepted_at
                ? Carbon::parse($history->accepted_at)->timezone('Asia/Manila')->format('M d, Y h:i A')
                : 'Not Set',
            'in_progress_at' => $history->in_progress_at
                ? Carbon::parse($history->in_progress_at)->timezone('Asia/Manila')->format('M d, Y h:i A')
                : 'Not Set',
            'deadline' => $history->deadline
                ? Carbon::parse($history->deadline)->timezone('Asia/Manila')->format('M d, Y h:i A')
                : 'Not Set',
            'finished_at' => $history->finished_at
                ? Carbon::parse($history->finished_at)->timezone('Asia/Manila')->format('M d, Y h:i A')
                : 'Not Set',

            'attachment' => $history->attachment ? [
                'name' => $history->attachment->original_name,
                'url' => Storage::url($history->attachment->file_path)
            ] : null,

            'client' => $history->client_id ? [
                'id' => $history->client_id,
                'name' => trim(
                    "{$history->client_first_name} " .
                    ($history->client_middle_name ? "{$history->client_middle_name} " : "") .
                    $history->client_last_name
                ),
                'email' => $history->client_email,
                'contact_number' => $history->client_contact_number,
                'profile_picture' => $history->client_profile_picture
                    ? Storage::url($history->client_profile_picture)
                    : asset('storage/uploads/history/profile_pictures/default.jpg')
            ] : null,

            'tasker' => $history->tasker_id ? [
                'id' => $history->tasker_id,
                'name' => trim(
                    "{$history->tasker_first_name} " .
                    ($history->tasker_middle_name ? "{$history->tasker_middle_name} " : "") .
                    $history->tasker_last_name
                ),
                'email' => $history->tasker_email,
                'contact_number' => $history->tasker_contact_number,
                'profile_picture' => $history->tasker_profile_picture
                    ? Storage::url($history->tasker_profile_picture)
                    : asset('storage/uploads/history/profile_pictures/default.jpg')
            ] : null,
        ];

        return response()->json([
            'success' => true,
            'errand' => $formattedErrand
        ]);
    }

    public function resetErrandToPending(Request $request, $id)
    {
        $admin = $request->user();

        $errand = Errand::where('id', $id)
            ->whereIn('status', ['accepted', 'in_progress'])
            ->first();

        if (!$errand) {
            return response()->json([
                'success' => false, 
                'message' => 'Errand not found or is not in an accepted or in-progress state.'
            ], 404);
        }

        $tasker_id = $errand->tasker_id;
        $client_id = $errand->client_id;

        $errand->status = 'pending';
        $errand->tasker_id = null;
        $errand->accepted_at = null;
        $errand->in_progress_at = null;
        $errand->cancel_requested_by_client = false;
        $errand->cancel_requested_by_tasker = false;
        $errand->save();

        $errand_id = $errand->id;
        $errand_title = $errand->title;
        
        $admin_full_name = trim("{$admin->first_name} " . ($admin->middle_name ? "{$admin->middle_name} " : "") . "{$admin->last_name}");

        $client_msg = "Your Errand: \"{$errand_title}\" (ID: {$errand_id}) status was reset to \"Pending\" by Administrator: \"{$admin_full_name}\" (ID: {$admin->id}).";
        $tasker_msg = "The Errand: \"{$errand_title}\" (ID: {$errand_id}) you were assigned to has been reset to \"Pending\" by Administrator: \"{$admin_full_name}\" (ID: {$admin->id}).";
        $admin_msg = "Errand Reset | Errand: \"{$errand_title}\" (ID: {$errand_id}) | Reset to Pending by Admin: \"{$admin_full_name}\" (ID: {$admin->id}).";

        $notif_type = "errand";

        $client_link = route('client.view-errand', ['id' => $errand_id]) . '?from=notifications';
        $tasker_link = route('tasker.view-errand', ['id' => $errand_id]) . '?from=notifications';
        $admin_link = route('admin.view-errand', ['id' => $errand_id]) . '?from=notifications';

        if ($client_id) {
            Notification::create([
                'user_id' => $client_id,
                'type' => $notif_type,
                'message' => $client_msg,
                'link' => $client_link,
                'is_read' => false,
            ]);
        }

        if ($tasker_id) {
            Notification::create([
                'user_id' => $tasker_id,
                'type' => $notif_type,
                'message' => $tasker_msg,
                'link' => $tasker_link,
                'is_read' => false,
            ]);
        }

        $admins = User::where('role', 'admin')->where('id', '!=', $admin->id)->get();
        foreach ($admins as $adm) {
            Notification::create([
                'user_id' => $adm->id,
                'type' => $notif_type,
                'message' => $admin_msg,
                'link' => $admin_link,
                'is_read' => false,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Errand successfully reset to pending status!',
        ]);
    }

    public function destroyErrand(Request $request, $id)
    {
        $admin = $request->user();

        $errand = Errand::with('attachment')
            ->where('id', $id)
            ->first();

        if (!$errand) {
            return response()->json([
                'success' => false, 
                'message' => 'Errand not found.'
            ], 404);
        }

        if (strtolower($errand->status) !== 'pending') {
            return response()->json([
                'success' => false, 
                'message' => 'Only pending errands can be deleted.'
            ], 403);
        }

        $client_id = $errand->client_id;

        $errand_id = $errand->id;
        $errand_title = $errand->title;

        if ($errand->attachment) {
            Storage::disk('public')->delete($errand->attachment->file_path);
            $errand->attachment()->delete();
        }

        $errand->delete();

        $admin_full_name = trim("{$admin->first_name} " . ($admin->middle_name ? "{$admin->middle_name} " : "") . "{$admin->last_name}");
        
        $client_msg = "Your Pending Errand: \"{$errand_title}\" (ID: {$errand_id}) has been deleted by Administrator: \"{$admin_full_name}\" (ID: {$admin->id}).";
        $admin_msg = "Errand Deleted | Pending Errand: \"{$errand_title}\" (ID: {$errand_id}) | Deleted by Admin: \"{$admin_full_name}\" (ID: {$admin->id}).";

        $notif_type = "errand";

        $client_link = route('client.dashboard') . '?from=notifications';
        $admin_link = route('admin.dashboard') . '?from=notifications';

        if ($client_id) {
            Notification::create([
                'user_id' => $client_id,
                'type' => $notif_type,
                'message' => $client_msg,
                'link' => $client_link,
                'is_read' => false,
            ]);
        }

        $admins = User::where('role', 'admin')->where('id', '!=', $admin->id)->get();
        foreach ($admins as $adm) {
            Notification::create([
                'user_id' => $adm->id,
                'type' => $notif_type,
                'message' => $admin_msg,
                'link' => $admin_link,
                'is_read' => false,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Errand deleted successfully.',
        ]);
    }
}