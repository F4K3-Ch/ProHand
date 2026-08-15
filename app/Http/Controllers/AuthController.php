<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate(
            [
                'email' => ['required', 'email'],
                'password' => ['required', 'string'],
            ],
            [
                'email.required' => 'Please enter your email address.',
                'email.email' => 'Please enter a valid email address.',
                'password.required' => 'Please enter your password.',
            ]
        );

        $user = User::where('email', $credentials['email'])->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Email is not registered.'
            ], 401);
        }

        if (!Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Wrong password.'
            ], 401);
        }

        Auth::login($user);

        $request->session()->regenerate();

        $request->session()->flash('success', 'Login successful.');

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'user' => $user
        ]);
    }

    public function register(Request $request)
    {
        $validated = $request->validate(
            [
                'email' => ['required', 'email', 'max:100', 'unique:users,email'],
                'first_name' => ['required', 'string', 'max:50'],
                'middle_name' => ['nullable', 'string', 'max:50'],
                'last_name' => ['required', 'string', 'max:50'],
                'password' => ['required', 'string', 'min:3', 'confirmed'],
                'contact_number' => ['required', 'string', 'max:20'],
            ],
            [
                'email.unique' => 'This email is already registered.',
                'email.required' => 'Please enter your email address.',
                'email.email' => 'Please enter a valid email address.',

                'password.confirmed' => 'The password confirmation does not match.',
                'password.min' => 'The password must be at least :min characters.',

                'first_name.required' => 'Please enter your first name.',
                'last_name.required' => 'Please enter your last name.',
                'contact_number.required' => 'Please enter your contact number.',
            ]
        );

        $user = User::create([
            'email' => $validated['email'],
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'last_name' => $validated['last_name'],
            'password' => Hash::make($validated['password']),
            'contact_number' => $validated['contact_number'],
            'role' => 'client',
        ]);

        Auth::login($user);

        $request->session()->regenerate();

        $request->session()->flash('success', 'Registration successful.');

        return response()->json([
            'success' => true,
            'message' => 'Registration successful.',
            'user' => $user
        ], 201);
    }

    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'user' => $request->user()
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
            'message' => 'Logout successful.'
        ]);
    }
}