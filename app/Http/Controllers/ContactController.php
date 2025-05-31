<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Contact;

class ContactController extends Controller
{
    // Menampilkan form kontak
    public function index()
    {
        return view('kontak');
    }

    // Menyimpan pesan yang dikirimkan
    public function store(Request $request)
    {
        // Validasi input
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'pesan' => 'required|string',
        ]);

        // Simpan data pesan ke database
        Contact::create([
            'name' => $request->name,
            'email' => $request->email,
            'message' => $request->pesan,
        ]);

        // Redirect atau memberikan feedback
        return redirect()->route('kontak')->with('success', 'Pesan berhasil dikirim');
    }

    // Lihat Pesan Masuk
    public function showMessages()
    {
        $messages = Contact::all(); // Ambil semua pesan dari database
        return view('admin.messages.index', compact('messages'));
    }
}
