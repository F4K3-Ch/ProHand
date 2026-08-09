<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Errand;

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
}