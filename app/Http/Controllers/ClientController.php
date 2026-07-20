<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Errand;
use App\Models\ErrandAttachment;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = Auth::user();
        $search = $request->input('search');

        $stats = Errand::where('client_id', $user->id)
            ->selectRaw("count(*) as total")
            ->selectRaw("count(case when status = 'pending' then 1 end) as pending")
            ->selectRaw("count(case when status = 'pending_payment' then 1 end) as pending_payment")
            ->selectRaw("count(case when status = 'in_process' then 1 end) as in_process")
            ->first();

        $searchDate = null;
            try {
                if ($search) {
                    // Handles formats like "July 17, 2026" or "2026-07-17"
                    $searchDate = Carbon::parse($search)->format('Y-m-d');
                }
            } catch (\Exception $e) {
                // Search isn't a date, ignore it
            }

        $errands = Errand::where('client_id', $user->id)
            ->when($search, function ($query) use ($search, $searchDate) {
                $searchSlug = str_replace(' ', '_', strtolower($search));

                $query->where(function ($q) use ($search, $searchDate, $searchSlug) {
                    $q->where('id', $search)
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$searchSlug}%")
                    ->orWhere('deadline', 'like', "%{$search}%")
                    ->orWhere('fee', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$searchSlug}%");

                    if ($searchDate) {
                        $q->orWhereDate('deadline', $searchDate);
                    }
                });
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return view('client.client_dashboard', [
            'errands' => $errands,
            'status_counts' => [
                'pending' => $stats->pending ?? 0,
                'pending_payment' => $stats->pending_payment ?? 0,
                'in_process' => $stats->in_process ?? 0,
            ],
            'total_errands' => $stats->total ?? 0,
            'search' => $search
        ]);
    }

    public function create()
    {
        return view('client.create_errand');
    }

    public function store(Request $request)
    {
        $rates = [
            'delivery' => 50,
            'moving' => 150,
            'cleaning' => 100,
            'repair' => 200,
            'tutoring' => 250,
            'pet_care' => 80
        ];

        $validated = $request->validate([
            'title'          => 'required|string|max:150',
            'category'       => 'required|in:delivery,moving,cleaning,repair,tutoring,pet_care',
            'expected_hours' => 'required|numeric|min:0.5|max:100',
            'description'    => 'required|string',
            'deadline'       => 'required', 
            'contact'        => 'required|string',
            'location'       => 'required|string|max:200',
            'attachment'     => 'nullable|file|mimes:pdf,jpg,jpeg,png,gif,docx|max:10240', 
        ], [
            'expected_hours.max' => 'The duration is too long. Please enter a value of 100 hours or less.',
            'expected_hours.min' => 'The duration must be at least 0.5 hours.',
            'attachment.max' => 'The file is too large. Maximum size is 10MB.',
            'attachment.mimes' => 'The file type you uploaded is not allowed. Allowed types: PDF, JPG, JPEG, PNG, GIF, DOCX.',
        ]);

        $hourlyRate = $rates[$validated['category']];
        $totalFee = $hourlyRate * $validated['expected_hours'];

        $deadline = Carbon::createFromFormat('F j, Y h:i A', $validated['deadline']);

        $errand = Errand::create([
            'client_id'      => Auth::id(),
            'title'          => $validated['title'],
            'category'       => $validated['category'],
            'description'    => $validated['description'],
            'expected_hours' => $validated['expected_hours'],
            'fee'            => $totalFee, 
            'deadline'       => $deadline,
            'location'       => $validated['location'],
            'contact'        => $validated['contact'],
            'status'         => 'pending',
        ]);

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->store('uploads/attachments', 'public');

            ErrandAttachment::create([
                'errand_id'     => $errand->id,
                'file_path'     => $path,
                'original_name' => $file->getClientOriginalName(),
            ]);
        }

        return redirect()->route('client.dashboard')->with('success', 'Errand created successfully!');
    }
}