<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:100', 'unique:users,email'],
            'first_name' => ['required', 'string', 'max:50'],
            'middle_name' => ['nullable', 'string', 'max:50'],
            'last_name' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:3', 'confirmed'],
            'contact_number' => ['required', 'string', 'max:20'],
        ]);

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

        return response()->json([
            'message' => 'Registration successful.',
            'redirect' => $this->getRedirectUrl($user->role),
        ]);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid email or password.'
            ], 401);
        }

        $request->session()->regenerate();

        $user = Auth::user();

        return response()->json([
            'message' => 'Login successful.',
            'redirect' => $this->getRedirectUrl($user->role),
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user(),
        ]);
    }

    private function getRedirectUrl(string $role)
    {
        return match ($role) {
            'admin' => route('admin.dashboard'),
            'tasker' => route('tasker.dashboard'),
            'client' => route('client.dashboard'),
            default => route('login'),
        };
    }
}