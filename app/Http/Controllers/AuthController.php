<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RealRashid\SweetAlert\Facades\Alert;
// use app/Http/Controllers/AuthController.php


class AuthController extends Controller
{
    // Halaman login admin
    public function showAdminLoginForm()
    {
        return view('auth.login.admin');
    }

    // Proses login admin
    public function adminLogin(Request $request)
{
    // 1️⃣ Validasi input
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    // 2️⃣ Ambil data email dan password dari form
    $credentials = $request->only('email', 'password');

    // 3️⃣ Coba login pakai guard 'admin'
    if (Auth::guard('admin')->attempt($credentials)) {
        // Regenerasi session biar aman dari session fixation
        $request->session()->regenerate();

        Alert::toast('Selamat datang, Admin!', 'success');

        // 4️⃣ Redirect ke route dashboard admin
        return redirect()->intended('/admin/dashboard');
        // atau kalau kamu punya route name:
        // return redirect()->intended(route('admin.dashboard'));
    }

    // 5️⃣ Kalau login gagal
    Alert::toast('Email atau Password salah!', 'error');
    return back()->withInput();
}

public function showUserLoginForm()
{
    return view('auth.login.user');
}


    // Proses login user
    public function userLogin(Request $request)
    {
        // Validasi input
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Cek kredensial user
        if (Auth::attempt($request->only('email', 'password'))) {
            Alert::toast('Selamat datang, User!', 'success');
            return redirect()->intended('/dashboard');
        }

        Alert::toast('Username atau Password Salah!', 'error');
        return back();
    }

    // Logout user
    public function userLogout()
    {
        Auth::logout();
        Alert::toast('Anda telah berhasil logout.', 'success');
        return redirect('/');
    }
}