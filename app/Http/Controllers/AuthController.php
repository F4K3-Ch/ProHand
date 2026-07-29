<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return response()->json([
                'error' => 'No account found with this email address.'
            ], 422);
        }

        if (! Hash::check($request->password, $user->password)) {
            return response()->json([
                'error' => 'Incorrect password. Please try again.'
            ], 422);
        }

        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'role' => $user->role,
            'message' => 'Login successful'
        ]);
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:100', 'unique:users'],
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
            'token' => $token,
            'role' => $user->role,
            'message' => 'Registration successful'
        ], 201);
    }
}