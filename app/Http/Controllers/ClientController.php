<?php

namespace App\Http\Controllers;

use App\Models\Errand;
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

        $errands = Errand::where('client_id', $user->id)
            ->when($search, fn($q, $s) => $q->where('title', 'like', "%{$s}%"))
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
}