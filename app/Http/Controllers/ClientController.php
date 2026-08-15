<?php

namespace App\Http\Controllers;

use App\Models\Errand;
use Illuminate\Http\Request;

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
}