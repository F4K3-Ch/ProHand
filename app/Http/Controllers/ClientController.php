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

    public function activeErrands(Request $request)
    {
        $clientId = $request->user()->id;

        $search = $request->query('search');
        $perPage = (int) $request->query('per_page', 10);

        $allowedPerPage = [5, 10, 25, 50];

        if (!in_array($perPage, $allowedPerPage)) {
            $perPage = 10;
        }

        $query = Errand::where('client_id', $clientId)
            ->whereIn('status', [
                'pending',
                'pending_payment',
                'in_process',
                'pending_completion',
                'pending_failure',
                'dispute',
            ]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%");
            });
        }

        $errands = $query
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'errands' => $errands->items(),
            'pagination' => [
                'current_page' => $errands->currentPage(),
                'last_page' => $errands->lastPage(),
                'per_page' => $errands->perPage(),
                'total' => $errands->total(),
                'from' => $errands->firstItem(),
                'to' => $errands->lastItem(),
            ],
        ]);
    }
}