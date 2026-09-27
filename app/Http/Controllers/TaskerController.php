<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Notification;
use App\Models\Errand;
use App\Models\ErrandAttachment;
use App\Models\History;
use App\Models\HistoryAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class TaskerController extends Controller
{
    public function pendingMetrics()
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
        $perPage = (int) $request->query('per_page', 8);

        $allowedPerPage = [4, 8, 16, 32];

        if (!in_array($perPage, $allowedPerPage)) {
            $perPage = 8;
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
        $taskerId = $request->user()->id;

        $metrics = Errand::selectRaw('status, COUNT(*) as total')
            ->where('tasker_id', $taskerId)
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
        $perPage = (int) $request->query('per_page', 8);

        $allowedPerPage = [4, 8, 16, 32];

        if (!in_array($perPage, $allowedPerPage)) {
            $perPage = 8;
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
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
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
                'cancel_requested_by_tasker' => $errand->cancel_requested_by_tasker,
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

    public function historyMetrics(Request $request)
    {
        $taskerId = $request->user()->id;

        $metrics = History::where('tasker_id', $taskerId)
            ->where('tasker_deleted', false)
            ->selectRaw('status, COUNT(*) as total')
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

    public function historyErrands(Request $request)
    {
        $taskerId = $request->user()->id;

        $search = $request->query('search');
        $perPage = (int) $request->query('per_page', 8);

        $allowedPerPage = [4, 8, 16, 32];

        if (!in_array($perPage, $allowedPerPage)) {
            $perPage = 8;
        }

        $query = History::where('tasker_id', $taskerId)
            ->where('tasker_deleted', false)
            ->whereIn('status', [
                'completed', 
                'failed'
            ]);

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

    public function getErrandDetails(Request $request, $id)
    {
        $taskerId = $request->user()->id;

        $errand = Errand::with(['attachment', 'client', 'payment'])
            ->where('id', $id)
            ->where(function ($q) use ($taskerId) {
                $q->whereNull('tasker_id')
                    ->orWhere('tasker_id', $taskerId);
            })
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
                        "{$errand->client->last_name}"
                    ),
                    'email' => $errand->client->email,
                    'contact_number' => $errand->client->contact_number,
                    'profile_picture' => $errand->client->profile_picture
                        ? Storage::url($errand->client->profile_picture)
                        : asset('storage/uploads/profile_pictures/default.jpg')
                ] : null,

                'payment' => $errand->payment ? [
                    'method' => Str::title($errand->payment->method),
                    'status' => Str::title(str_replace('_', ' ', $errand->payment->status)),
                    'raw_status' => $errand->payment->status,
                    'status_class' => 'status-field status-' . $errand->payment->status,
                    'amount' => '₱' . number_format($errand->payment->amount, 2),
                    'paid_at' => $errand->payment->paid_at
                        ? Carbon::parse($errand->payment->paid_at)->timezone('Asia/Manila')->format('M d, Y h:i A')
                        : 'Not paid yet',
                ] : null,
            ];

            return response()->json([
                'success' => true,
                'errand' => $formattedErrand
            ]);
        }

        $history = History::with('attachment')
            ->where('errand_id', $id)
            ->where('tasker_id', $taskerId)
            ->where('tasker_deleted', false)
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

            'payment' => $history->payment_method ? [
                'method' => Str::title($history->payment_method),
                'status' => Str::title(str_replace('_', ' ', $history->payment_status)),
                'raw_status' => $history->payment_status,
                'status_class' => 'status-field status-' . $history->payment_status,
                'amount' => '₱' . number_format($history->payment_amount, 2),
                'paid_at' => $history->payment_paid_at
                    ? Carbon::parse($history->payment_paid_at)->timezone('Asia/Manila')->format('M d, Y h:i A')
                    : 'None',
            ] : null,
        ];

        return response()->json([
            'success' => true,
            'errand' => $formattedErrand
        ]);
    }

    public function acceptErrand(Request $request, $id)
    {
        $tasker = $request->user();

        $errand = Errand::with('payment')
            ->where('id', $id)
            ->where('status', 'pending')
            ->first();

        if (!$errand) {
            return response()->json([
                'success' => false,
                'message' => 'Errand not found.'
            ], 404);
        }

        $errand->status = 'accepted';
        $errand->tasker_id = $tasker->id;
        $errand->accepted_at = Carbon::now();
        $errand->save();

        $errand_id = $errand->id;
        $errand_title = $errand->title;
        $errand_fee = number_format((float) $errand->fee, 2);

        $client_id = $errand->client_id;
        $client = User::find($client_id);
        $client_full_name = trim("{$client->first_name} " . ($client->middle_name ? "{$client->middle_name} " : "") . "{$client->last_name}");

        $tasker_id = $tasker->id;
        $tasker_full_name = trim("{$tasker->first_name} " . ($tasker->middle_name ? "{$tasker->middle_name} " : "") . "{$tasker->last_name}");

        $has_payment_proof = $errand->payment &&
            $errand->payment->proof_path &&
            $errand->payment->status === 'proof_submitted';

        if ($has_payment_proof) {
            $client_msg = "Your Errand: \"{$errand_title}\" (ID: {$errand_id}) has been accepted by Tasker: \"{$tasker_full_name}\" (ID: {$tasker_id}). Your GCash payment proof has already been submitted and is now waiting for admin verification.";

            $admin_msg = "Errand Accepted | Errand: \"{$errand_title}\" (ID: {$errand_id}) | Created by Client: \"{$client_full_name}\" (ID: {$client_id}) | Accepted by Tasker: \"{$tasker_full_name}\" (ID: {$tasker_id}) | Amount: ₱{$errand_fee} | Payment proof has already been submitted and is waiting for verification.";
        } else {
            $client_msg = "Your Errand: \"{$errand_title}\" (ID: {$errand_id}) has been accepted by Tasker: \"{$tasker_full_name}\" (ID: {$tasker_id}). Please pay the exact amount of ₱{$errand_fee} via GCash and submit your payment proof.";

            $admin_msg = "Errand Accepted | Errand: \"{$errand_title}\" (ID: {$errand_id}) | Created by Client: \"{$client_full_name}\" (ID: {$client_id}) | Accepted by Tasker: \"{$tasker_full_name}\" (ID: {$tasker_id}) | Amount: ₱{$errand_fee}. Waiting for the client's GCash payment proof.";
        }

        $notif_type = "errand";

        $client_link = route('client.view-errand', ['id' => $errand_id]) . '?from=notifications';
        $admin_link = route('admin.view-errand', ['id' => $errand_id]) . '?from=notifications';

        Notification::create([
            'user_id' => $client_id,
            'type' => $notif_type,
            'message' => $client_msg,
            'link' => $client_link,
            'is_read' => false,
        ]);

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

    public function markAwaitingConfirmation(Request $request, $id)
    {
        $tasker = $request->user();

        $errand = Errand::where('id', $id)
            ->where('status', 'in_progress')
            ->where('tasker_id', $tasker->id)
            ->first();

        if (!$errand) {
            return response()->json([
                'success' => false, 
                'message' => 'Errand not found.'
            ], 404);
        }

        $errand->status = 'awaiting_confirmation';
        $errand->save();

        $errand_id = $errand->id;
        $errand_title = $errand->title;
        
        $client_id = $errand->client_id;
        $client = User::find($client_id);
        $client_full_name = $client ? trim("{$client->first_name} " . ($client->middle_name ? "{$client->middle_name} " : "") . "{$client->last_name}") : 'Client';

        $tasker_id = $tasker->id;
        $tasker_full_name = trim("{$tasker->first_name} " . ($tasker->middle_name ? "{$tasker->middle_name} " : "") . "{$tasker->last_name}");

        $client_msg = "Your Errand: \"{$errand_title}\" (ID: {$errand_id}) is awaiting your confirmation. Tasker: \"{$tasker_full_name}\" (ID: {$tasker_id}) has marked the errand as completed.";

        $admin_msg = "Errand Awaiting Confirmation | Errand: \"{$errand_title}\" (ID: {$errand_id}) | Client: \"{$client_full_name}\" (ID: {$client_id}) | Tasker: \"{$tasker_full_name}\" (ID: {$tasker_id}) | The tasker has marked the errand as completed and is awaiting client confirmation.";

        $notif_type = "errand";

        $client_link = route('client.view-errand', ['id' => $errand_id]) . '?from=notifications';
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
            'message' => 'Errand marked as awaiting confirmation successfully!',
        ]);
    }

    public function requestCancellation(Request $request, $id)
    {
        $tasker = $request->user();

        $errand = Errand::where('id', $id)
            ->whereIn('status', ['accepted', 'in_progress'])
            ->where('tasker_id', $tasker->id)
            ->first();

        if (!$errand) {
            return response()->json([
                'success' => false, 
                'message' => 'Errand not found or cannot be cancelled.'
            ], 404);
        }

        if ($errand->cancel_requested_by_tasker) {
            return response()->json([
                'success' => false, 
                'message' => 'Cancellation has already been requested for this errand.'
            ], 400);
        }

        $errand->cancel_requested_by_tasker = true;
        $errand->save();

        $errand_id = $errand->id;
        $errand_title = $errand->title;
        
        $tasker_id = $tasker->id;
        $user_full_name = trim("{$tasker->first_name} " . ($tasker->middle_name ? "{$tasker->middle_name} " : "") . "{$tasker->last_name}");

        $client_id = $errand->client_id;

        $notif_type = "errand";

        $client_msg = "Tasker \"{$user_full_name}\" (ID: {$tasker_id}) has requested a cancellation for the Errand: \"{$errand_title}\" (ID: {$errand_id}). Waiting for admin decision.";
        $admin_msg = "Cancellation Requested | Errand: \"{$errand_title}\" (ID: {$errand_id}) | Tasker: \"{$user_full_name}\" (ID: {$tasker_id}) has a cancellation request.";

        $client_link = route('client.view-errand', ['id' => $errand_id]) . '?from=notifications';
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
            'message' => 'Cancellation request submitted successfully. Waiting for admin approval.',
        ]);
    }

    public function destroyHistory(Request $request, $id)
    {
        $taskerId = $request->user()->id;

        $history = History::where('errand_id', $id)
            ->where('tasker_id', $taskerId)
            ->first();

        if (!$history) {
            return response()->json([
                'success' => false, 
                'message' => 'History record not found.'
            ], 404);
        }

        $history->tasker_deleted = true;
        $history->save();

        return response()->json([
            'success' => true,
            'message' => 'Errand removed from history successfully.'
        ]);
    }
}