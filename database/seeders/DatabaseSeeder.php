<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Admin;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Admin::create([
            'name' => 'lyra',
            'email' => 'lyra@gmail.com',
            'password' => Hash::make('lyra'),
        ]);
    }
}
