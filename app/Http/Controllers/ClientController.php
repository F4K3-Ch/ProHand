<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\User;
use App\Models\Notification;
use App\Models\Errand;
use App\Models\ErrandAttachment;
use App\Models\History;
use App\Models\HistoryAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ClientController extends Controller
{
    public function activeMetrics(Request $request)
    {
        $clientId = $request->user()->id;

        $metrics = Errand::where('client_id', $clientId)
            ->selectRaw('status, COUNT(*) as total')
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

    public function activeErrands(Request $request)
    {
        $clientId = $request->user()->id;

        $search = $request->query('search');
        $perPage = (int) $request->query('per_page', 8);

        $allowedPerPage = [4, 8, 16, 32];

        if (!in_array($perPage, $allowedPerPage)) {
            $perPage = 8;
        }

        $query = Errand::where('client_id', $clientId)
            ->whereIn('status', [
                'pending',
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
                'cancel_requested_by_client' => $errand->cancel_requested_by_client,
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
        $clientId = $request->user()->id;

        $metrics = History::where('client_id', $clientId)
            ->where('client_deleted', false)
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
        $clientId = $request->user()->id;

        $search = $request->query('search');
        $perPage = (int) $request->query('per_page', 8);

        $allowedPerPage = [4, 8, 16, 32];

        if (!in_array($perPage, $allowedPerPage)) {
            $perPage = 8;
        }

        $query = History::where('client_id', $clientId)
            ->where('client_deleted', false)
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

    public function createErrand(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'category' => ['required', 'in:delivery,moving,cleaning,repair,grocery,laundry,project,other'],
            'description' => ['required', 'string'],
            'fee' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['required', 'in:gcash'],
            'deadline' => ['required', 'date', 'after:now'],
            'location' => ['required', 'string', 'max:200'],
            'contact' => ['required', 'string'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,gif,docx', 'max:10240'],
        ]);

        DB::beginTransaction();

        try {
            $errand = Errand::create([
                'client_id' => $request->user()->id,
                'title' => $validated['title'],
                'category' => $validated['category'],
                'description' => $validated['description'],
                'fee' => $validated['fee'],
                'deadline' => $validated['deadline'],
                'location' => $validated['location'],
                'contact' => $validated['contact'],
                'status' => 'pending',
            ]);

            Payment::create([
                'errand_id' => $errand->id,
                'client_id' => $request->user()->id,
                'amount' => $validated['fee'],
                'method' => $validated['payment_method'],
                'status' => 'pending',
            ]);

            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');

                $path = $file->store('uploads/attachments', 'public');

                ErrandAttachment::create([
                    'errand_id' => $errand->id,
                    'file_path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Errand created successfully.',
                'errand' => $errand->load('attachment', 'payment'),
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getErrandDetails(Request $request, $id)
    {
        $clientId = $request->user()->id;

        $errand = Errand::with(['attachment', 'tasker', 'payment'])
            ->where('id', $id)
            ->where('client_id', $clientId)
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
                'raw_fee' => $errand->fee,
                'status' => Str::title(str_replace('_', ' ', $errand->status)),
                'raw_status' => $errand->status,
                'cancel_requested_by_client' => $errand->cancel_requested_by_client,
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
                'raw_deadline' => $errand->deadline,
                'finished_at' => $errand->finished_at
                    ? Carbon::parse($errand->finished_at)->timezone('Asia/Manila')->format('M d, Y h:i A')
                    : 'Not Set',

                'attachment' => $errand->attachment ? [
                    'name' => $errand->attachment->original_name,
                    'url' => Storage::url($errand->attachment->file_path)
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
                ] : null,
            ];

            return response()->json([
                'success' => true,
                'errand' => $formattedErrand
            ]);
        }

        $history = History::with('attachment')
            ->where('errand_id', $id)
            ->where('client_id', $clientId)
            ->where('client_deleted', false)
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
                'status' => Str::title($history->payment_status),
                'raw_status' => $history->payment_status,
                'status_class' => 'status-field status-' . $history->payment_status,
                'amount' => $history->payment_amount,
                'paid_at' => $history->payment_paid_at
                    ? Carbon::parse($history->payment_paid_at)->timezone('Asia/Manila')->format('M d, Y h:i A')
                    : 'None',
                'proof' => $history->payment_proof_path ? [
                    'name' => basename($history->payment_proof_path),
                    'url' => Storage::url($history->payment_proof_path)
                ] : null,
            ] : null,
        ];

        return response()->json([
            'success' => true,
            'errand' => $formattedErrand
        ]);
    }

    public function submitPaymentProof(Request $request, $id)
    {
        $client = $request->user();

        $validated = $request->validate([
            'payment_proof' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:10240'],
        ]);

        DB::beginTransaction();

        try {
            $errand = Errand::with('payment')
                ->where('id', $id)
                ->where('client_id', $client->id)
                ->where('status', 'accepted')
                ->first();

            if (!$errand) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Errand not found.'
                ], 404);
            }

            if (!$errand->payment) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Payment record not found.'
                ], 404);
            }

            $file = $request->file('payment_proof');
            $path = $file->store('uploads/payment_proofs', 'public');

            $errand->payment->proof_path = $path;
            $errand->payment->proof_submitted_at = Carbon::now();
            $errand->payment->status = 'proof_submitted';
            $errand->payment->save();

            $errand_id = $errand->id;
            $errand_title = $errand->title;
            $errand_fee = number_format((float) $errand->payment->amount, 2);

            $client_id = $client->id;
            $client_full_name = trim(
                "{$client->first_name} " .
                ($client->middle_name ? "{$client->middle_name} " : "") .
                "{$client->last_name}"
            );

            $tasker_id = $errand->tasker_id;
            $tasker = User::find($tasker_id);

            $tasker_full_name = trim(
                "{$tasker->first_name} " .
                ($tasker->middle_name ? "{$tasker->middle_name} " : "") .
                "{$tasker->last_name}"
            );

            $tasker_msg = "Payment Proof Submitted | Your Errand: \"{$errand_title}\" (ID: {$errand_id}) has received a GCash payment proof from Client: \"{$client_full_name}\" (ID: {$client_id}) for ₱{$errand_fee}. Please wait for admin verification.";
            $admin_msg = "Payment Proof Submitted | Errand: \"{$errand_title}\" (ID: {$errand_id}) | Created by Client: \"{$client_full_name}\" (ID: {$client_id}) | Accepted by Tasker: \"{$tasker_full_name}\" (ID: {$tasker_id}) | Amount: ₱{$errand_fee}. The client's GCash payment proof is waiting for verification.";

            $notif_type = 'errand';

            $tasker_link = route('tasker.view-errand', ['id' => $errand_id]) . '?from=notifications';
            $admin_link = route('admin.view-errand', ['id' => $errand_id]) . '?from=notifications';

            Notification::create([
                'user_id' => $tasker_id,
                'type' => $notif_type,
                'message' => $tasker_msg,
                'link' => $tasker_link,
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

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment proof uploaded successfully.'
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function editErrand(Request $request, $id)
    {
        $clientId = $request->user()->id;

        $errand = Errand::with(['attachment', 'payment'])
            ->where('id', $id)
            ->where('client_id', $clientId)
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
                'message' => 'Only pending errands can be edited.'
            ], 403);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'category' => ['required', 'in:delivery,moving,cleaning,repair,grocery,laundry,project,other'],
            'description' => ['required', 'string'],
            'fee' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['required', 'in:gcash'],
            'deadline' => ['required', 'date'],
            'location' => ['required', 'string', 'max:200'],
            'contact' => ['required', 'string'],
            'remove_current_file' => ['nullable', 'boolean'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,gif,docx', 'max:10240'],
        ]);

        DB::beginTransaction();

        try {
            $errand->update([
                'title' => $validated['title'],
                'category' => $validated['category'],
                'description' => $validated['description'],
                'fee' => $validated['fee'],
                'deadline' => $validated['deadline'],
                'location' => $validated['location'],
                'contact' => $validated['contact'],
            ]);

            if ($errand->payment) {
                if ($errand->payment->status !== 'pending') {
                    throw new \Exception('Payment can no longer be changed.');
                }

                $errand->payment->update([
                    'amount' => $validated['fee'],
                    'method' => $validated['payment_method'],
                ]);
            }

            if ($request->filled('remove_current_file') && $errand->attachment) {
                Storage::disk('public')->delete($errand->attachment->file_path);
                $errand->attachment()->delete();
            }

            if ($request->hasFile('attachment')) {
                if ($errand->attachment) {
                    Storage::disk('public')->delete($errand->attachment->file_path);
                    $errand->attachment()->delete();
                }

                $file = $request->file('attachment');
                $path = $file->store('uploads/attachments', 'public');

                ErrandAttachment::create([
                    'errand_id' => $errand->id,
                    'file_path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                ]);
            }

            $errand->touch();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Errand updated successfully.',
                'errand' => $errand->load(['attachment', 'payment']),
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function destroyErrand(Request $request, $id)
    {
        $clientId = $request->user()->id;

        $errand = Errand::with(['attachment', 'payment'])
            ->where('id', $id)
            ->where('client_id', $clientId)
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

        if ($errand->attachment) {
            Storage::disk('public')->delete($errand->attachment->file_path);
            $errand->attachment()->delete();
        }

        $errand->delete();

        return response()->json([
            'success' => true,
            'message' => 'Errand deleted successfully.'
        ]);
    }

    public function markDispute(Request $request, $id)
    {
        $client = $request->user();

        $errand = Errand::where('id', $id)
            ->whereIn('status', ['awaiting_confirmation'])
            ->where('client_id', $client->id)
            ->first();

        if (!$errand) {
            return response()->json([
                'success' => false, 
                'message' => 'Errand not found.'
            ], 404);
        }

        $errand->status = 'dispute';
        $errand->save();

        $errand_id = $errand->id;
        $errand_title = $errand->title;
        
        $client_id = $client->id;
        $client_full_name = trim("{$client->first_name} " . ($client->middle_name ? "{$client->middle_name} " : "") . "{$client->last_name}");

        $tasker_id = $errand->tasker_id;
        $tasker = User::find($tasker_id);
        $tasker_full_name = $tasker ? trim("{$tasker->first_name} " . ($tasker->middle_name ? "{$tasker->middle_name} " : "") . "{$tasker->last_name}") : 'Tasker';

        $tasker_msg = "Your Errand: \"{$errand_title}\" (ID: {$errand_id}) that you marked as awaiting confirmation has been disputed by Client: \"{$client_full_name}\" (ID: {$client_id}).";
        $admin_msg = "Errand Disputed | Errand: \"{$errand_title}\" (ID: {$errand_id}) | Tasker: \"{$tasker_full_name}\" (ID: {$tasker_id}) | Disputed by Client: \"{$client_full_name}\" (ID: {$client_id}).";

        $notif_type = "errand";

        $tasker_link = route('tasker.view-errand', ['id' => $errand_id]) . '?from=notifications';
        $admin_link = route('admin.view-errand', ['id' => $errand_id]) . '?from=notifications';

        if ($tasker_id) {
            Notification::create([
                'user_id' => $tasker_id,
                'type' => $notif_type,
                'message' => $tasker_msg,
                'link' => $tasker_link,
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
            'message' => 'Errand dispute raised successfully!',
        ]);
    }

    public function confirmCompletion(Request $request, $id)
    {
        $client = $request->user();

        $errand = Errand::with(['attachment', 'payment'])
            ->where('id', $id)
            ->where('client_id', $client->id)
            ->where('status', 'awaiting_confirmation')
            ->first();

        if (!$errand) {
            return response()->json([
                'success' => false,
                'message' => 'Errand not found.'
            ], 404);
        }

        $payment = $errand->payment;

        if (!$payment || $payment->status !== 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Payment must be confirmed before completing the errand.'
            ], 422);
        }

        $tasker = $errand->tasker_id
            ? User::find($errand->tasker_id)
            : null;

        try {
            $history = DB::transaction(function () use ($errand, $client, $tasker, $payment) {

                $history = History::create([
                    'errand_id' => $errand->id,
                    'title' => $errand->title,
                    'category' => $errand->category,
                    'description' => $errand->description,
                    'fee' => $errand->fee,

                    'payment_method' => $payment->method,
                    'payment_amount' => $payment->amount,
                    'payment_status' => $payment->status,
                    'payment_reference' => $payment->reference,
                    'payment_paid_at' => $payment->paid_at,

                    'deadline' => $errand->deadline,
                    'location' => $errand->location,
                    'contact' => $errand->contact,
                    'status' => 'completed',

                    'client_id' => $client->id,
                    'client_last_name' => $client->last_name,
                    'client_first_name' => $client->first_name,
                    'client_middle_name' => $client->middle_name,
                    'client_email' => $client->email,
                    'client_contact_number' => $client->contact_number,

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
                    $client->profile_picture &&
                    $client->profile_picture !== 'uploads/profile_pictures/default.jpg' &&
                    Storage::disk('public')->exists($client->profile_picture)
                ) {
                    $extension = pathinfo($client->profile_picture, PATHINFO_EXTENSION);

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
                    $extension = pathinfo($tasker->profile_picture, PATHINFO_EXTENSION);

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

                $client_id = $client->id;

                $client_full_name = trim(
                    "{$client->first_name} " .
                    ($client->middle_name ? "{$client->middle_name} " : "") .
                    "{$client->last_name}"
                );

                $tasker_id = $errand->tasker_id;

                $tasker_full_name = $tasker
                    ? trim(
                        "{$tasker->first_name} " .
                        ($tasker->middle_name ? "{$tasker->middle_name} " : "") .
                        "{$tasker->last_name}"
                    )
                    : 'None';

                $tasker_msg = "Your Assigned Errand: \"{$errand_title}\" (ID: {$errand_id}) has been confirmed as completed by Client: \"{$client_full_name}\" (ID: {$client_id}) and has been moved to history.";
                $admin_msg = "Errand Completed | Errand: \"{$errand_title}\" (ID: {$errand_id}) | Client: \"{$client_full_name}\" (ID: {$client_id}) | Tasker: \"{$tasker_full_name}\" (ID: {$tasker_id}). The client confirmed the errand as completed and it has been transferred to history.";

                $notif_type = "errand";

                $tasker_link = route('tasker.view-errand', ['id' => $errand_id]) . '?from=notifications';
                $admin_link = route('admin.view-errand', ['id' => $errand_id]) . '?from=notifications';

                if ($tasker_id) {
                    Notification::create([
                        'user_id' => $tasker_id,
                        'type' => $notif_type,
                        'message' => $tasker_msg,
                        'link' => $tasker_link,
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

    public function cancelErrand(Request $request, $id)
    {
        $client = $request->user();

        $errand = Errand::where('id', $id)
            ->where('status', 'accepted')
            ->where('client_id', $client->id)
            ->first();

        if (!$errand) {
            return response()->json([
                'success' => false, 
                'message' => 'Errand not found or cannot be cancelled.'
            ], 404);
        }

        $tasker_id = $errand->tasker_id;
        $tasker = $tasker_id ? User::find($tasker_id) : null;

        $errand->status = 'pending';
        $errand->tasker_id = null;
        $errand->accepted_at = null;
        $errand->save();

        $errand_id = $errand->id;
        $errand_title = $errand->title;
        
        $client_id = $client->id;
        $client_full_name = trim("{$client->first_name} " . ($client->middle_name ? "{$client->middle_name} " : "") . "{$client->last_name}");

        $tasker_full_name = $tasker ? trim("{$tasker->first_name} " . ($tasker->middle_name ? "{$tasker->middle_name} " : "") . "{$tasker->last_name}") : 'Tasker';

        $tasker_msg = "The Errand: \"{$errand_title}\" (ID: {$errand_id}) you accepted earlier was Cancelled by the Client: \"{$client_full_name}\" (ID: {$client_id}). Status returned to \"Pending\".";
        $admin_msg = "Errand Cancelled | Errand: \"{$errand_title}\" (ID: {$errand_id}) | Previously Assigned to Tasker: \"{$tasker_full_name}\" (ID: {$tasker_id}) | Cancelled by Client: \"{$client_full_name}\" (ID: {$client_id}). Status returned to \"Pending\".";

        $notif_type = "errand";

        $tasker_link = route('tasker.view-errand', ['id' => $errand_id]) . '?from=notifications';
        $admin_link = route('admin.view-errand', ['id' => $errand_id]) . '?from=notifications';

        if ($tasker_id) {
            Notification::create([
                'user_id' => $tasker_id,
                'type' => $notif_type,
                'message' => $tasker_msg,
                'link' => $tasker_link,
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
            'message' => 'Errand cancelled successfully!',
        ]);
    }

    public function requestCancellation(Request $request, $id)
    {
        $client = $request->user();

        $errand = Errand::where('id', $id)
            ->where('status', 'in_progress')
            ->where('client_id', $client->id)
            ->first();

        if (!$errand) {
            return response()->json([
                'success' => false, 
                'message' => 'Errand not found or cannot be cancelled.'
            ], 404);
        }

        if ($errand->cancel_requested_by_client) {
            return response()->json([
                'success' => false, 
                'message' => 'Cancellation has already been requested for this errand.'
            ], 400);
        }

        $errand->cancel_requested_by_client = true;
        $errand->save();

        $errand_id = $errand->id;
        $errand_title = $errand->title;
        
        $client_id = $client->id;
        $user_full_name = trim("{$client->first_name} " . ($client->middle_name ? "{$client->middle_name} " : "") . "{$client->last_name}");

        $tasker_id = $errand->tasker_id;

        $notif_type = "errand";

        $tasker_msg = "Client \"{$user_full_name}\" (ID: {$client_id}) has requested a cancellation for the In-progress Errand: \"{$errand_title}\" (ID: {$errand_id}). Waiting for admin decision.";
        $admin_msg = "Cancellation Requested | Errand: \"{$errand_title}\" (ID: {$errand_id}) | Client: \"{$user_full_name}\" (ID: {$client_id}) has a cancellation request.";

        $tasker_link = route('tasker.view-errand', ['id' => $errand_id]) . '?from=notifications';
        $admin_link = route('admin.view-errand', ['id' => $errand_id]) . '?from=notifications';

        if ($tasker_id) {
            Notification::create([
                'user_id' => $tasker_id,
                'type' => $notif_type,
                'message' => $tasker_msg,
                'link' => $tasker_link,
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
        $clientId = $request->user()->id;

        $history = History::where('errand_id', $id)
            ->where('client_id', $clientId)
            ->first();

        if (!$history) {
            return response()->json([
                'success' => false, 
                'message' => 'History record not found.'
            ], 404);
        }

        $history->client_deleted = true;
        $history->save();

        return response()->json([
            'success' => true,
            'message' => 'Errand removed from history successfully.'
        ]);
    }
}