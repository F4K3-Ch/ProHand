<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Errand;

class ClientController extends Controller
{
    public function dashboardData(Request $request)
    {
        $user = $request->user();
        $search = $request->input('search');
        $perPage = $request->input('per_page', 10); 

        $query = Errand::where('client_id', $user->id);

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        }

        $errands = $query->orderBy('created_at', 'desc')->paginate($perPage);

        $statusCounts = [
            'pending'             => Errand::where('client_id', $user->id)->where('status', 'pending')->count(),
            'pending_payment'     => Errand::where('client_id', $user->id)->where('status', 'pending_payment')->count(),
            'in_process'          => Errand::where('client_id', $user->id)->where('status', 'in_process')->count(),
            'pending_completion'  => Errand::where('client_id', $user->id)->where('status', 'pending_completion')->count(),
            'pending_failure'     => Errand::where('client_id', $user->id)->where('status', 'pending_failure')->count(),
            'dispute'             => Errand::where('client_id', $user->id)->where('status', 'dispute')->count(),
        ];

        $totalErrands = Errand::where('client_id', $user->id)->count();

        $statusLabels = [
            'pending'            => 'Pending',
            'pending_payment'    => 'Pending Payment',
            'in_process'         => 'In Process',
            'pending_completion' => 'Pending Completion',
            'pending_failure'    => 'Pending Failure',
            'dispute'            => 'Dispute',
        ];

        return response()->json([
            'status_counts' => $statusCounts,
            'total_errands' => $totalErrands,
            'status_labels' => $statusLabels,
            'errands'       => $errands,
        ]);
    }
}