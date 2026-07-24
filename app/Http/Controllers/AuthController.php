<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showRegister() 
    { 
        return view('auth.register'); 
    }

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
            'role' => 'client', 
        ]);

        Auth::login($user);
        return redirect()->route('client.dashboard');
    }

    public function showLogin() 
    { 
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }
        return view('auth.login'); 
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $userExists = User::where('email', $credentials['email'])->exists();

        if (!$userExists) {
            return back()
                ->withErrors(['email' => 'This email address is not registered in our system.'])
                ->withInput($request->only('email'));
        }

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return $this->redirectBasedOnRole(Auth::user());
        }

        return back()
            ->withErrors(['password' => 'Oops! The password you entered is incorrect.'])
            ->withInput($request->only('email')); 
    }

    private function redirectBasedOnRole($user)
    {
        return match ($user->role) {
            'admin'  => redirect()->route('admin.dashboard'),
            'tasker' => redirect()->route('tasker.dashboard'),
            'client' => redirect()->route('client.dashboard'),
        };
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('login')->withHeaders([
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}
