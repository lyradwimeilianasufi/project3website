<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class UserSeeder extends Seeder
{
    public function run()
    {
        $users = [];
        
        // Field yang wajib ada untuk semua user
        $baseFields = [
            'email_verified_at' => Carbon::now(),
            'remember_token' => Str::random(10),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // Admin user
        $users[] = array_merge([
            'full_name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'phone_number' => '081234567890',
            'street_address' => 'Jl. Admin No. 1',
            'city' => 'Jakarta',
            'province' => 'DKI Jakarta',
            'postal_code' => '10110',
            'membership_type' => 'membership',
            'registration_date' => Carbon::now(),
            'is_admin' => true,
        ], $baseFields);

        // Generate 99 customer users dengan field yang konsisten
        $cities = ['Jakarta', 'Bandung', 'Surabaya', 'Medan', 'Makassar', 'Semarang', 'Yogyakarta', 'Malang', 'Denpasar', 'Palembang'];
        $provinces = ['DKI Jakarta', 'Jawa Barat', 'Jawa Timur', 'Sumatera Utara', 'Sulawesi Selatan', 'Jawa Tengah', 'DI Yogyakarta', 'Bali', 'Sumatera Selatan'];
        $membershipTypes = ['regular', 'membership'];
        
        for ($i = 1; $i <= 99; $i++) {
            $users[] = array_merge([
                'full_name' => 'Customer ' . $i,
                'email' => 'customer' . $i . '@example.com',
                'password' => Hash::make('password'),
                'phone_number' => '08' . rand(100000000, 999999999),
                'street_address' => 'Jl. Customer No. ' . $i,
                'city' => $cities[array_rand($cities)],
                'province' => $provinces[array_rand($provinces)],
                'postal_code' => (string) rand(10000, 99999),
                'membership_type' => $membershipTypes[array_rand($membershipTypes)],
                'registration_date' => Carbon::now()->subDays(rand(1, 365)),
                'is_admin' => false,
            ], $baseFields);
        }

        // Insert satu per satu untuk menghindari error bulk insert
        foreach ($users as $user) {
            User::create($user);
        }
        
        $this->command->info('100 users seeded successfully!');
    }
}