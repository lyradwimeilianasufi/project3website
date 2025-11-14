<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\UserTransaction;
use App\Models\TransactionItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    /**
     * Get checkout data
     */
    public function checkout()
    {
        try {
            $cart = Cart::with('product')
                ->where('user_id', auth()->id())
                ->get();

            if ($cart->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Your cart is empty'
                ], 400);
            }

            $total = $cart->sum('subtotal');
            $fee = 2000;
            $finalTotal = $total + $fee;

            return response()->json([
                'status' => 'success',
                'data' => [
                    'cart' => $cart,
                    'total' => $total,
                    'admin_fee' => $fee,
                    'final_total' => $finalTotal
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get checkout data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process payment
     */
    public function processPayment(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'payment_method' => 'required|string|in:bank_transfer,qris,credit_card,gopay,shopeepay',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $cart = Cart::with('product')
                ->where('user_id', auth()->id())
                ->get();

            if ($cart->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Your cart is empty'
                ], 400);
            }

            // Check stock for all items
            foreach ($cart as $item) {
                if ($item->quantity > $item->product->stock) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Insufficient stock for product: ' . $item->product->name
                    ], 400);
                }
            }

            $total = $cart->sum('subtotal');
            $fee = 2000;
            $finalTotal = $total + $fee;

            // Create transaction
            $transaction = UserTransaction::create([
                'user_id' => auth()->id(),
                'transaction_id' => 'ORDER-' . uniqid(),
                'total' => $finalTotal,
                'status' => 'pending',
                'payment_method' => $request->payment_method,
                'expiry_time' => now()->addMinutes(15),
            ]);

            // Create transaction items
            foreach ($cart as $item) {
                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'subtotal' => $item->subtotal,
                ]);
            }

            // Clear cart
            Cart::where('user_id', auth()->id())->delete();

            // In a real implementation, you would integrate with Midtrans here
            // For now, we'll simulate payment success
            $paymentData = [
                'transaction_id' => $transaction->transaction_id,
                'total_amount' => $finalTotal,
                'payment_method' => $request->payment_method,
                'status' => 'pending',
                'payment_url' => 'https://example.com/payment/' . $transaction->transaction_id, // Simulated payment URL
            ];

            return response()->json([
                'status' => 'success',
                'message' => 'Payment processed successfully',
                'data' => [
                    'transaction' => $transaction->load('items.product'),
                    'payment' => $paymentData
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Payment processing failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Confirm payment (simulate payment confirmation)
     */
    public function confirmPayment(Request $request, $transactionId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|string|in:success,failed,cancelled'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $transaction = UserTransaction::where('transaction_id', $transactionId)
                ->where('user_id', auth()->id())
                ->firstOrFail();

            if ($transaction->status !== 'pending') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Transaction already processed'
                ], 400);
            }

            $transaction->status = $request->status;
            $transaction->save();

            // If payment successful, update product stock
            if ($request->status === 'success') {
                $this->updateProductStock($transaction);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Payment confirmed',
                'data' => $transaction->load('items.product')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to confirm payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment status
     */
    public function getPaymentStatus($transactionId)
    {
        try {
            $transaction = UserTransaction::with('items.product')
                ->where('transaction_id', $transactionId)
                ->where('user_id', auth()->id())
                ->firstOrFail();

            return response()->json([
                'status' => 'success',
                'data' => $transaction
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get payment status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function updateProductStock($transaction)
    {
        foreach ($transaction->items as $item) {
            $product = Product::find($item->product_id);
            if ($product) {
                $product->stock -= $item->quantity;
                $product->save();
            }
        }
    }
}