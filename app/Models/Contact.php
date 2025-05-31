<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;

    // Tentukan nama tabel (optional, karena Laravel akan menebak nama tabel dari pluralisasi nama model)
    protected $table = 'contacts';

    // Tentukan kolom yang dapat diisi (mass assignable)
    protected $fillable = [
        'name',
        'email',
        'message',
    ];

    // Tentukan kolom yang tidak dapat diisi (guarded), jika perlu
    // protected $guarded = ['id'];

    // Jika kamu ingin menambahkan atribut lain yang bisa diubah atau diambil, bisa menggunakan metode accessor dan mutator
}