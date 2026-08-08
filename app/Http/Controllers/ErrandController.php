<?php

namespace App\Http\Controllers;

use App\Models\Errand;
use App\Models\ErrandAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ErrandController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'category' => 'required|in:delivery,moving,cleaning,repair,grocery,laundry,project,others',
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