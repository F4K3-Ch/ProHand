<?php

namespace App\Http\Controllers;

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
        $perPage = (int) $request->query('per_page', 10);

        $allowedPerPage = [5, 10, 25, 50];

        if (!in_array($perPage, $allowedPerPage)) {
            $perPage = 10;
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
        $perPage = (int) $request->query('per_page', 10);

        $allowedPerPage = [5, 10, 25, 50];

        if (!in_array($perPage, $allowedPerPage)) {
            $perPage = 10;
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
                'id' => $errand->errand_id,
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

    public function createErrand(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'category' => ['required', 'in:delivery,moving,cleaning,repair,grocery,laundry,project,other'],
            'description' => ['required', 'string'],
            'fee' => ['required', 'numeric', 'min:0'],
            'deadline' => ['required', 'date', 'after:now'],
            'location' => ['required', 'string', 'max:200'],
            'contact' => ['required', 'string'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,gif,docx', 'max:10240'],
        ]);

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

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');

            $path = $file->store('uploads/attachments', 'public');

            ErrandAttachment::create([
                'errand_id' => $errand->id,
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Errand created successfully.',
            'errand' => $errand->load('attachment'),
        ], 201);
    }

    public function getErrandDetails($id, Request $request)
    {
        $clientId = $request->user()->id;

        $errand = Errand::with(['attachment', 'tasker'])
            ->where('id', $id)
            ->where('client_id', $clientId)
            ->first();

        if (!$errand) {
            return response()->json([
                'success' => false, 
                'message' => 'Errand not found.'
            ], 404);
        }

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

            'tasker' => $errand->tasker ? [
                'id' => $errand->tasker->id,
                'name' => trim("{$errand->tasker->first_name} {$errand->tasker->middle_name} {$errand->tasker->last_name}"),
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

    public function editErrand(Request $request, $id)
    {
        $clientId = $request->user()->id;

        $errand = Errand::where('id', $id)
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
            'deadline' => ['required', 'date'],
            'location' => ['required', 'string', 'max:200'],
            'contact' => ['required', 'string'],
            'remove_current_file' => ['nullable', 'boolean'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,gif,docx', 'max:10240'],
        ]);

        $errand->update([
            'title' => $validated['title'],
            'category' => $validated['category'],
            'description' => $validated['description'],
            'fee' => $validated['fee'],
            'deadline' => $validated['deadline'],
            'location' => $validated['location'],
            'contact' => $validated['contact'],
        ]);

        // Handle attachment removal
        if ($request->filled('remove_current_file') && $errand->attachment) {
            Storage::disk('public')->delete($errand->attachment->file_path);
            $errand->attachment()->delete();
        }

        // Handle new attachment upload / replacement
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

        return response()->json([
            'success' => true,
            'message' => 'Errand updated successfully.',
            'errand' => $errand->load('attachment'),
        ]);
    }

    public function destroyErrand(Request $request, $id)
    {
        $clientId = $request->user()->id;

        $errand = Errand::with('attachment')
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

        $errand = Errand::with('attachment')
            ->where('id', $id)
            ->where('client_id', $client->id)
            ->where('status', 'awaiting_confirmation')
            ->first();

        if (!$errand) {
            return response()->json([
                'success' => false,
                'message' => 'Errand not found or is not awaiting confirmation.'
            ], 404);
        }

        $tasker = $errand->tasker_id ? User::find($errand->tasker_id) : null;

        try {
            $history = DB::transaction(function () use ($errand, $client, $tasker) {

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
                        $history->id . '.' . $extension;

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
                        $history->id . '.' . $extension;

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
                }

                $errandId = $errand->id;
                $errandTitle = $errand->title;

                $clientName = trim(
                    "{$client->first_name} " .
                    ($client->middle_name ? "{$client->middle_name} " : "") .
                    "{$client->last_name}"
                );

                $taskerId = $tasker?->id;

                $taskerName = $tasker
                    ? trim(
                        "{$tasker->first_name} " .
                        ($tasker->middle_name ? "{$tasker->middle_name} " : "") .
                        "{$tasker->last_name}"
                    )
                    : 'Tasker';

                if ($taskerId) {
                    Notification::create([
                        'user_id' => $taskerId,
                        'type' => 'errand',
                        'message' => "Your Errand: \"{$errandTitle}\" (ID: {$errandId}) has been confirmed as completed by Client: \"{$clientName}\" (ID: {$client->id}) and has been moved to history.",
                        'link' => '',
                        'is_read' => false,
                    ]);
                }

                $admins = User::where('role', 'admin')->get();

                foreach ($admins as $admin) {
                    Notification::create([
                        'user_id' => $admin->id,
                        'type' => 'errand',
                        'message' => "Errand Completed | Errand: \"{$errandTitle}\" (ID: {$errandId}) | Client: \"{$clientName}\" (ID: {$client->id}) | Tasker: \"{$taskerName}\" (ID: {$taskerId}). The client confirmed the errand as completed and it has been transferred to history.",
                        'link' => '',
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
}