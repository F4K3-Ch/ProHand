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
                    $searchDate = \Carbon\Carbon::parse($search)->format('Y-m-d');
                }
            } catch (\Exception $e) {
                // Search isn't a date, ignore it
            }

        $errands = Errand::where('client_id', $user->id)
            ->when($search, function ($query) use ($search, $searchDate) {
                $query->where(function ($q) use ($search, $searchDate) {
                    $q->where('id', $search)
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('fee', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhere('created_at', 'like', "%{$search}%")
                    ->orWhere('deadline', 'like', "%{$search}%");

                    if ($searchDate) {
                        $q->orWhereDate('created_at', $searchDate)
                        ->orWhereDate('deadline', $searchDate);
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
        $validated = $request->validate([
            'title'       => 'required|string|max:100',
            'description' => 'required|string',
            'deadline'    => 'required', 
            'contact'     => 'required|string',
            'location'    => 'required|string|max:255',
            'fee'         => 'required|numeric|min:0',
            'attachment'  => 'nullable|file|mimes:pdf,jpg,jpeg,png,gif,docx|max:10240', 
        ]);

        $deadline = Carbon::createFromFormat('F j, Y h:i A', $request->deadline);

        $errand = Errand::create([
            'client_id'   => Auth::id(),
            'title'       => $validated['title'],
            'description' => $validated['description'],
            'deadline'    => $deadline,
            'contact'     => $validated['contact'],
            'location'    => $validated['location'],
            'fee'         => $validated['fee'],
            'status'      => 'pending',
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