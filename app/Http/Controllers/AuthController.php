<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showRegister() { return view('auth.register'); }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|unique:users',
            'first_name' => 'required|max:50',
            'last_name' => 'required|max:50',
            'contact_number' => 'required|max:20',
            'password' => 'required|confirmed|min:3',
        ]);

        $user = User::create([
            'email' => $validated['email'],
            'first_name' => $validated['first_name'],
            'middle_name' => $request->middle_name,
            'last_name' => $validated['last_name'],
            'contact_number' => $validated['contact_number'],
            'password' => Hash::make($validated['password']),
            'role' => 'client', // Default role
        ]);

        Auth::login($user);
        return redirect()->route('client.dashboard');
    }

    public function showLogin() { return view('auth.login'); }

    public function login(Request $request)
    {
        // 1. Validate the basic input structure
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // 2. Check if the user exists by email
        $user = User::where('email', $credentials['email'])->first();

        if (!$user) {
            // Return back with error and keep the email in the input field
            return back()->withInput($request->only('email'))
                        ->withErrors(['email' => 'Email not found.']);
        }

        // 3. Check if the password is correct
        if (!Hash::check($credentials['password'], $user->password)) {
            // Return back with error and keep the email in the input field
            return back()->withInput($request->only('email'))
                        ->withErrors(['password' => 'Incorrect password, try again.']);
        }

        // 4. Everything is correct, log them in
        Auth::login($user);
        $request->session()->regenerate();

        // 5. Redirect based on user role
        return match ($user->role) {
            'admin' => redirect()->route('admin.dashboard'),
            'tasker' => redirect()->route('tasker.dashboard'), 
            'client' => redirect()->route('client.dashboard'),
        };
    }
}
