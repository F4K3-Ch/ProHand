<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use App\Models\Errand;
use App\Models\ErrandAttachment;
use Illuminate\Http\Request;

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
}