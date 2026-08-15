<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'error' => 'No account found with this email address.'
            ], 422);
        }

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'error' => 'Incorrect password. Please try again.'
            ], 422);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'token' => $token,
            'user' => $user,
        ], 200);
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:100', 'unique:users,email'],
            'last_name' => ['required', 'string', 'max:50'],
            'first_name' => ['required', 'string', 'max:50'],
            'middle_name' => ['nullable', 'string', 'max:50'],
            'contact_number' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:3', 'confirmed'],
        ]);

        $user = User::create([
            'email' => $validated['email'],
            'last_name' => $validated['last_name'],
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'contact_number' => $validated['contact_number'],
            'password' => Hash::make($validated['password']),
            'role' => 'client',
            'profile_picture' => 'uploads/profile_pictures/default.jpg',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful.',
            'token' => $token,
            'user' => $user,
        ], 201);
    }

    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user(),
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout successful.',
        ], 200);
    }
}