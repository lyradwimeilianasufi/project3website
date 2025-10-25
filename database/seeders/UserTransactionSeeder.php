<?php

namespace Database\Seeders;

use App\Models\UserTransaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class UserTransactionSeeder extends Seeder
{
    public function run()
    {
        $transactions = [];
        $customers = User::where('is_admin', false)->get();
        $statuses = ['pending', 'success', 'failed'];
        
        for ($i = 1; $i <= 100; $i++) {
            $customer = $customers->random();
            $total = rand(10000, 500000);
            $status = $statuses[array_rand($statuses)];
            
            $transactions[] = [
                'user_id' => $customer->id,
                'transaction_id' => 'INV-' . str_pad($i, 6, '0', STR_PAD_LEFT),
                'total' => $total,
                'status' => $status,
                'payment_url' => $status == 'pending' ? 'https://payment.example.com/' . $i : null,
                'invoice_url' => 'https://invoice.example.com/' . $i,
                'expiry_time' => $status == 'pending' ? Carbon::now()->addHours(24) : null,
                'created_at' => Carbon::now()->subDays(rand(0, 30)),
                'updated_at' => now(),
            ];
        }

        UserTransaction::insert($transactions);
        
        $this->command->info('100 user transactions seeded successfully!');
    }
}