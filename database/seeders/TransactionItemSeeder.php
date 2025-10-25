<?php

namespace Database\Seeders;

use App\Models\TransactionItem;
use App\Models\UserTransaction;
use App\Models\Product;
use Illuminate\Database\Seeder;

class TransactionItemSeeder extends Seeder
{
    public function run()
    {
        $transactionItems = [];
        $transactions = UserTransaction::all();
        $products = Product::all();
        
        $itemId = 1;
        foreach ($transactions as $transaction) {
            $numItems = rand(1, 4); // 1-4 items per transaction
            
            for ($j = 0; $j < $numItems; $j++) {
                $product = $products->random();
                $quantity = rand(1, 5);
                $price = $product->selling_price;
                $subtotal = $quantity * $price;
                
                $transactionItems[] = [
                    'transaction_id' => $transaction->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'price' => $price,
                    'subtotal' => $subtotal,
                    'created_at' => $transaction->created_at,
                    'updated_at' => now(),
                ];
                
                $itemId++;
                if ($itemId > 200) break 2;
            }
        }

        TransactionItem::insert($transactionItems);
        
        $this->command->info('200 transaction items seeded successfully!');
    }
}