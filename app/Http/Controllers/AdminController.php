<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;
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

    public function usersMetrics()
    {
        $metrics = User::selectRaw('role, COUNT(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role');

        $admin = $metrics['admin'] ?? 0;
        $tasker = $metrics['tasker'] ?? 0;
        $client = $metrics['client'] ?? 0;
        $total = $metrics->sum();

        return response()->json([
            'success' => true,
            'metrics' => [
                'admin' => $admin,
                'tasker' => $tasker,
                'client' => $client,
                'total' => $total,
            ],
        ]);
    }

    public function allUsers(Request $request)
    {
        $search = $request->query('search');
        $perPage = (int) $request->query('per_page', 10);

        $allowedPerPage = [5, 10, 25, 50];

        if (!in_array($perPage, $allowedPerPage)) {
            $perPage = 10;
        }

        $query = User::query()
            ->where('id', '!=', $request->user()->id);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('middle_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('role', 'like', "%{$search}%");
            });
        }

        $users = $query
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        $formattedUsers = collect($users->items())->map(function ($user) {
            return [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'middle_name' => $user->middle_name ?? '',
                'last_name' => $user->last_name,
                'email' => $user->email,
                'contact_number' => $user->contact_number,
                'profile_picture' => asset($user->profile_picture),
                'role' => Str::title($user->role),
                'raw_role' => strtolower($user->role),
                'role_class' => 'role-badge role-' . strtolower($user->role),
                'created_at' => $user->created_at 
                    ? Carbon::parse($user->created_at)->timezone('Asia/Manila')->format('M d, Y h:i A') 
                    : '—',
            ];
        });

        return response()->json([
            'success' => true,
            'users' => $formattedUsers,
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'from' => $users->firstItem(),
                'to' => $users->lastItem(),
            ],
        ]);
    }

    public function getErrandDetails($id)
    {
        $errand = Errand::with(['attachment', 'client', 'tasker', 'payment'])
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

                'payment' => $errand->payment ? [
                    'method' => Str::title($errand->payment->method),
                    'status' => Str::title(str_replace('_', ' ', $errand->payment->status)),
                    'raw_status' => $errand->payment->status,
                    'status_class' => 'status-field status-' . $errand->payment->status,
                    'amount' => '₱' . number_format($errand->payment->amount, 2),
                    'paid_at' => $errand->payment->paid_at
                        ? Carbon::parse($errand->payment->paid_at)->timezone('Asia/Manila')->format('M d, Y h:i A')
                        : 'Not paid yet',

                    'proof' => $errand->payment->proof_path ? [
                        'name' => basename($errand->payment->proof_path),
                        'url' => Storage::url($errand->payment->proof_path)
                    ] : null,

                    'refund_proof' => $errand->payment->refund_proof_path ? [
                        'name' => basename($errand->payment->refund_proof_path),
                        'url' => Storage::url($errand->payment->refund_proof_path)
                    ] : null,
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

            'payment' => $history->payment_method ? [
                'method' => Str::title($history->payment_method),
                'status' => Str::title(str_replace('_', ' ', $history->payment_status)),
                'raw_status' => $history->payment_status,
                'status_class' => 'status-field status-' . $history->payment_status,
                'amount' => '₱' . number_format($history->payment_amount, 2),
                'paid_at' => $history->payment_paid_at
                    ? Carbon::parse($history->payment_paid_at)->timezone('Asia/Manila')->format('M d, Y h:i A')
                    : 'None',

                'proof' => $history->payment_proof_path ? [
                    'name' => basename($history->payment_proof_path),
                    'url' => Storage::url($history->payment_proof_path)
                ] : null,

                'refund_proof' => $history->payment_refund_proof_path ? [
                    'name' => basename($history->payment_refund_proof_path),
                    'url' => Storage::url($history->payment_refund_proof_path)
                ] : null,
            ] : null,
        ];

        return response()->json([
            'success' => true,
            'errand' => $formattedErrand
        ]);
    }

    public function acceptReceipt(Request $request, $id)
    {
        $admin = $request->user();

        $errand = Errand::where('id', $id)
            ->where('status', 'accepted')
            ->first();

        if (!$errand) {
            return response()->json([
                'success' => false,
                'message' => 'Errand not found or is not in an accepted state.'
            ], 404);
        }

        $payment = $errand->payment;

        if (!$payment || !$payment->proof_path) {
            return response()->json([
                'success' => false,
                'message' => 'Payment receipt not found.'
            ], 404);
        }

        $payment->status = 'paid';
        $payment->paid_at = Carbon::now();
        $payment->save();

        $errand->status = 'in_progress';
        $errand->in_progress_at = Carbon::now();
        $errand->save();

        $errand_id = $errand->id;
        $errand_title = $errand->title;

        $client_id = $errand->client_id;
        $tasker_id = $errand->tasker_id;

        $admin_full_name = trim(
            "{$admin->first_name} " .
            ($admin->middle_name ? "{$admin->middle_name} " : "") .
            "{$admin->last_name}"
        );

        $client = User::find($client_id);
        $tasker = User::find($tasker_id);

        $client_full_name = $client
            ? trim(
                "{$client->first_name} " .
                ($client->middle_name ? "{$client->middle_name} " : "") .
                "{$client->last_name}"
            )
            : 'Client';

        $tasker_full_name = $tasker
            ? trim(
                "{$tasker->first_name} " .
                ($tasker->middle_name ? "{$tasker->middle_name} " : "") .
                "{$tasker->last_name}"
            )
            : 'Tasker';

        $client_msg = "Your payment receipt for Errand: \"{$errand_title}\" (ID: {$errand_id}) has been accepted by Administrator: \"{$admin_full_name}\" (ID: {$admin->id}). The errand is now in progress.";

        $tasker_msg = "Payment for Errand: \"{$errand_title}\" (ID: {$errand_id}) has been verified by Administrator: \"{$admin_full_name}\" (ID: {$admin->id}). You can now start working on the errand.";

        $admin_msg = "Payment Receipt Accepted | Errand: \"{$errand_title}\" (ID: {$errand_id}) | Client: \"{$client_full_name}\" (ID: {$client_id}) | Tasker: \"{$tasker_full_name}\" (ID: {$tasker_id}) | Accepted by Admin: \"{$admin_full_name}\" (ID: {$admin->id}).";

        $notif_type = 'errand';

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

        $admins = User::where('role', 'admin')
            ->where('id', '!=', $admin->id)
            ->get();

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
            'message' => 'Payment receipt accepted and errand started successfully!',
        ]);
    }

    public function denyReceipt(Request $request, $id)
    {
        $admin = $request->user();

        $errand = Errand::where('id', $id)
            ->where('status', 'accepted')
            ->first();

        if (!$errand) {
            return response()->json([
                'success' => false,
                'message' => 'Errand not found or is not in an accepted state.'
            ], 404);
        }

        $payment = $errand->payment;

        if (!$payment || !$payment->proof_path) {
            return response()->json([
                'success' => false,
                'message' => 'Payment receipt not found.'
            ], 404);
        }

        $proof_path = $payment->proof_path;

        if (Storage::disk('public')->exists($proof_path)) {
            Storage::disk('public')->delete($proof_path);
        }

        $payment->proof_path = null;
        $payment->status = 'pending';
        $payment->proof_submitted_at = null;
        $payment->paid_at = null;
        $payment->save();

        $errand_id = $errand->id;
        $errand_title = $errand->title;
        $client_id = $errand->client_id;

        $admin_full_name = trim(
            "{$admin->first_name} " .
            ($admin->middle_name ? "{$admin->middle_name} " : "") .
            "{$admin->last_name}"
        );

        $client_msg = "Your payment receipt for Errand: \"{$errand_title}\" (ID: {$errand_id}) was denied by Administrator: \"{$admin_full_name}\" (ID: {$admin->id}). Please upload a valid payment receipt.";

        $admin_msg = "Payment Receipt Denied | Errand: \"{$errand_title}\" (ID: {$errand_id}) | Payment receipt was denied by Admin: \"{$admin_full_name}\" (ID: {$admin->id}). The client was asked to upload a valid receipt.";

        $notif_type = 'errand';

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

        $admins = User::where('role', 'admin')
            ->where('id', '!=', $admin->id)
            ->get();

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
            'message' => 'Payment receipt denied. The client can upload a new receipt.',
        ]);
    }

    public function resetErrandToPending(Request $request, $id)
    {
        $admin = $request->user();

        $errand = Errand::with('payment')
            ->where('id', $id)
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

        if ($errand->payment) {
            $errand->payment->status = $errand->payment->proof_path
                ? 'proof_submitted'
                : 'pending';

            $errand->payment->save();
        }

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

        $admins = User::where('role', 'admin')
            ->where('id', '!=', $admin->id)
            ->get();

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
        $requester = $request->input('requester');

        $errand = Errand::with('payment')
            ->where('id', $id)
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

        if ($errand->payment) {
            $errand->payment->status = $errand->payment->proof_path
                ? 'proof_submitted'
                : 'pending';

            $errand->payment->save();
        }

        $errand->status = 'pending';
        $errand->tasker_id = null;
        $errand->accepted_at = null;
        $errand->in_progress_at = null;
        $errand->cancel_requested_by_client = false;
        $errand->cancel_requested_by_tasker = false;
        $errand->save();

        $errand_id = $errand->id;
        $errand_title = $errand->title;

        $admin_full_name = trim(
            "{$admin->first_name} " .
            ($admin->middle_name ? "{$admin->middle_name} " : "") .
            "{$admin->last_name}"
        );

        if ($requester === 'client') {
            $client_msg = "Your cancellation request for Errand: \"{$errand_title}\" (ID: {$errand_id}) was approved by Administrator: \"{$admin_full_name}\" (ID: {$admin->id}). Status reset to Pending.";
            $tasker_msg = "The client's cancellation request for Errand: \"{$errand_title}\" (ID: {$errand_id}) was approved by Administrator: \"{$admin_full_name}\" (ID: {$admin->id}). You are no longer assigned.";
            $admin_msg = "Cancellation Approved (Client Request) | Errand: \"{$errand_title}\" (ID: {$errand_id}) | Reset to Pending by Admin: \"{$admin_full_name}\" (ID: {$admin->id}).";
        } else {
            $client_msg = "The tasker's cancellation request for Errand: \"{$errand_title}\" (ID: {$errand_id}) was approved by Administrator: \"{$admin_full_name}\" (ID: {$admin->id}). Status reset to Pending.";
            $tasker_msg = "Your cancellation request for Errand: \"{$errand_title}\" (ID: {$errand_id}) was approved by Administrator: \"{$admin_full_name}\" (ID: {$admin->id}).";
            $admin_msg = "Cancellation Approved (Tasker Request) | Errand: \"{$errand_title}\" (ID: {$errand_id}) | Reset to Pending by Admin: \"{$admin_full_name}\" (ID: {$admin->id}).";
        }

        $notif_type = 'errand';

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

        $admins = User::where('role', 'admin')
            ->where('id', '!=', $admin->id)
            ->get();

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

        $errand = Errand::with(['attachment', 'payment'])
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

        if ($errand->payment && $errand->payment->proof_path) {
            $proof_path = $errand->payment->proof_path;

            if (Storage::disk('public')->exists($proof_path)) {
                Storage::disk('public')->delete($proof_path);
            }
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

        $otherAdmins = User::where('role', 'admin')
            ->where('id', '!=', $admin->id)
            ->get();

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

        $errand = Errand::with(['attachment', 'payment'])
            ->where('id', $id)
            ->whereIn('status', ['awaiting_confirmation', 'dispute'])
            ->first();

        if (!$errand) {
            return response()->json([
                'success' => false,
                'message' => 'Errand not found or is not in an awaiting confirmation or dispute state.'
            ], 404);
        }

        $payment = $errand->payment;

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment record not found.'
            ], 422);
        }

        if (
            !$payment->proof_path ||
            !Storage::disk('public')->exists($payment->proof_path)
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Payment proof not found.'
            ], 422);
        }

        $client = User::find($errand->client_id);

        $tasker = $errand->tasker_id
            ? User::find($errand->tasker_id)
            : null;

        try {
            $history = DB::transaction(function () use ($errand, $admin, $client, $tasker, $payment) {

                $payment->update([
                    'status' => 'paid',
                    'paid_at' => $payment->paid_at ?? Carbon::now(),
                ]);

                $paymentProofExtension = pathinfo(
                    $payment->proof_path,
                    PATHINFO_EXTENSION
                );

                $paymentProofPath =
                    'uploads/history/payment_proofs/' .
                    'errand_payment_proof_' . $errand->id . '.' . $paymentProofExtension;

                Storage::disk('public')->copy(
                    $payment->proof_path,
                    $paymentProofPath
                );

                $history = History::create([
                    'errand_id' => $errand->id,
                    'title' => $errand->title,
                    'category' => $errand->category,
                    'description' => $errand->description,
                    'fee' => $errand->fee,

                    'payment_method' => $payment->method,
                    'payment_amount' => $payment->amount,
                    'payment_status' => $payment->status,
                    'payment_proof_path' => $paymentProofPath,
                    'payment_proof_submitted_at' => $payment->proof_submitted_at,
                    'payment_paid_at' => $payment->paid_at,

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
                    $extension = pathinfo(
                        $client->profile_picture,
                        PATHINFO_EXTENSION
                    );

                    $clientProfilePicture =
                        'uploads/history/profile_pictures/client_' .
                        $history->errand_id . '.' . $extension;

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
                    $extension = pathinfo(
                        $tasker->profile_picture,
                        PATHINFO_EXTENSION
                    );

                    $taskerProfilePicture =
                        'uploads/history/profile_pictures/tasker_' .
                        $history->errand_id . '.' . $extension;

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

                if (
                    $attachment &&
                    Storage::disk('public')->exists($attachment->file_path)
                ) {
                    $extension = pathinfo(
                        $attachment->file_path,
                        PATHINFO_EXTENSION
                    );

                    $historyPath =
                        'uploads/history/attachments/' .
                        'errand_' . $history->errand_id . '.' . $extension;

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

                Storage::disk('public')->delete($payment->proof_path);

                $errand_id = $errand->id;
                $errand_title = $errand->title;

                $admin_full_name = trim(
                    "{$admin->first_name} " .
                    ($admin->middle_name ? "{$admin->middle_name} " : "") .
                    "{$admin->last_name}"
                );

                $client_id = $errand->client_id;

                $client_full_name = $client
                    ? trim(
                        "{$client->first_name} " .
                        ($client->middle_name ? "{$client->middle_name} " : "") .
                        "{$client->last_name}"
                    )
                    : 'Client';

                $tasker_id = $errand->tasker_id;

                $tasker_full_name = $tasker
                    ? trim(
                        "{$tasker->first_name} " .
                        ($tasker->middle_name ? "{$tasker->middle_name} " : "") .
                        "{$tasker->last_name}"
                    )
                    : 'Tasker';

                if ($errand->status === 'dispute') {
                    $client_msg = "Your Errand: \"{$errand_title}\" (ID: {$errand_id}) dispute has been resolved and marked as completed by Administrator: \"{$admin_full_name}\" (ID: {$admin->id}). The errand, payment details, and payment proof have been moved to history.";
                    $tasker_msg = "Your Assigned Errand: \"{$errand_title}\" (ID: {$errand_id}) dispute has been resolved and marked as completed by Administrator: \"{$admin_full_name}\" (ID: {$admin->id}). The errand, payment details, and payment proof have been moved to history.";
                    $admin_msg = "Errand Dispute Marked Complete | Errand: \"{$errand_title}\" (ID: {$errand_id}) | Client: \"{$client_full_name}\" (ID: {$client_id}) | Tasker: \"{$tasker_full_name}\" (ID: {$tasker_id}) | Marked Complete by Admin: \"{$admin_full_name}\" (ID: {$admin->id}). The errand, payment details, and payment proof have been transferred to history.";
                } else {
                    $client_msg = "Your Errand: \"{$errand_title}\" (ID: {$errand_id}) has been confirmed as completed by Administrator: \"{$admin_full_name}\" (ID: {$admin->id}). The errand, payment details, and payment proof have been moved to history.";
                    $tasker_msg = "Your Assigned Errand: \"{$errand_title}\" (ID: {$errand_id}) has been confirmed as completed by Administrator: \"{$admin_full_name}\" (ID: {$admin->id}). The errand, payment details, and payment proof have been moved to history.";
                    $admin_msg = "Errand Confirmed Completed | Errand: \"{$errand_title}\" (ID: {$errand_id}) | Client: \"{$client_full_name}\" (ID: {$client_id}) | Tasker: \"{$tasker_full_name}\" (ID: {$tasker_id}) | Confirmed Complete by Admin: \"{$admin_full_name}\" (ID: {$admin->id}). The errand, payment details, and payment proof have been transferred to history.";
                }

                $notif_type = 'errand';

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

                $otherAdmins = User::where('role', 'admin')
                    ->where('id', '!=', $admin->id)
                    ->get();

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

    public function submitRefundProof(Request $request, $id)
    {
        $admin = $request->user();

        $validated = $request->validate([
            'refund_proof' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:10240'],
        ]);

        DB::beginTransaction();

        try {
            $errand = Errand::with('payment')
                ->where('id', $id)
                ->where('status', 'dispute')
                ->first();

            if (!$errand) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Errand not found or is not in dispute.'
                ], 404);
            }

            if (!$errand->payment) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Payment record not found.'
                ], 422);
            }

            if ($errand->payment->status !== 'paid') {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Payment must be paid before submitting a refund receipt.'
                ], 422);
            }

            if (
                $errand->payment->refund_proof_path &&
                Storage::disk('public')->exists($errand->payment->refund_proof_path)
            ) {
                Storage::disk('public')->delete(
                    $errand->payment->refund_proof_path
                );
            }

            $file = $request->file('refund_proof');
            $path = $file->store('uploads/refund_proofs', 'public');

            $errand->payment->refund_proof_path = $path;
            $errand->payment->refund_submitted_at = Carbon::now();
            $errand->payment->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Refund receipt uploaded successfully.'
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function updateFailed(Request $request, $id)
    {
        $admin = $request->user();

        $errand = Errand::with(['attachment', 'payment'])
            ->where('id', $id)
            ->where('status', 'dispute')
            ->first();

        if (!$errand) {
            return response()->json([
                'success' => false,
                'message' => 'Errand not found or is not in a dispute state.'
            ], 404);
        }

        $payment = $errand->payment;

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment record not found.'
            ], 422);
        }

        if ($payment->status !== 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Payment must be paid before marking the errand as failed.'
            ], 422);
        }

        if (
            !$payment->proof_path ||
            !Storage::disk('public')->exists($payment->proof_path)
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Original payment proof not found.'
            ], 422);
        }

        if (
            !$payment->refund_proof_path ||
            !Storage::disk('public')->exists($payment->refund_proof_path)
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Refund receipt must be uploaded before marking the errand as failed.'
            ], 422);
        }

        $client = User::find($errand->client_id);

        $tasker = $errand->tasker_id
            ? User::find($errand->tasker_id)
            : null;

        try {
            $history = DB::transaction(function () use ($errand, $admin, $client, $tasker, $payment) {

                $payment->update([
                    'status' => 'refunded',
                    'refunded_at' => Carbon::now(),
                ]);

                $paymentProofExtension = pathinfo(
                    $payment->proof_path,
                    PATHINFO_EXTENSION
                );

                $paymentProofPath =
                    'uploads/history/payment_proofs/' .
                    'errand_payment_proof_' . $errand->id . '.' . $paymentProofExtension;

                Storage::disk('public')->copy(
                    $payment->proof_path,
                    $paymentProofPath
                );

                $refundProofExtension = pathinfo(
                    $payment->refund_proof_path,
                    PATHINFO_EXTENSION
                );

                $refundProofPath =
                    'uploads/history/refund_proofs/' .
                    'errand_refund_proof_' . $errand->id . '.' . $refundProofExtension;

                Storage::disk('public')->copy(
                    $payment->refund_proof_path,
                    $refundProofPath
                );

                $history = History::create([
                    'errand_id' => $errand->id,
                    'title' => $errand->title,
                    'category' => $errand->category,
                    'description' => $errand->description,
                    'fee' => $errand->fee,

                    'payment_method' => $payment->method,
                    'payment_amount' => $payment->amount,
                    'payment_status' => $payment->status,
                    'payment_proof_path' => $paymentProofPath,
                    'payment_proof_submitted_at' => $payment->proof_submitted_at,
                    'payment_paid_at' => $payment->paid_at,

                    'payment_refund_proof_path' => $refundProofPath,
                    'payment_refund_proof_submitted_at' => $payment->refund_submitted_at,
                    'payment_refunded_at' => $payment->refunded_at,

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
                    $extension = pathinfo(
                        $client->profile_picture,
                        PATHINFO_EXTENSION
                    );

                    $clientProfilePicture =
                        'uploads/history/profile_pictures/client_' .
                        $history->errand_id . '.' . $extension;

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
                    $extension = pathinfo(
                        $tasker->profile_picture,
                        PATHINFO_EXTENSION
                    );

                    $taskerProfilePicture =
                        'uploads/history/profile_pictures/tasker_' .
                        $history->errand_id . '.' . $extension;

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

                if (
                    $attachment &&
                    Storage::disk('public')->exists($attachment->file_path)
                ) {
                    $extension = pathinfo(
                        $attachment->file_path,
                        PATHINFO_EXTENSION
                    );

                    $historyPath =
                        'uploads/history/attachments/' .
                        'errand_' . $history->errand_id . '.' . $extension;

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

                Storage::disk('public')->delete($payment->proof_path);
                Storage::disk('public')->delete($payment->refund_proof_path);

                $errand_id = $errand->id;
                $errand_title = $errand->title;

                $admin_full_name = trim(
                    "{$admin->first_name} " .
                    ($admin->middle_name ? "{$admin->middle_name} " : "") .
                    "{$admin->last_name}"
                );

                $client_id = $errand->client_id;

                $client_full_name = $client
                    ? trim(
                        "{$client->first_name} " .
                        ($client->middle_name ? "{$client->middle_name} " : "") .
                        "{$client->last_name}"
                    )
                    : 'Client';

                $tasker_id = $errand->tasker_id;

                $tasker_full_name = $tasker
                    ? trim(
                        "{$tasker->first_name} " .
                        ($tasker->middle_name ? "{$tasker->middle_name} " : "") .
                        "{$tasker->last_name}"
                    )
                    : 'Tasker';

                $client_msg = "Your Errand: \"{$errand_title}\" (ID: {$errand_id}) dispute has been resolved and marked as failed by Administrator: \"{$admin_full_name}\" (ID: {$admin->id}). The paid amount has been refunded and the errand, payment details, payment proof, and refund proof have been moved to history.";

                $tasker_msg = "Your Assigned Errand: \"{$errand_title}\" (ID: {$errand_id}) dispute has been resolved and marked as failed by Administrator: \"{$admin_full_name}\" (ID: {$admin->id}). The paid amount has been refunded and the errand, payment details, payment proof, and refund proof have been moved to history.";

                $admin_msg = "Errand Dispute Marked Failed & Refunded | Errand: \"{$errand_title}\" (ID: {$errand_id}) | Client: \"{$client_full_name}\" (ID: {$client_id}) | Tasker: \"{$tasker_full_name}\" (ID: {$tasker_id}) | Marked Failed by Admin: \"{$admin_full_name}\" (ID: {$admin->id}). The payment was marked as refunded and the errand, payment details, payment proof, and refund proof were transferred to history.";

                $notif_type = 'errand';

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

                $otherAdmins = User::where('role', 'admin')
                    ->where('id', '!=', $admin->id)
                    ->get();

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
                'message' => 'Errand marked as failed, payment refunded, and moved to history successfully.',
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

        $client_deleted = $history->client_deleted;
        $tasker_deleted = $history->tasker_deleted;

        $admin_full_name = trim(
            "{$admin->first_name} " .
            ($admin->middle_name ? "{$admin->middle_name} " : "") .
            "{$admin->last_name}"
        );

        $client_full_name = trim(
            "{$history->client_first_name} " .
            ($history->client_middle_name ? "{$history->client_middle_name} " : "") .
            "{$history->client_last_name}"
        );

        $tasker_full_name = trim(
            "{$history->tasker_first_name} " .
            ($history->tasker_middle_name ? "{$history->tasker_middle_name} " : "") .
            "{$history->tasker_last_name}"
        );

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

        if (
            $history->payment_proof_path &&
            Storage::disk('public')->exists($history->payment_proof_path)
        ) {
            Storage::disk('public')->delete($history->payment_proof_path);
        }

        if (
            $history->payment_refund_proof_path &&
            Storage::disk('public')->exists($history->payment_refund_proof_path)
        ) {
            Storage::disk('public')->delete($history->payment_refund_proof_path);
        }

        $history->delete();

        $client_msg = "Your History Errand: \"{$history_title}\" (ID: {$errand_id}) has been deleted by Administrator: \"{$admin_full_name}\" (ID: {$admin->id}).";
        $tasker_msg = "Your History Errand: \"{$history_title}\" (ID: {$errand_id}) has been deleted by Administrator: \"{$admin_full_name}\" (ID: {$admin->id}).";
        $admin_msg = "History Errand Deleted | Errand: \"{$history_title}\" (ID: {$errand_id}) | Client: \"{$client_full_name}\" (ID: {$client_id}) | Tasker: \"{$tasker_full_name}\" (ID: {$tasker_id}) | Deleted by Admin: \"{$admin_full_name}\" (ID: {$admin->id}).";

        $notif_type = 'errand';

        if ($client_id && !$client_deleted && User::where('id', $client_id)->exists()) {
            Notification::create([
                'user_id' => $client_id,
                'type' => $notif_type,
                'message' => $client_msg,
                'link' => null,
                'is_read' => false,
            ]);
        }

        if ($tasker_id && !$tasker_deleted && User::where('id', $tasker_id)->exists()) {
            Notification::create([
                'user_id' => $tasker_id,
                'type' => $notif_type,
                'message' => $tasker_msg,
                'link' => null,
                'is_read' => false,
            ]);
        }

        $otherAdmins = User::where('role', 'admin')
            ->where('id', '!=', $admin->id)
            ->get();

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

    public function showUserData(Request $request ,$id)
    {
        $user = User::where('id', $id)
            ->where('id', '!=', $request->user()->id)
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'first_name' => $user->first_name,
                'middle_name' => $user->middle_name ?? '',
                'last_name' => $user->last_name,
                'full_name' => trim(
                    $user->first_name . ' ' .
                    ($user->middle_name ? $user->middle_name . ' ' : '') .
                    $user->last_name
                ),
                'role' => $user->role,
                'formatted_role' => Str::ucfirst(strtolower($user->role)),
                'role_class' => 'role-' . strtolower($user->role),
                'contact_number' => $user->contact_number,
                'profile_picture' => asset($user->profile_picture),
                'created_at' => $user->created_at
                    ->timezone('Asia/Manila')
                    ->format('F d, Y h:i A'),
            ]
        ]);
    }

    public function storeUser(Request $request)
    {
        $validated = $request->validate(
            [
                'email' => ['required', 'email', 'max:100'],
                'first_name' => ['required', 'string', 'max:50'],
                'middle_name' => ['nullable', 'string', 'max:50'],
                'last_name' => ['required', 'string', 'max:50'],
                'role' => ['required', 'string', 'in:admin,tasker,client'],
                'password' => ['required', 'string', 'min:3', 'confirmed'],
                'contact_number' => ['required', 'string', 'max:20'],
            ],
            [
                'email.required' => 'Please enter an email address.',
                'email.email' => 'Please enter a valid email address.',

                'password.confirmed' => 'The password confirmation does not match.',
                'password.min' => 'The password must be at least :min characters.',

                'first_name.required' => 'Please enter a first name.',
                'last_name.required' => 'Please enter a last name.',
                'role.required' => 'Please select a role.',
                'role.in' => 'The selected role is invalid.',
                'contact_number.required' => 'Please enter a contact number.',
            ]
        );

        $existingUser = User::where('email', $validated['email'])->first();

        if ($existingUser) {

            if ($existingUser->hasVerifiedEmail()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This email is already registered.'
                ], 409);
            }

            $existingUser->update([
                'first_name' => $validated['first_name'],
                'middle_name' => $validated['middle_name'] ?? null,
                'last_name' => $validated['last_name'],
                'role' => strtolower($validated['role']),
                'password' => Hash::make($validated['password']),
                'contact_number' => $validated['contact_number'],
            ]);

            event(new Registered($existingUser));

            $user = $existingUser;

        } else {

            $user = User::create([
                'email' => $validated['email'],
                'first_name' => $validated['first_name'],
                'middle_name' => $validated['middle_name'] ?? null,
                'last_name' => $validated['last_name'],
                'role' => strtolower($validated['role']),
                'password' => Hash::make($validated['password']),
                'contact_number' => $validated['contact_number'],
            ]);

            event(new Registered($user));
        }

        $admin = $request->user();

        $admin_full_name = trim("{$admin->first_name} " . ($admin->middle_name ? "{$admin->middle_name} " : "") . "{$admin->last_name}");
        $new_user_full_name = trim("{$user->first_name} " . ($user->middle_name ? "{$user->middle_name} " : "") . "{$user->last_name}");
        $formatted_role = ucfirst($user->role);

        $admin_msg = "User Added | New User: \"{$new_user_full_name}\" (ID: {$user->id}, Role: {$formatted_role}) | Added by Admin: \"{$admin_full_name}\" (ID: {$admin->id}).";

        $notif_type = "system";
        $admin_link = route('admin.view-user', ['id' => $user->id]) . '?from=notifications';

        $otherAdmins = User::where('role', 'admin')
            ->where('id', '!=', $admin->id)
            ->get();

        foreach ($otherAdmins as $adm) {
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
            'message' => 'User added successfully. A verification email has been sent to the user.',
            'user' => $user
        ], $existingUser ? 200 : 201);
    }
}