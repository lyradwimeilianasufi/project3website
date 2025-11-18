<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RealRashid\SweetAlert\Facades\Alert;

class AuthController extends Controller
{
    // Halaman login universal
    public function showLoginForm()
    {
        return view('auth.login.user');
    }

    // Proses login universal
    public function login(Request $request)
    {
        // return "lmao";
        // Validasi input
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Coba login
        $credentials = $request->only('email', 'password');
        
        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            
            // Cek role user dan redirect sesuai role
            if (Auth::user()->isAdmin()) {
                Alert::toast('Selamat datang, Admin!', 'success');
                return redirect()->intended(route('admin.dashboard'));
            } else {
                Alert::toast('Selamat datang!', 'success');
                return redirect()->intended(route('user.dashboard'));
            }
        }

        // Kalau login gagal
        Alert::toast('Email atau Password salah!', 'error');
        return back()->withInput();
    }

    // Logout
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        Alert::toast('Anda telah berhasil logout.', 'success');
        return redirect('/');
    }
}