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
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    public function checkout()
    {
        $userId = Auth::id();

        if (!$userId) return redirect()->route('login');

        $cart = Cart::where('user_id', $userId)->get();
        $total = $cart->sum('subtotal');

        if ($cart->isEmpty()) {
            Alert::error('Keranjang Kosong', 'Silakan masukkan barang dulu.');
            return redirect()->route('cart.index');
        }

        $cart->load('product');

        return view('page.checkout.index', compact('cart', 'total'));
    }

    public function processPayment(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['success' => false, 'msg' => 'Unauthorized'], 401);
        }

        $user = Auth::user();

        try {
            // CART
            $cart = Cart::where('user_id', $user->id)->get();
            $total = $cart->sum('subtotal');
            $fee = 2000;
            $finalTotal = $total + $fee;

            if ($cart->isEmpty()) {
                return response()->json(['success' => false, 'msg' => 'Cart Kosong'], 400);
            }

            // MIDTRANS CONFIG
            Config::$serverKey = env('MIDTRANS_SERVER_KEY');
            Config::$clientKey = env('MIDTRANS_CLIENT_KEY');
            Config::$isProduction = env('MIDTRANS_IS_PRODUCTION', false);
            Config::$isSanitized = true;
            Config::$is3ds = true;
            
            // ITEM DETAIL
            $items = [];
            foreach ($cart as $item) {
                $items[] = [
                    'id' => $item->product_id,
                    'price' => (int)$item->price,
                    'quantity' => (int)$item->quantity,
                    'name' => $item->product->name,
                ];
            }

            // ADMIN FEE
            $items[] = [
                'id' => 'fee',
                'name' => 'Admin Fee',
                'price' => (int)$fee,
                'quantity' => 1,
            ];

            // ORDER
            $orderId = 'ORDER-' . uniqid();

            $params = [
                'transaction_details' => [
                    'order_id' => $orderId,
                    'gross_amount' => (int)$finalTotal
                ],
                'customer_details' => [
                    'first_name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone_number ?? '08123456789',
                ],
                'item_details' => $items
            ];

            // SIMPAN TRANSAKSI
            $transaction = UserTransaction::create([
                'user_id' => $user->id,
                'transaction_id' => $orderId,
                'total' => $finalTotal,
                'status' => 'pending',
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

            // SNAP TOKEN
            $snapToken = Snap::getSnapToken($params);

            // CLEAR CART
            Cart::where('user_id', $user->id)->delete();

            return response()->json([
                'success' => true,
                'snap_token' => $snapToken,
            ]);

        } catch (\Exception $e) {
            Log::error("Payment ERROR: " . $e->getMessage());
            return response()->json(['success' => false, 'msg' => 'Server Error'], 500);
        }
    }
}
