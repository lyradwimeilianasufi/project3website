<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * Kolom yang boleh diisi secara mass-assignment.
     */
    protected $fillable = [
        'full_name',        // <- sesuai nama kolom di migration kamu
        'email',
        'password',
        'phone_number',
        'street_address',
        'city',
        'province',
        'postal_code',
        'membership_type',
        'registration_date',
        'is_admin',         // kolom tambahan yang kamu tambahkan
    ];

    /**
     * Kolom yang disembunyikan saat serialisasi (misal ke JSON).
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Konversi otomatis tipe data kolom tertentu.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_admin' => 'boolean',
    ];

    /**
     * Helper: Cek apakah user adalah admin.
     */
    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }
}
