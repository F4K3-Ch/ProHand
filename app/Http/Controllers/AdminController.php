<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
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
            ->whereIn('status', ['accepted', 'in_progress', 'awaiting_confirmation'])
            ->first();

        if (!$errand) {
            return response()->json([
                'success' => false, 
                'message' => 'Errand not found or is not in an accepted, in-progress or awaiting_confirmation state.'
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

    public function approveCancellation(Request $request, $id)
    {
        $admin = $request->user();
        $requester = $request->input('requester'); // 'client' or 'tasker'

        $errand = Errand::where('id', $id)
            ->where(function ($query) {
                $query->where('cancel_requested_by_client', true)
                    ->orWhere('cancel_requested_by_tasker', true);
            })
            ->first();

        if (!$errand) {
            return response()->json([
                'success' => false, 
                'message' => 'Errand not found or has no pending cancellation request.'
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

        if ($requester === 'client') {
            $client_msg = "Your cancellation request for Errand: \"{$errand_title}\" (ID: {$errand_id}) was approved by Administrator: \"{$admin_full_name}\" (ID: {$admin->id}). Status reset to Pending.";
            $tasker_msg = "The client's cancellation request for Errand: \"{$errand_title}\" (ID: {$errand_id}) was approved by Administrator: \"{$admin_full_name}\" (ID: {$admin->id}). You are no longer assigned.";
            $admin_msg = "Cancellation Approved (Client Request) | Errand: \"{$errand_title}\" (ID: {$errand_id}) | Reset to Pending by Admin: \"{$admin_full_name}\" (ID: {$admin->id}).";
        } else {
            $client_msg = "The tasker's cancellation request for Errand: \"{$errand_title}\" (ID: {$errand_id}) was approved by Administrator: \"{$admin_full_name}\" (ID: {$admin->id}). Status reset to Pending.";
            $tasker_msg = "Your cancellation request for Errand: \"{$errand_title}\" (ID: {$errand_id}) was approved by Administrator: \"{$admin_full_name}\" (ID: {$admin->id}).";
            $admin_msg = "Cancellation Approved (Tasker Request) | Errand: \"{$errand_title}\" (ID: {$errand_id}) | Reset to Pending by Admin: \"{$admin_full_name}\" (ID: {$admin->id}).";
        }

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
            'message' => 'Cancellation request approved successfully and errand reset to pending!',
        ]);
    }

    public function denyCancellation(Request $request, $id)
    {
        $admin = $request->user();
        $requester = $request->input('requester'); // 'client' or 'tasker'

        $errand = Errand::where('id', $id)->first();

        if (!$errand) {
            return response()->json([
                'success' => false, 
                'message' => 'Errand not found.'
            ], 404);
        }

        if ($requester === 'client') {
            $errand->cancel_requested_by_client = false;
        } elseif ($requester === 'tasker') {
            $errand->cancel_requested_by_tasker = false;
        } else {
            $errand->cancel_requested_by_client = false;
            $errand->cancel_requested_by_tasker = false;
        }
        $errand->save();

        $tasker_id = $errand->tasker_id;
        $client_id = $errand->client_id;
        $errand_id = $errand->id;
        $errand_title = $errand->title;
        
        $admin_full_name = trim("{$admin->first_name} " . ($admin->middle_name ? "{$admin->middle_name} " : "") . "{$admin->last_name}");

        if ($requester === 'client') {
            $client_msg = "Your cancellation request for Errand: \"{$errand_title}\" (ID: {$errand_id}) was denied by Administrator: \"{$admin_full_name}\" (ID: {$admin->id}).";
            $tasker_msg = "The client's cancellation request for Errand: \"{$errand_title}\" (ID: {$errand_id}) was denied by Administrator: \"{$admin_full_name}\" (ID: {$admin->id}).";
            $admin_msg = "Cancellation Denied (Client Request) | Errand: \"{$errand_title}\" (ID: {$errand_id}) | Denied by Admin: \"{$admin_full_name}\" (ID: {$admin->id}).";
        } else {
            $client_msg = "The tasker's cancellation request for Errand: \"{$errand_title}\" (ID: {$errand_id}) was denied by Administrator: \"{$admin_full_name}\" (ID: {$admin->id}).";
            $tasker_msg = "Your cancellation request for Errand: \"{$errand_title}\" (ID: {$errand_id}) was denied by Administrator: \"{$admin_full_name}\" (ID: {$admin->id}).";
            $admin_msg = "Cancellation Denied (Tasker Request) | Errand: \"{$errand_title}\" (ID: {$errand_id}) | Denied by Admin: \"{$admin_full_name}\" (ID: {$admin->id}).";
        }

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
            'message' => 'Cancellation request denied successfully!',
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

        if ($client_id) {
            Notification::create([
                'user_id' => $client_id,
                'type' => $notif_type,
                'message' => $client_msg,
                'link' => null,
                'is_read' => false,
            ]);
        }

        $otherAdmins = User::where('role', 'admin')->where('id', '!=', $admin->id)->get();
        foreach ($otherAdmins as $adm) {
            Notification::create([
                'user_id' => $adm->id,
                'type' => $notif_type,
                'message' => $admin_msg,
                'link' => null,
                'is_read' => false,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Errand deleted successfully.',
        ]);
    }

    public function updateCompleted(Request $request, $id)
    {
        $admin = $request->user();

        $errand = Errand::with('attachment')
            ->where('id', $id)
            ->whereIn('status', ['awaiting_confirmation', 'dispute'])
            ->first();

        if (!$errand) {
            return response()->json([
                'success' => false,
                'message' => 'Errand not found or is not in an awaiting confirmation or dispute state.'
            ], 404);
        }

        $client = User::find($errand->client_id);
        $tasker = $errand->tasker_id ? User::find($errand->tasker_id) : null;

        try {
            $history = DB::transaction(function () use ($errand, $admin, $client, $tasker) {

                $history = History::create([
                    'errand_id' => $errand->id,
                    'title' => $errand->title,
                    'category' => $errand->category,
                    'description' => $errand->description,
                    'fee' => $errand->fee,
                    'deadline' => $errand->deadline,
                    'location' => $errand->location,
                    'contact' => $errand->contact,
                    'status' => 'completed',

                    'client_id' => $client?->id,
                    'client_last_name' => $client?->last_name,
                    'client_first_name' => $client?->first_name,
                    'client_middle_name' => $client?->middle_name,
                    'client_email' => $client?->email,
                    'client_contact_number' => $client?->contact_number,

                    'tasker_id' => $tasker?->id,
                    'tasker_last_name' => $tasker?->last_name,
                    'tasker_first_name' => $tasker?->first_name,
                    'tasker_middle_name' => $tasker?->middle_name,
                    'tasker_email' => $tasker?->email,
                    'tasker_contact_number' => $tasker?->contact_number,

                    'created_at' => $errand->created_at,
                    'accepted_at' => $errand->accepted_at,
                    'in_progress_at' => $errand->in_progress_at,
                    'finished_at' => Carbon::now(),
                ]);

                $clientProfilePicture = 'uploads/history/profile_pictures/default.jpg';
                $taskerProfilePicture = 'uploads/history/profile_pictures/default.jpg';

                if (
                    $client &&
                    $client->profile_picture &&
                    $client->profile_picture !== 'uploads/profile_pictures/default.jpg' &&
                    Storage::disk('public')->exists($client->profile_picture)
                ) {
                    $extension = pathinfo($client->profile_picture, PATHINFO_EXTENSION);
                    $clientProfilePicture = 'uploads/history/profile_pictures/client_' . $history->errand_id . '.' . $extension;

                    Storage::disk('public')->copy(
                        $client->profile_picture,
                        $clientProfilePicture
                    );
                }

                if (
                    $tasker &&
                    $tasker->profile_picture &&
                    $tasker->profile_picture !== 'uploads/profile_pictures/default.jpg' &&
                    Storage::disk('public')->exists($tasker->profile_picture)
                ) {
                    $extension = pathinfo($tasker->profile_picture, PATHINFO_EXTENSION);
                    $taskerProfilePicture = 'uploads/history/profile_pictures/tasker_' . $history->errand_id . '.' . $extension;

                    Storage::disk('public')->copy(
                        $tasker->profile_picture,
                        $taskerProfilePicture
                    );
                }

                $history->update([
                    'client_profile_picture' => $clientProfilePicture,
                    'tasker_profile_picture' => $taskerProfilePicture,
                ]);

                $attachment = $errand->attachment;

                if ($attachment && Storage::disk('public')->exists($attachment->file_path)) {
                    $extension = pathinfo(
                        $attachment->file_path,
                        PATHINFO_EXTENSION
                    );

                    $historyPath =
                        'uploads/history/attachments/' .
                        Str::uuid() . '.' . $extension;

                    Storage::disk('public')->copy(
                        $attachment->file_path,
                        $historyPath
                    );

                    HistoryAttachment::create([
                        'history_errand_id' => $history->id,
                        'file_path' => $historyPath,
                        'original_name' => $attachment->original_name,
                        'uploaded_at' => $attachment->uploaded_at,
                    ]);

                    Storage::disk('public')->delete($attachment->file_path);
                }

                $errand_id = $errand->id;
                $errand_title = $errand->title;
                
                $admin_full_name = trim("{$admin->first_name} " . ($admin->middle_name ? "{$admin->middle_name} " : "") . "{$admin->last_name}");
                
                $client_id = $errand->client_id;
                $client_full_name = trim("{$client->first_name} " . ($client->middle_name ? "{$client->middle_name} " : "") . "{$client->last_name}");

                $tasker_id = $errand->tasker_id;
                $tasker_full_name = $tasker ? trim("{$tasker->first_name} " . ($tasker->middle_name ? "{$tasker->middle_name} " : "") . "{$tasker->last_name}") : 'Tasker';


                if ($errand->status === 'dispute') {
                    $client_msg = "Your Errand: \"{$errand_title}\" (ID: {$errand_id}) dispute has been resolved and marked as completed by Administrator: \"{$admin_full_name}\" (ID: {$admin->id}) and moved to history.";
                    $tasker_msg = "Your Assigned Errand: \"{$errand_title}\" (ID: {$errand_id}) dispute has been resolved and marked as completed by Administrator: \"{$admin_full_name}\" (ID: {$admin->id}) and moved to history.";
                    $admin_msg = "Errand Dispute Marked Complete | Errand: \"{$errand_title}\" (ID: {$errand_id}) | Client: \"{$client_full_name}\" (ID: {$client_id}) | Tasker: \"{$tasker_full_name}\" (ID: {$tasker_id}) | Marked Complete by Admin: \"{$admin_full_name}\" (ID: {$admin->id}). Transferred to history.";
                } else {
                    $client_msg = "Your Errand: \"{$errand_title}\" (ID: {$errand_id}) has been confirmed as completed by Administrator: \"{$admin_full_name}\" (ID: {$admin->id}) and has been moved to history.";
                    $tasker_msg = "Your Assigned Errand: \"{$errand_title}\" (ID: {$errand_id}) has been confirmed as completed by Administrator: \"{$admin_full_name}\" (ID: {$admin->id}) and has been moved to history.";
                    $admin_msg = "Errand Confirmed Completed | Errand: \"{$errand_title}\" (ID: {$errand_id}) | Client: \"{$client_full_name}\" (ID: {$client_id}) | Tasker: \"{$tasker_full_name}\" (ID: {$tasker_id}) | Confirmed Complete by Admin: \"{$admin_full_name}\" (ID: {$admin->id}). Transferred to history.";
                }

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

                $otherAdmins = User::where('role', 'admin')->where('id', '!=', $admin->id)->get();
                foreach ($otherAdmins as $adm) {
                    Notification::create([
                        'user_id' => $adm->id,
                        'type' => $notif_type,
                        'message' => $admin_msg,
                        'link' => $admin_link,
                        'is_read' => false,
                    ]);
                }

                $errand->delete();

                return $history;
            });

            return response()->json([
                'success' => true,
                'message' => 'Errand completed and moved to history successfully.',
                'history_id' => $history->id,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateFailed(Request $request, $id)
    {
        $admin = $request->user();

        $errand = Errand::with('attachment')
            ->where('id', $id)
            ->where('status', 'dispute')
            ->first();

        if (!$errand) {
            return response()->json([
                'success' => false,
                'message' => 'Errand not found or is not in a dispute state.'
            ], 404);
        }

        $client = User::find($errand->client_id);
        $tasker = $errand->tasker_id ? User::find($errand->tasker_id) : null;

        try {
            $history = DB::transaction(function () use ($errand, $admin, $client, $tasker) {

                $history = History::create([
                    'errand_id' => $errand->id,
                    'title' => $errand->title,
                    'category' => $errand->category,
                    'description' => $errand->description,
                    'fee' => $errand->fee,
                    'deadline' => $errand->deadline,
                    'location' => $errand->location,
                    'contact' => $errand->contact,
                    'status' => 'failed',

                    'client_id' => $client?->id,
                    'client_last_name' => $client?->last_name,
                    'client_first_name' => $client?->first_name,
                    'client_middle_name' => $client?->middle_name,
                    'client_email' => $client?->email,
                    'client_contact_number' => $client?->contact_number,

                    'tasker_id' => $tasker?->id,
                    'tasker_last_name' => $tasker?->last_name,
                    'tasker_first_name' => $tasker?->first_name,
                    'tasker_middle_name' => $tasker?->middle_name,
                    'tasker_email' => $tasker?->email,
                    'tasker_contact_number' => $tasker?->contact_number,

                    'created_at' => $errand->created_at,
                    'accepted_at' => $errand->accepted_at,
                    'in_progress_at' => $errand->in_progress_at,
                    'finished_at' => Carbon::now(),
                ]);

                $clientProfilePicture = 'uploads/history/profile_pictures/default.jpg';
                $taskerProfilePicture = 'uploads/history/profile_pictures/default.jpg';

                if (
                    $client &&
                    $client->profile_picture &&
                    $client->profile_picture !== 'uploads/profile_pictures/default.jpg' &&
                    Storage::disk('public')->exists($client->profile_picture)
                ) {
                    $extension = pathinfo($client->profile_picture, PATHINFO_EXTENSION);
                    $clientProfilePicture = 'uploads/history/profile_pictures/client_' . $history->errand_id . '.' . $extension;

                    Storage::disk('public')->copy(
                        $client->profile_picture,
                        $clientProfilePicture
                    );
                }

                if (
                    $tasker &&
                    $tasker->profile_picture &&
                    $tasker->profile_picture !== 'uploads/profile_pictures/default.jpg' &&
                    Storage::disk('public')->exists($tasker->profile_picture)
                ) {
                    $extension = pathinfo($tasker->profile_picture, PATHINFO_EXTENSION);
                    $taskerProfilePicture = 'uploads/history/profile_pictures/tasker_' . $history->errand_id . '.' . $extension;

                    Storage::disk('public')->copy(
                        $tasker->profile_picture,
                        $taskerProfilePicture
                    );
                }

                $history->update([
                    'client_profile_picture' => $clientProfilePicture,
                    'tasker_profile_picture' => $taskerProfilePicture,
                ]);

                $attachment = $errand->attachment;

                if ($attachment && Storage::disk('public')->exists($attachment->file_path)) {
                    $extension = pathinfo(
                        $attachment->file_path,
                        PATHINFO_EXTENSION
                    );

                    $historyPath =
                        'uploads/history/attachments/' .
                        Str::uuid() . '.' . $extension;

                    Storage::disk('public')->copy(
                        $attachment->file_path,
                        $historyPath
                    );

                    HistoryAttachment::create([
                        'history_errand_id' => $history->id,
                        'file_path' => $historyPath,
                        'original_name' => $attachment->original_name,
                        'uploaded_at' => $attachment->uploaded_at,
                    ]);

                    Storage::disk('public')->delete($attachment->file_path);
                }

                $errand_id = $errand->id;
                $errand_title = $errand->title;
                
                $admin_full_name = trim("{$admin->first_name} " . ($admin->middle_name ? "{$admin->middle_name} " : "") . "{$admin->last_name}");
                $client_id = $errand->client_id;
                $tasker_id = $errand->tasker_id;

                $client_msg = "Your Errand: \"{$errand_title}\" (ID: {$errand_id}) dispute has been marked as failed by Administrator: \"{$admin_full_name}\" (ID: {$admin->id}) and moved to history.";
                $tasker_msg = "Your Assigned Errand: \"{$errand_title}\" (ID: {$errand_id}) dispute has been marked as failed by Administrator: \"{$admin_full_name}\" (ID: {$admin->id}) and moved to history.";
                $admin_msg = "Errand Dispute Marked Failed | Errand: \"{$errand_title}\" (ID: {$errand_id}) | Marked Failed by Admin: \"{$admin_full_name}\" (ID: {$admin->id}). Transferred to history.";

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

                $otherAdmins = User::where('role', 'admin')->where('id', '!=', $admin->id)->get();
                foreach ($otherAdmins as $adm) {
                    Notification::create([
                        'user_id' => $adm->id,
                        'type' => $notif_type,
                        'message' => $admin_msg,
                        'link' => $admin_link,
                        'is_read' => false,
                    ]);
                }

                $errand->delete();

                return $history;
            });

            return response()->json([
                'success' => true,
                'message' => 'Errand marked as failed and moved to history successfully.',
                'history_id' => $history->id,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteHistory(Request $request, $id)
    {
        $admin = $request->user();

        $history = History::with('attachment')
            ->where('errand_id', $id)
            ->first();

        if (!$history) {
            return response()->json([
                'success' => false, 
                'message' => 'History record not found.'
            ], 404);
        }

        $client_id = $history->client_id;
        $tasker_id = $history->tasker_id;

        $errand_id = $history->errand_id;
        $history_title = $history->title;

        if ($history->attachment) {
            Storage::disk('public')->delete($history->attachment->file_path);
            $history->attachment()->delete();
        }

        if (
            $history->client_profile_picture &&
            $history->client_profile_picture !== 'uploads/history/profile_pictures/default.jpg' &&
            Storage::disk('public')->exists($history->client_profile_picture)
        ) {
            Storage::disk('public')->delete($history->client_profile_picture);
        }

        if (
            $history->tasker_profile_picture &&
            $history->tasker_profile_picture !== 'uploads/history/profile_pictures/default.jpg' &&
            Storage::disk('public')->exists($history->tasker_profile_picture)
        ) {
            Storage::disk('public')->delete($history->tasker_profile_picture);
        }

        $history->delete();

        $admin_full_name = trim("{$admin->first_name} " . ($admin->middle_name ? "{$admin->middle_name} " : "") . "{$admin->last_name}");
        $client = $client_id ? User::find($client_id) : null;
        $tasker = $tasker_id ? User::find($tasker_id) : null;
        $client_full_name = $client ? trim("{$client->first_name} " . ($client->middle_name ? "{$client->middle_name} " : "") . "{$client->last_name}") : 'N/A';
        $tasker_full_name = $tasker ? trim("{$tasker->first_name} " . ($tasker->middle_name ? "{$tasker->middle_name} " : "") . "{$tasker->last_name}") : 'N/A';

        $client_msg = "Your History Errand: \"{$history_title}\" (ID: {$errand_id}) has been deleted by Administrator: \"{$admin_full_name}\" (ID: {$admin->id}).";
        $tasker_msg = "Your History Errand: \"{$history_title}\" (ID: {$errand_id}) has been deleted by Administrator: \"{$admin_full_name}\" (ID: {$admin->id}).";
        $admin_msg = "History Errand Deleted | Errand: \"{$history_title}\" (ID: {$errand_id}) | Client: \"{$client_full_name}\" (ID: {$client_id}) | Tasker: \"{$tasker_full_name}\" (ID: {$tasker_id}) | Deleted by Admin: \"{$admin_full_name}\" (ID: {$admin->id}).";

        $notif_type = "errand";

        if ($client_id && $history->client_deleted === false) {
            Notification::create([
                'user_id' => $client_id,
                'type' => $notif_type,
                'message' => $client_msg,
                'link' => null,
                'is_read' => false,
            ]);
        }

        if ($tasker_id && $history->tasker_deleted === false) {
            Notification::create([
                'user_id' => $tasker_id,
                'type' => $notif_type,
                'message' => $tasker_msg,
                'link' => null,
                'is_read' => false,
            ]);
        }

        $otherAdmins = User::where('role', 'admin')->where('id', '!=', $admin->id)->get();
        foreach ($otherAdmins as $adm) {
            Notification::create([
                'user_id' => $adm->id,
                'type' => $notif_type,
                'message' => $admin_msg,
                'link' => null,
                'is_read' => false,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'History record deleted successfully.',
        ]);
    }
}