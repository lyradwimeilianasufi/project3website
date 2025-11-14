<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BarangKeluar;
use App\Models\UserTransaction;
use Illuminate\Http\Request;

class TransaksiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $transaksi = BarangKeluar::select('transaction_id', 'date', 'membership_type', 'user_id')
                ->with(['customer'])
                ->groupBy('transaction_id', 'date', 'membership_type', 'user_id')
                ->get();

            // Add total price for each transaction
            foreach ($transaksi as $item) {
                $totalPrice = BarangKeluar::where('transaction_id', $item->transaction_id)
                    ->sum('total_price');
                $item->total_price = $totalPrice;
            }

            return response()->json([
                'status' => 'success',
                'data' => $transaksi
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve transactions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($transactionId)
    {
        try {
            $barangKeluar = BarangKeluar::with(['product', 'customer'])
                ->where('transaction_id', $transactionId)
                ->get();

            $totalPrice = $barangKeluar->sum('total_price');

            return response()->json([
                'status' => 'success',
                'data' => [
                    'transaction_id' => $transactionId,
                    'items' => $barangKeluar,
                    'total_price' => $totalPrice
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Transaction not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Get user transactions
     */
    public function userTransactions(Request $request)
    {
        try {
            $user = $request->user();
            
            $transactions = UserTransaction::with(['items.product'])
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            $paymentMethods = [
                'bank_transfer' => 'Bank Transfer',
                'qris' => 'QRIS',
                'credit_card' => 'Credit Card',
                'gopay' => 'GoPay',
                'shopeepay' => 'ShopeePay',
            ];

            // Add payment method name to each transaction
            foreach ($transactions as $transaction) {
                $transaction->payment_method_name = $paymentMethods[$transaction->payment_method] ?? 'Unknown Payment Method';
            }

            return response()->json([
                'status' => 'success',
                'data' => $transactions
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve user transactions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get transaction statistics
     */
    public function statistics()
    {
        try {
            $totalTransactions = BarangKeluar::distinct('transaction_id')->count('transaction_id');
            $totalRevenue = BarangKeluar::sum('total_price');
            $todayTransactions = BarangKeluar::whereDate('date', today())
                ->distinct('transaction_id')
                ->count('transaction_id');
            $todayRevenue = BarangKeluar::whereDate('date', today())->sum('total_price');

            return response()->json([
                'status' => 'success',
                'data' => [
                    'total_transactions' => $totalTransactions,
                    'total_revenue' => $totalRevenue,
                    'today_transactions' => $todayTransactions,
                    'today_revenue' => $todayRevenue
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}