<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run()
    {
        $products = [];
        
        $categories = [
            'Bahan Pokok' => ['Beras', 'Gula', 'Minyak Goreng', 'Garam', 'Terigu'],
            'Minuman' => ['Air Mineral', 'Kopi', 'Teh', 'Susu', 'Jus'],
            'Snack' => ['Kerupuk', 'Biskuit', 'Coklat', 'Permen', 'Kacang'],
            'Elektronik' => ['Kipas Angin', 'Lampu', 'Charger', 'Baterai', 'Kabel'],
            'Perabotan' => ['Panci', 'Wajan', 'Piring', 'Gelas', 'Sendok'],
            'Kesehatan' => ['Sabun', 'Shampoo', 'Pasta Gigi', 'Sikat Gigi', 'Hand Sanitizer'],
            'Pakaian' => ['Kaos', 'Celana', 'Jaket', 'Topi', 'Sepatu']
        ];
        
        $brands = ['Indofood', 'Unilever', 'Wings', 'ABC', 'Sidu', 'Aqua', 'Coca-Cola', 'Nestle', 'Dancow', 'Pepsodent'];
        $units = ['pcs', 'kg', 'liter', 'pack', 'dus', 'bungkus'];
        
        $productId = 1;
        foreach ($categories as $category => $items) {
            foreach ($items as $item) {
                $purchasePrice = rand(5000, 50000);
                $sellingPrice = $purchasePrice * (1 + rand(20, 50) / 100); // Margin 20-50%
                $stock = rand(0, 200);
                
                $products[] = [
                    'name' => $item . ' ' . $brands[array_rand($brands)],
                    'category' => $category,
                    'brand' => $brands[array_rand($brands)],
                    'sku' => 'SKU-' . str_pad($productId, 6, '0', STR_PAD_LEFT),
                    'purchase_price' => $purchasePrice,
                    'selling_price' => $sellingPrice,
                    'stock' => $stock,
                    'description' => 'Deskripsi produk ' . $item . ' berkualitas tinggi.',
                    'image' => null,
                    'unit' => $units[array_rand($units)],
                    'min_stock_alert' => rand(5, 20),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                
                $productId++;
                if ($productId > 50) break 2;
            }
        }

        Product::insert($products);
        
        $this->command->info('50 products seeded successfully!');
    }
}