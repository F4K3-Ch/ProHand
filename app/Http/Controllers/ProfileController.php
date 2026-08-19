<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
}