<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;
use App\Models\Cart;
use App\Models\UserTransaction;
use App\Models\TransactionItem;
use Midtrans\Config;
use Midtrans\Snap;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    // Proses Pembayaran untuk Mobile
    public function processPaymentMobile(Request $request)
    {
        try {
            // Validasi request
            $request->validate([
                'payment_method' => 'required|string',
                'shipping_address' => 'nullable|string',
            ]);

            // Ambil keranjang dan total
            $cart = Cart::where('user_id', auth()->id())->get();
            $total = $cart->sum('subtotal');
            $fee = 2000;  // Fee admin

            // Pastikan keranjang tidak kosong
            if ($cart->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your cart is empty'
                ], 400);
            }

            // Hitung total akhir dengan fee admin
            $finalTotal = $total + $fee;

            // Konfigurasi Midtrans
            Config::$serverKey = env('MIDTRANS_SERVER_KEY');
            Config::$clientKey = env('MIDTRANS_CLIENT_KEY');
            Config::$isProduction = env('MIDTRANS_IS_PRODUCTION', false);
            Config::$isSanitized = true;
            Config::$is3ds = true;

            // Generate unique order ID
            $orderId = 'ORDER-' . uniqid();

            // Membuat transaksi di database
            $transaction = UserTransaction::create([
                'user_id' => auth()->id(),
                'transaction_id' => $orderId,
                'total' => $finalTotal,
                'status' => 'pending',
                'payment_method' => $request->payment_method,
                'shipping_address' => $request->shipping_address,
                'expire_time' => now()->addMinutes(15),
            ]);

            // Pindahkan data produk dari keranjang ke transaction_items
            foreach ($cart as $item) {
                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'subtotal' => $item->subtotal,
                ]);
            }

            // Detail items untuk Midtrans
            $items = [];
            foreach ($cart as $item) {
                $items[] = [
                    'id' => $item->product_id,
                    'price' => $item->price,
                    'quantity' => $item->quantity,
                    'name' => $item->product->name,
                ];
            }

            // Tambah admin fee
            $items[] = [
                'id' => 'fee',
                'name' => 'Admin Fee',
                'price' => $fee,
                'quantity' => 1,
            ];

            // Parameter untuk Midtrans
            $params = [
                'transaction_details' => [
                    'order_id' => $orderId,
                    'gross_amount' => $finalTotal,
                ],
                'item_details' => $items,
                'customer_details' => [
                    'first_name' => auth()->user()->full_name,
                    'email' => auth()->user()->email,
                    'phone' => auth()->user()->phone_number,
                ],
                'callbacks' => [
                    'finish' => env('APP_URL') . '/api/payment/callback/finish',
                    'error' => env('APP_URL') . '/api/payment/callback/error',
                    'pending' => env('APP_URL') . '/api/payment/callback/pending',
                ]
            ];

            // Tambahkan payment method specific parameters
            if ($request->payment_method === 'gopay') {
                $params['payment_type'] = 'gopay';
            } elseif ($request->payment_method === 'bank_transfer') {
                $params['payment_type'] = 'bank_transfer';
            } else {
                $params['payment_type'] = 'credit_card';
                $params['credit_card'] = ['secure' => true];
            }

            // Generate Snap Token
            $snapToken = Snap::getSnapToken($params);

            // Hapus keranjang setelah transaksi dibuat
            Cart::where('user_id', auth()->id())->delete();

            return response()->json([
                'success' => true,
                'snap_token' => $snapToken,
                'transaction_id' => $orderId,
                'amount' => $finalTotal,
            ]);

        } catch (\Exception $e) {
            Log::error('Payment Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Payment processing failed'
            ], 500);
        }
    }

    // Get Transaction Status
    public function getTransactionStatus($transactionId)
    {
        $transaction = UserTransaction::where('transaction_id', $transactionId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        return response()->json([
            'transaction_id' => $transaction->transaction_id,
            'status' => $transaction->status,
            'total' => $transaction->total,
            'payment_method' => $transaction->payment_method,
            'created_at' => $transaction->created_at,
        ]);
    }

    // Callback handler untuk mobile
    public function mobileCallback(Request $request)
    {
        $serverKey = config('midtrans.server_key');
        $hashed = hash('sha512', 
            $request->order_id . 
            $request->status_code . 
            $request->gross_amount . 
            $serverKey
        );

        if ($hashed == $request->signature_key) {
            $transaction = UserTransaction::where('transaction_id', $request->order_id)->first();

            if ($transaction) {
                // Update payment method dan expiry time
                $transaction->payment_method = $request->payment_type;
                $transaction->expiry_time = $request->expiry_time;
                $transaction->save();

                // Update status transaksi
                if ($request->transaction_status == 'capture' || $request->transaction_status == 'settlement') {
                    $transaction->update(['status' => 'success']);
                    $this->updateProductStock($transaction);
                } elseif ($request->transaction_status == 'cancel' || $request->transaction_status == 'deny') {
                    $transaction->update(['status' => 'failed']);
                } elseif ($request->transaction_status == 'pending') {
                    $transaction->update(['status' => 'pending']);
                } elseif ($request->transaction_status == 'expire') {
                    $transaction->update(['status' => 'expired']);
                }

                return response()->json(['success' => true]);
            }
        }

        return response()->json(['success' => false], 400);
    }

    private function updateProductStock($transaction)
    {
        foreach ($transaction->items as $item) {
            $product = $item->product;
            $product->stock -= $item->quantity;
            $product->save();
        }
    }
}