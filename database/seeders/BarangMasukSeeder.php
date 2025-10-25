<?php

namespace Database\Seeders;

use App\Models\BarangMasuk;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class BarangMasukSeeder extends Seeder
{
    public function run()
    {
        $barangMasuk = [];
        $products = Product::all();
        $suppliers = Supplier::all();
        
        for ($i = 1; $i <= 100; $i++) {
            $product = $products->random();
            $quantity = rand(10, 100);
            $purchasePrice = $product->purchase_price * (1 + (rand(-10, 10) / 100)); // Variasi harga ±10%
            
            $barangMasuk[] = [
                'product_id' => $product->id,
                'supplier_id' => $suppliers->random()->id,
                'quantity' => $quantity,
                'purchase_price' => $purchasePrice,
                'date' => Carbon::now()->subDays(rand(0, 90)),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        BarangMasuk::insert($barangMasuk);
        
        $this->command->info('100 barang masuk seeded successfully!');
    }
}