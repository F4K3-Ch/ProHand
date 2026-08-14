<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Errand;
use Illuminate\Support\Facades\Auth;

class ClientController extends Controller
{
    public function activeMetrics()
    {
        $clientId = Auth::id();

        $counts = Errand::where('client_id', $clientId)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return response()->json([
            'status_counts' => [
                'pending'             => $counts->get('pending', 0),
                'pending_payment'     => $counts->get('pending_payment', 0),
                'in_process'          => $counts->get('in_process', 0),
                'pending_completion'  => $counts->get('pending_completion', 0),
                'pending_failure'     => $counts->get('pending_failure', 0),
                'dispute'             => $counts->get('dispute', 0),
            ],
            'total_errands' => $counts->sum()
        ]);
    }
}