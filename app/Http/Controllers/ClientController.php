<?php

namespace App\Http\Controllers;

use App\Models\Errand;
use App\Models\ErrandAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

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
        $pendingPayment = $metrics['pending_payment'] ?? 0;
        $inProcess = $metrics['in_process'] ?? 0;
        $pendingCompletion = $metrics['pending_completion'] ?? 0;
        $pendingFailure = $metrics['pending_failure'] ?? 0;
        $dispute = $metrics['dispute'] ?? 0;

        $total = $pending
            + $pendingPayment
            + $inProcess
            + $pendingCompletion
            + $pendingFailure
            + $dispute;

        return response()->json([
            'success' => true,
            'metrics' => [
                'pending' => $pending,
                'pending_payment' => $pendingPayment,
                'in_process' => $inProcess,
                'pending_completion' => $pendingCompletion,
                'pending_failure' => $pendingFailure,
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
                'pending_payment',
                'in_process',
                'pending_completion',
                'pending_failure',
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

        $errand = Errand::with(['attachment', 'paymentProof'])
            ->where('id', $id)
            ->where('client_id', $clientId)
            ->first();

        if (!$errand) {
            return response()->json([
                'success' => false, 
                'message' => 'Errand not found.'
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
            'status_class' => 'status-field status-' . $errand->status,
            
            'created_at' => $errand->created_at?->format('M d, Y h:i A') ?? 'Not Set',
            'updated_at' => $errand->updated_at?->format('M d, Y h:i A') ?? 'Not Set',
            'accepted_at' => $errand->accepted_at?->format('M d, Y h:i A') ?? 'Not Set',
            'in_process_at' => $errand->in_process_at?->format('M d, Y h:i A') ?? 'Not Set',
            'deadline' => $errand->deadline?->format('M d, Y h:i A') ?? 'Not Set',
            'finished_at' => $finishedAt?->format('M d, Y h:i A') ?? 'Not Set',
            
            'attachment' => $errand->attachment ? [
                'name' => $errand->attachment->original_name,
                'url' => Storage::url($errand->attachment->file_path)
            ] : null,
            
            'payment_proof' => $errand->paymentProof ? [
                'name' => $errand->paymentProof->original_name,
                'url' => Storage::url($errand->paymentProof->file_path)
            ] : null,
        ];

        return response()->json([
            'success' => true, 
            'errand' => $formattedErrand
        ]);
    }
}