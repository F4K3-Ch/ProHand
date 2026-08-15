<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Errand;
use App\Models\ErrandAttachment;

class ClientController extends Controller
{
    public function activeMetrics(Request $request)
    {
        $clientId = $request->user()->id;

        $metrics = [
            'pending' => Errand::where('client_id', $clientId)
                ->where('status', 'pending')
                ->count(),

            'pending_payment' => Errand::where('client_id', $clientId)
                ->where('status', 'pending_payment')
                ->count(),

            'in_process' => Errand::where('client_id', $clientId)
                ->where('status', 'in_process')
                ->count(),

            'pending_completion' => Errand::where('client_id', $clientId)
                ->where('status', 'pending_completion')
                ->count(),

            'pending_failure' => Errand::where('client_id', $clientId)
                ->where('status', 'pending_failure')
                ->count(),

            'dispute' => Errand::where('client_id', $clientId)
                ->where('status', 'dispute')
                ->count(),
        ];

        $metrics['total'] = array_sum($metrics);

        return response()->json($metrics);
    }

    public function activeErrands(Request $request)
    {
        $query = Errand::where('client_id', $request->user()->id);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                ->orwhere('title', 'like', "%{$search}%")
                ->orWhere('category', 'like', "%{$search}%")
                ->orWhere('status', 'like', "%{$search}%");
            });
        }

        return response()->json(
            $query->latest('created_at')
                ->paginate($request->input('per_page', 10))
        );
    }

    public function createErrand(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'category' => 'required|in:delivery,moving,cleaning,repair,grocery,laundry,project,other',
            'description' => 'required|string',
            'fee' => 'required|numeric|min:0',
            'deadline' => 'required|date',
            'location' => 'required|string|max:200',
            'contact' => 'required|string',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,gif,docx|max:10240',
        ]);

        try {
            DB::beginTransaction();

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
                $filePath = $file->store('uploads/attachments', 'public');

                ErrandAttachment::create([
                    'errand_id' => $errand->id,
                    'file_path' => $filePath,
                    'original_name' => $file->getClientOriginalName(),
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Errand created successfully!',
                'errand' => $errand->load('attachment')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Failed to create errand. Please try again.'
            ], 500);
        }
    }
}