<?php

namespace App\Http\Controllers;

use App\Models\Errand;
use App\Models\ErrandAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;
use Illuminate\Support\Facades\Storage;

class ClientController extends Controller
{
    public function activeMetrics(Request $request)
    {
        $clientId = $request->user()->id;

        $metrics = Errand::where('client_id', $clientId)
            ->whereIn('status', [
                'pending',
                'pending_payment',
                'in_process',
                'pending_completion',
                'pending_failure',
                'dispute',
            ])
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $result = [
            'pending' => $metrics->get('pending', 0),
            'pending_payment' => $metrics->get('pending_payment', 0),
            'in_process' => $metrics->get('in_process', 0),
            'pending_completion' => $metrics->get('pending_completion', 0),
            'pending_failure' => $metrics->get('pending_failure', 0),
            'dispute' => $metrics->get('dispute', 0),
        ];

        $result['total'] = array_sum($result);

        return response()->json($result);
    }

    public function activeErrands(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Errand::where('client_id', $request->user()->id);

        if (!empty($validated['search'])) {
            $search = $validated['search'];

            $query->where(function ($query) use ($search) {
                $query->where('id', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%");
            });
        }

        return response()->json(
            $query->latest('created_at')
                ->paginate($validated['per_page'] ?? 10)
        );
    }

    public function createErrand(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'category' => [
                'required',
                'in:delivery,moving,cleaning,repair,grocery,laundry,project,other'
            ],
            'description' => ['required', 'string'],
            'fee' => ['required', 'numeric', 'min:0'],
            'deadline' => ['required', 'date'],
            'location' => ['required', 'string', 'max:200'],
            'contact' => ['required', 'string'],
            'attachment' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png,gif,docx',
                'max:10240'
            ],
        ]);

        try {
            $errand = DB::transaction(function () use ($request, $validated) {
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

                    $filePath = $file->store(
                        'uploads/attachments',
                        'public'
                    );

                    ErrandAttachment::create([
                        'errand_id' => $errand->id,
                        'file_path' => $filePath,
                        'original_name' => $file->getClientOriginalName(),
                    ]);
                }

                return $errand;
            });

            return response()->json([
                'message' => 'Errand created successfully!',
                'errand' => $errand->load('attachment'),
            ], 201);

        } catch (Throwable $e) {
            return response()->json([
                'error' => 'Failed to create errand. Please try again.',
            ], 500);
        }
    }

    public function showErrand(Request $request, $id)
    {
        $errand = Errand::with(['attachment', 'paymentProof'])
            ->where('id', $id)
            ->where('client_id', $request->user()->id)
            ->first();

        if (!$errand) {
            return response()->json([
                'error' => 'Errand not found.'
            ], 404);
        }

        return response()->json([
            'errand' => $errand
        ], 200);
    }

    public function updateErrand(Request $request, $id)
    {
        $errand = Errand::with('attachment')
            ->where('id', $id)
            ->where('client_id', $request->user()->id)
            ->where('status', 'pending')
            ->first();

        if (!$errand) {
            return response()->json([
                'error' => 'Errand not found or cannot be edited.'
            ], 404);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'category' => ['required', 'in:delivery,moving,cleaning,repair,grocery,laundry,project,other'],
            'description' => ['required', 'string'],
            'fee' => ['required', 'numeric', 'min:0'],
            'deadline' => ['required', 'date'],
            'location' => ['required', 'string', 'max:200'],
            'contact' => ['required', 'string'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,gif,docx', 'max:10240'],
        ]);

        try {
            $updatedErrand = DB::transaction(function () use ($request, $validated, $errand) {
                $errand->update([
                    'title' => $validated['title'],
                    'category' => $validated['category'],
                    'description' => $validated['description'],
                    'fee' => $validated['fee'],
                    'deadline' => $validated['deadline'],
                    'location' => $validated['location'],
                    'contact' => $validated['contact'],
                ]);

                if ($request->hasFile('attachment')) {
                    if ($errand->attachment) {
                        Storage::disk('public')->delete($errand->attachment->file_path);
                        $errand->attachment->delete();
                    }

                    $file = $request->file('attachment');
                    $filePath = $file->store('uploads/attachments', 'public');

                    ErrandAttachment::create([
                        'errand_id' => $errand->id,
                        'file_path' => $filePath,
                        'original_name' => $file->getClientOriginalName(),
                    ]);
                }

                return $errand->fresh('attachment');
            });

            return response()->json([
                'message' => 'Errand updated successfully!',
                'errand' => $updatedErrand,
            ], 200);

        } catch (Throwable $e) {
            return response()->json([
                'error' => 'Failed to update errand. Please try again.',
            ], 500);
        }
    }
}