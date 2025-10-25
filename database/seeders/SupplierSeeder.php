<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run()
    {
        $suppliers = [];
        $companyTypes = ['PT', 'CV', 'UD', 'TOKO'];
        $products = ['Beras', 'Minyak', 'Gula', 'Terigu', 'Susu', 'Kopi', 'Teh', 'Snack', 'Sembako', 'Elektronik'];
        
        for ($i = 1; $i <= 20; $i++) {
            $suppliers[] = [
                'name' => $companyTypes[array_rand($companyTypes)] . ' ' . $products[array_rand($products)] . ' Sejahtera ' . $i,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Supplier::insert($suppliers);
        
        $this->command->info('20 suppliers seeded successfully!');
    }
}