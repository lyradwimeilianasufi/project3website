<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\CartSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\SupplierSeeder;
use Database\Seeders\BarangMasukSeeder;
use Database\Seeders\BarangKeluarSeeder;
use Database\Seeders\TransactionItemSeeder;
use Database\Seeders\UserTransactionSeeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            UserSeeder::class,
            SupplierSeeder::class,
            ProductSeeder::class,
            BarangMasukSeeder::class,
            BarangKeluarSeeder::class,
            CartSeeder::class,
            UserTransactionSeeder::class,
            TransactionItemSeeder::class,
        ]);
    }
}