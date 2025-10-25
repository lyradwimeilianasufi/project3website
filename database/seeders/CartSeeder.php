<?php

namespace Database\Seeders;

use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class CartSeeder extends Seeder
{
    public function run()
    {
        $carts = [];
        $products = Product::all();
        $customers = User::where('is_admin', false)->get();
        
        for ($i = 1; $i <= 50; $i++) {
            $product = $products->random();
            $customer = $customers->random();
            $quantity = rand(1, 5);
            $price = $product->selling_price;
            $subtotal = $quantity * $price;
            
            $carts[] = [
                'user_id' => $customer->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'price' => $price,
                'subtotal' => $subtotal,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Cart::insert($carts);
        
        $this->command->info('50 cart items seeded successfully!');
    }
}