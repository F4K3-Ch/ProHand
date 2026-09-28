<?php

namespace App\Http\Controllers;

use App\Models\History;
use App\Models\User;
use App\Models\Notification;
use App\Models\Errand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    public function data(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'first_name' => $user->first_name,
                'middle_name' => $user->middle_name ?? '',
                'last_name' => $user->last_name,
                'full_name' => trim(
                    $user->first_name . ' ' .
                    ($user->middle_name ? $user->middle_name . ' ' : '') .
                    $user->last_name
                ),
                'role' => $user->role,
                'formatted_role' => Str::ucfirst(strtolower($user->role)),
                'role_class' => 'role-' . strtolower($user->role),
                'contact_number' => $user->contact_number,
                'profile_picture' => asset($user->profile_picture),
                'created_at' => $user->created_at
                    ->timezone('Asia/Manila')
                    ->format('F d, Y h:i A'),
            ]
        ]);
    }

    public function metrics(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'admin') {
            return response()->json([
                'success' => true,
                'role' => $user->role,
                'metrics' => [],
            ]);
        }

        if ($user->role === 'client') {
            $clientId = $user->id;

            $metrics = Errand::where('client_id', $clientId)
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

            $pending = $metrics['pending'] ?? 0;
            $accepted = $metrics['accepted'] ?? 0;
            $inProgress = $metrics['in_progress'] ?? 0;
            $awaitingConfirmation = $metrics['awaiting_confirmation'] ?? 0;
            $dispute = $metrics['dispute'] ?? 0;

            $totalActiveErrands = $metrics->sum();

            $historyMetrics = History::where('client_id', $clientId)
                ->where('client_deleted', false)
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

            $completed = $historyMetrics['completed'] ?? 0;
            $failed = $historyMetrics['failed'] ?? 0;
            $totalHistoryErrands = $historyMetrics->sum();

            return response()->json([
                'success' => true,
                'role' => $user->role,
                'metrics' => [
                    'pending' => $pending,
                    'accepted' => $accepted,
                    'in_progress' => $inProgress,
                    'awaiting_confirmation' => $awaitingConfirmation,
                    'dispute' => $dispute,
                    'total_active' => $totalActiveErrands,
                    'total_history' => $totalHistoryErrands,
                    'completed' => $completed,
                    'failed' => $failed,
                ],
            ]);
        }

        if ($user->role === 'tasker') {
            $taskerId = $user->id;

            $metrics = Errand::where('tasker_id', $taskerId)
                ->whereIn('status', [
                    'accepted',
                    'in_progress',
                    'awaiting_confirmation',
                    'dispute'
                ])
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

            $accepted = $metrics['accepted'] ?? 0;
            $inProgress = $metrics['in_progress'] ?? 0;
            $awaitingConfirmation = $metrics['awaiting_confirmation'] ?? 0;
            $dispute = $metrics['dispute'] ?? 0;

            $totalActiveErrands = $metrics->sum();

            $historyMetrics = History::where('tasker_id', $taskerId)
                ->where('tasker_deleted', false)
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

            $completed = $historyMetrics['completed'] ?? 0;
            $failed = $historyMetrics['failed'] ?? 0;
            $totalHistoryErrands = $historyMetrics->sum();

            $incomeMetrics = History::where('tasker_id', $taskerId)
                ->where('tasker_deleted', false)
                ->where('status', 'completed')
                ->where('payment_status', 'paid')
                ->selectRaw('COALESCE(SUM(payment_amount), 0) as income')
                ->first();

            $income = $incomeMetrics->income * 0.80;

            return response()->json([
                'success' => true,
                'role' => $user->role,
                'metrics' => [
                    'accepted' => $accepted,
                    'in_progress' => $inProgress,
                    'awaiting_confirmation' => $awaitingConfirmation,
                    'dispute' => $dispute,
                    'total_active' => $totalActiveErrands,
                    'total_history' => $totalHistoryErrands,
                    'completed' => $completed,
                    'failed' => $failed,
                    'income' => '₱' . number_format($income, 2),
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid user role.'
        ], 403);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'first_name' => 'required|string|max:50',
            'middle_name' => 'nullable|string|max:50',
            'last_name' => 'required|string|max:50',
            'contact_number' => 'required|string|max:20',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'remove_picture' => 'nullable|boolean',
        ]);

        if ($request->boolean('remove_picture')) {
            if ($user->profile_picture && $user->profile_picture !== 'uploads/profile_pictures/default.jpg' && Storage::disk('public')->exists($user->profile_picture)) {
                Storage::disk('public')->delete($user->profile_picture);
            }
            $validated['profile_picture'] = 'uploads/profile_pictures/default.jpg';
        }

        if ($request->hasFile('profile_picture')) {
            if ($user->profile_picture && $user->profile_picture !== 'uploads/profile_pictures/default.jpg' && Storage::disk('public')->exists($user->profile_picture)) {
                Storage::disk('public')->delete($user->profile_picture);
            }

            $file = $request->file('profile_picture');
            $validated['profile_picture'] = $file->store('uploads/profile_pictures', 'public');
        }

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully!',
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'first_name' => $user->first_name,
                'middle_name' => $user->middle_name ?? '',
                'last_name' => $user->last_name,
                'contact_number' => $user->contact_number,
                'profile_picture' => asset($user->profile_picture),
            ]
        ]);
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:3|confirmed',
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'The provided current password is incorrect.'
            ], 422);
        }

        $user->password = Hash::make($validated['new_password']);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully!'
        ]);
    }

    public function deleteAccount(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'admin') {
            $activeErrandExists = Errand::where(function ($query) use ($user) {
                $query->where('client_id', $user->id)
                    ->orWhere('tasker_id', $user->id);
            })
            ->whereIn('status', [
                'accepted',
                'in_progress',
                'awaiting_confirmation',
                'dispute'
            ])
            ->exists();

            if ($activeErrandExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot delete your account while you have an active errand.'
                ], 422);
            }
        }

        DB::beginTransaction();

        try {
            $errands = Errand::with(['attachment', 'payment'])
                ->where(function ($query) use ($user) {
                    $query->where('client_id', $user->id)
                        ->orWhere('tasker_id', $user->id);
                })
                ->get();

            foreach ($errands as $errand) {
                if ($errand->attachment) {
                    if (
                        $errand->attachment->file_path &&
                        Storage::disk('public')->exists($errand->attachment->file_path)
                    ) {
                        Storage::disk('public')->delete(
                            $errand->attachment->file_path
                        );
                    }

                    $errand->attachment()->delete();
                }

                if ($errand->payment) {
                    if (
                        $errand->payment->proof_path &&
                        Storage::disk('public')->exists($errand->payment->proof_path)
                    ) {
                        Storage::disk('public')->delete(
                            $errand->payment->proof_path
                        );
                    }

                    $errand->payment->delete();
                }

                $errand->delete();
            }

            if (
                $user->profile_picture &&
                $user->profile_picture !== 'uploads/profile_pictures/default.jpg' &&
                Storage::disk('public')->exists($user->profile_picture)
            ) {
                Storage::disk('public')->delete($user->profile_picture);
            }

            $user_id = $user->id;

            $user_full_name = trim(
                "{$user->first_name} " .
                ($user->middle_name ? "{$user->middle_name} " : "") .
                "{$user->last_name}"
            );

            if ($user->role === 'tasker') {
                $admin_msg = "Tasker Account Deleted | Tasker: \"{$user_full_name}\" (ID: {$user_id}) deleted their own account.";

                $admins = User::where('role', 'admin')->get();

                foreach ($admins as $admin) {
                    Notification::create([
                        'user_id' => $admin->id,
                        'type' => 'system',
                        'message' => $admin_msg,
                        'link' => null,
                        'is_read' => false,
                    ]);
                }
            }

            if ($user->role === 'admin') {
                $admin_msg = "Admin Account Deleted | Admin: \"{$user_full_name}\" (ID: {$user_id}) deleted their own account.";

                $otherAdmins = User::where('role', 'admin')
                    ->where('id', '!=', $user_id)
                    ->get();

                foreach ($otherAdmins as $admin) {
                    Notification::create([
                        'user_id' => $admin->id,
                        'type' => 'system',
                        'message' => $admin_msg,
                        'link' => null,
                        'is_read' => false,
                    ]);
                }
            }

            Auth::logout();
            $user->delete();

            DB::commit();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return response()->json([
                'success' => true,
                'message' => 'Account deleted successfully.'
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Unable to delete your account. Please try again.'
            ], 500);
        }
    }
}