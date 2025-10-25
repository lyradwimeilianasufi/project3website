<?php

namespace Database\Seeders;

use App\Models\BarangKeluar;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class BarangKeluarSeeder extends Seeder
{
    public function run()
    {
        $barangKeluar = [];
        $products = Product::all();
        $customers = User::where('is_admin', false)->get();
        
        for ($i = 1; $i <= 100; $i++) {
            $product = $products->random();
            $customer = $customers->random();
            $quantity = rand(1, 10);
            $sellingPrice = $product->selling_price;
            $totalPrice = $quantity * $sellingPrice;
            
            $barangKeluar[] = [
                'product_id' => $product->id,
                'membership_type' => $customer->membership_type,
                'user_id' => $customer->id,
                'quantity' => $quantity,
                'selling_price' => $sellingPrice,
                'total_price' => $totalPrice,
                'date' => Carbon::now()->subDays(rand(0, 60)),
                'transaction_id' => 'TRX-' . str_pad($i, 6, '0', STR_PAD_LEFT),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        BarangKeluar::insert($barangKeluar);
        
        $this->command->info('100 barang keluar seeded successfully!');
    }
}