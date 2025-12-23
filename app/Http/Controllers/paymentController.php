<?php

namespace App\Http\Controllers;

use Midtrans\Snap;
use App\Models\Cart;
use Midtrans\Config;
use Illuminate\Http\Request;
use App\Models\TransactionItem;
use App\Models\UserTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use RealRashid\SweetAlert\Facades\Alert;

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
                return response()->json([
                'success' => false,
                'msg' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
                ], 500);
            }


    }
    public function updatePaymentStatus(Request $request, $transactionId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|in:success,failed,pending,cancelled,expired',
                'payment_method' => 'nullable|string',
                'payment_data' => 'nullable|array',
                'snap_token' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            // Find transaction
            $transaction = UserTransaction::where('transaction_id', $transactionId)
                ->where('user_id', $user->id)
                ->first();

            if (!$transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction not found'
                ], 404);
            }

            DB::beginTransaction();

            $oldStatus = $transaction->status;
            $newStatus = $request->input('status');

            // Update transaction status
            $transaction->status = $newStatus;
            
            if ($request->has('payment_method')) {
                $transaction->payment_method = $request->input('payment_method');
            }
            
            if ($request->has('snap_token')) {
                $transaction->snap_token = $request->input('snap_token');
            }
            
            if ($request->has('payment_data')) {
                $transaction->payment_data = json_encode($request->input('payment_data'));
            }
            
            $transaction->save();

            // If payment is successful, update product stock
            if ($newStatus === 'success' && $oldStatus !== 'success') {
                $this->updateProductStock($transaction);
                
                Log::info("✅ Mobile payment success for transaction: {$transactionId}");
                
                // TODO: Send notification/email
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment status updated successfully',
                'data' => [
                    'transaction_id' => $transaction->transaction_id,
                    'old_status' => $oldStatus,
                    'new_status' => $transaction->status,
                    'total' => $transaction->total,
                    'updated_at' => $transaction->updated_at,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('💥 Error updating payment status: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update payment status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function handleMobileCallback(Request $request)
    {
        Log::info('📱 Mobile Callback Received:', $request->all());

        try {
            $validator = Validator::make($request->all(), [
                'order_id' => 'required|string',
                'transaction_status' => 'required|string',
                'payment_type' => 'nullable|string',
                'status_code' => 'nullable|string',
                'signature_key' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $orderId = $request->input('order_id');
            $transactionStatus = $request->input('transaction_status');
            $fraudStatus = $request->input('fraud_status');

            // Find transaction
            $transaction = UserTransaction::where('transaction_id', $orderId)->first();

            if (!$transaction) {
                Log::error("❌ Transaction not found: {$orderId}");
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction not found'
                ], 404);
            }

            // Update status similar to Midtrans callback
            $this->updateTransactionFromCallback($transaction, $transactionStatus, $fraudStatus, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Callback processed successfully',
                'data' => [
                    'order_id' => $orderId,
                    'status' => $transaction->status,
                    'payment_method' => $transaction->payment_method,
                    'deep_link' => "toko24app://payment/result?order_id={$orderId}&status={$transaction->status}"
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('💥 Mobile callback error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to process callback',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update transaction from callback data
     */
    private function updateTransactionFromCallback($transaction, $transactionStatus, $fraudStatus, $notification)
    {
        DB::beginTransaction();

        try {
            $oldStatus = $transaction->status;
            
            switch ($transactionStatus) {
                case 'capture':
                    if ($fraudStatus == 'challenge') {
                        $transaction->status = 'challenge';
                    } else if ($fraudStatus == 'accept') {
                        $transaction->status = 'success';
                        $this->handleSuccessfulPayment($transaction);
                    }
                    break;

                case 'settlement':
                    $transaction->status = 'success';
                    $this->handleSuccessfulPayment($transaction);
                    break;

                case 'pending':
                    $transaction->status = 'pending';
                    break;

                case 'deny':
                    $transaction->status = 'failed';
                    break;

                case 'expire':
                    $transaction->status = 'expired';
                    break;

                case 'cancel':
                    $transaction->status = 'cancelled';
                    break;

                default:
                    Log::warning("⚠️ Unknown transaction status: {$transactionStatus}");
                    break;
            }

            // Update payment details
            $transaction->payment_method = $notification['payment_type'] ?? $transaction->payment_method;
            $transaction->payment_data = json_encode($notification);
            $transaction->save();

            DB::commit();

            Log::info("📱 Mobile callback: Status changed {$oldStatus} → {$transaction->status}");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('💥 Error updating transaction from callback: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle successful payment
     */
    private function handleSuccessfulPayment($transaction)
    {
        try {
            $this->updateProductStock($transaction);
            Log::info("🎉 Payment successful for order: {$transaction->transaction_id}");
        } catch (\Exception $e) {
            Log::error('💥 Error handling successful payment: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update product stock
     */
    private function updateProductStock($transaction)
    {
        $items = $transaction->items;
        
        foreach ($items as $item) {
            $product = $item->product;
            if ($product) {
                $newStock = $product->stock - $item->quantity;
                $product->stock = max(0, $newStock);
                $product->save();
                
                Log::info("📦 Stock updated: {$product->name} -{$item->quantity} (new: {$product->stock})");
            }
        }
    }
     public function callback(Request $request)
    {
        Log::info('📥 Midtrans Callback Received:', $request->all());

        $data = $request->all();

        // Pastikan order_id ada
        if (!isset($data['order_id']) || !isset($data['transaction_status'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid callback data'
            ], 400);
        }

        $orderId = $data['order_id'];
        $status = $data['transaction_status']; // capture, settlement, pending, deny, cancel, expire
        $paymentType = $data['payment_type'] ?? null;

        DB::beginTransaction();

        try {
            $transaction = UserTransaction::where('transaction_id', $orderId)->first();

            if (!$transaction) {
                Log::error("❌ Transaction not found: {$orderId}");
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction not found'
                ], 404);
            }

            $oldStatus = $transaction->status;

            // Update status berdasarkan callback Midtrans
            switch ($status) {
                case 'capture':
                    $transaction->status = $data['fraud_status'] === 'challenge' ? 'challenge' : 'success';
                    break;

                case 'settlement':
                    $transaction->status = 'success';
                    break;

                case 'pending':
                    $transaction->status = 'pending';
                    break;

                case 'deny':
                    $transaction->status = 'failed';
                    break;

                case 'expire':
                    $transaction->status = 'expired';
                    break;

                case 'cancel':
                    $transaction->status = 'cancelled';
                    break;

                default:
                    $transaction->status = 'pending';
                    break;
            }

            $transaction->payment_method = $paymentType ?? $transaction->payment_method;
            $transaction->save();

            DB::commit();

            Log::info("✅ Transaction updated: {$orderId} ({$oldStatus} → {$transaction->status})");

            // Jika ingin frontend langsung redirect ke invoice, cukup kembalikan JSON sukses
            return response()->json([
                'success' => true,
                'order_id' => $orderId,
                'status' => $transaction->status,
                'redirect_url' => route('invoice.show', ['order_id' => $orderId])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('💥 Midtrans callback error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to process callback',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
