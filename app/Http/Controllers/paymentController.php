<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;
use App\Models\Cart;
use App\Models\UserTransaction;
use App\Models\TransactionItem;
use Midtrans\Snap;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    // 🛒 Halaman checkout
    public function checkout()
    {
        $cart = Cart::where('user_id', auth()->id())->get();
        $total = $cart->sum('subtotal');

        if ($cart->isEmpty()) {
            Alert::error('Your cart is empty', 'Please add products to your cart first.');
            return redirect()->route('cart.index');
        }

        return view('page.checkout.index', compact('cart', 'total'));
    }

    // 💳 Proses pembayaran
    public function processPayment(Request $request)
    {
        $cart = Cart::where('user_id', auth()->id())->get();
        $total = $cart->sum('subtotal');
        $fee = 2000;
        $finalTotal = $total + $fee;

        // Detail transaksi
        $transaction_details = [
            'order_id' => 'ORDER-' . uniqid(),
            'gross_amount' => $finalTotal,
        ];

        // Item detail
        $items = [];
        foreach ($cart as $item) {
            $items[] = [
                'id' => $item->product_id,
                'price' => $item->price,
                'quantity' => $item->quantity,
                'name' => $item->product->name,
            ];
        }

        // Fee admin
        $items[] = [
            'id' => 'fee',
            'name' => 'Admin Fee',
            'price' => $fee,
            'quantity' => 1,
        ];

        // Customer detail
        $params = [
            'transaction_details' => $transaction_details,
            'item_details' => $items,
            'customer_details' => [
                'first_name' => auth()->user()->name,
                'email' => auth()->user()->email,
                'phone' => auth()->user()->phone_number ?? '08123456789',
            ],
        ];

        // Simpan ke database
        $transaction = UserTransaction::create([
            'user_id' => auth()->id(),
            'transaction_id' => $transaction_details['order_id'],
            'total' => $finalTotal,
            'status' => 'pending',
            'payment_method' => 'midtrans',
            'expire_time' => now()->addMinutes(15),
        ]);

        foreach ($cart as $item) {
            TransactionItem::create([
                'transaction_id' => $transaction->id,
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'subtotal' => $item->subtotal,
            ]);
        }

        Cart::where('user_id', auth()->id())->delete();

        // Dapatkan Snap Token
        $snapToken = Snap::getSnapToken($params);

        return response()->json(['snap_token' => $snapToken]);
    }

    // 🔁 Callback Midtrans
    public function callback(Request $request)
    {
        Log::info('Midtrans callback received', $request->all());

        $serverKey = config('midtrans.server_key');
        $hashed = hash('sha512', $request->order_id . $request->status_code . $request->gross_amount . $serverKey);

        if ($hashed === $request->signature_key) {
            $transaction = UserTransaction::where('transaction_id', $request->order_id)->first();

            if ($transaction) {
                $status = $request->transaction_status;

                if ($status === 'capture' || $status === 'settlement') {
                    $transaction->update(['status' => 'success']);
                    $this->updateProductStock($transaction);
                } elseif ($status === 'cancel' || $status === 'deny' || $status === 'expire') {
                    $transaction->update(['status' => 'failed']);
                } elseif ($status === 'pending') {
                    $transaction->update(['status' => 'pending']);
                }

                return response()->json(['success' => true]);
            } else {
                Log::error("Transaction not found for order_id: {$request->order_id}");
                return response()->json(['error' => 'Transaction not found'], 404);
            }
        }

        return response()->json(['error' => 'Invalid signature'], 403);
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
