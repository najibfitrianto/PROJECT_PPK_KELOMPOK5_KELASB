<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function loginForm()
    {
        return view('auth.login');
    }

    public function registerForm()
    {
        return view('auth.register');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        if ($user->account_status === 'blocked') {
            throw ValidationException::withMessages([
                'email' => ['Akun Anda telah diblokir oleh Admin. Silakan hubungi pengelola.'],
            ]);
        }

        if ($user->account_status === 'inactive') {
            throw ValidationException::withMessages([
                'email' => ['Akun Anda dalam status nonaktif.'],
            ]);
        }

        if ($user->verification_status === 'pending') {
            throw ValidationException::withMessages([
                'email' => ['Akun Anda belum diverifikasi oleh Admin. Silakan tunggu verifikasi admin.'],
            ]);
        }

        if ($user->verification_status === 'rejected') {
            throw ValidationException::withMessages([
                'email' => ['Pendaftaran akun Anda ditolak oleh Admin.'],
            ]);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'user',
            'account_status' => 'active',
            'verification_status' => 'pending',
        ]);

        return redirect()->route('login')->with('status', 'Registrasi berhasil! Akun Anda sedang menunggu verifikasi dari Admin sebelum dapat masuk.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
