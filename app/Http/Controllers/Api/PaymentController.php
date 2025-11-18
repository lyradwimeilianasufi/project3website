<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\UserTransaction;
use App\Models\TransactionItem;
use App\Models\Product;
use Midtrans\Config;
use Midtrans\Snap;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    /**
     * Proses Pembayaran untuk Mobile dengan Midtrans
     */
    public function processPaymentMobile(Request $request)
    {
        DB::beginTransaction();
        
        try {
            Log::info('💰 Starting payment process for user: ' . auth()->id());

            // Ambil keranjang dan total
            $cart = Cart::with('product')->where('user_id', auth()->id())->get();
            $total = $cart->sum('subtotal');
            $fee = 2000;  // Fee admin

            // Pastikan keranjang tidak kosong
            if ($cart->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Keranjang belanja kosong'
                ], 400);
            }

            // Validasi stok produk
            foreach ($cart as $item) {
                if ($item->product->stock < $item->quantity) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Stok produk ' . $item->product->name . ' tidak mencukupi'
                    ], 400);
                }
            }

            // Hitung total akhir dengan fee admin
            $finalTotal = $total + $fee;

            // Konfigurasi Midtrans
            $this->setupMidtransConfig();

            // Generate unique order ID
            $orderId = 'ORDER-' . time() . '-' . auth()->id();

            // Membuat transaksi di database
            $transaction = UserTransaction::create([
                'user_id' => auth()->id(),
                'transaction_id' => $orderId,
                'total' => $finalTotal,
                'status' => 'pending',
                'payment_method' => 'midtrans',
                'expiry_time' => now()->addMinutes(15),
            ]);

            Log::info('📦 Transaction created: ' . $transaction->id);

            // Pindahkan data produk dari keranjang ke transaction_items
            $this->createTransactionItems($transaction, $cart);

            // Siapkan parameter untuk Midtrans
            $params = $this->prepareMidtransParams($orderId, $finalTotal, $cart, $fee);

            Log::info('🎯 Midtrans params prepared', $params);

            // Generate Snap Token
            $snapToken = Snap::getSnapToken($params);
            
            Log::info('🔑 Snap token generated: ' . substr($snapToken, 0, 20) . '...');

            // Update transaction dengan snap token
            $transaction->update([
                'snap_token' => $snapToken,
                'payment_url' => 'https://app.sandbox.midtrans.com/snap/v3/redirection/' . $snapToken,
            ]);

            Log::info('💾 Snap token saved to transaction: ' . $transaction->id);

            // Hapus keranjang setelah transaksi dibuat
            Cart::where('user_id', auth()->id())->delete();
            
            Log::info('🛒 Cart cleared for user: ' . auth()->id());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment processed successfully',
                'data' => [
                    'snap_token' => $snapToken,
                    'transaction' => $this->formatTransactionResponse($transaction)
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('💥 Payment Error: ' . $e->getMessage());
            Log::error('💥 Payment Error Trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Payment processing failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Setup Midtrans configuration
     */
    private function setupMidtransConfig()
    {
        Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        Config::$clientKey = env('MIDTRANS_CLIENT_KEY');
        Config::$isProduction = env('MIDTRANS_IS_PRODUCTION', false);
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }

    /**
     * Create transaction items from cart
     */
    private function createTransactionItems($transaction, $cart)
    {
        foreach ($cart as $item) {
            TransactionItem::create([
                'transaction_id' => $transaction->id,
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'subtotal' => $item->subtotal,
            ]);
            
            Log::info('📝 Transaction item created for product: ' . $item->product_id);
        }
    }

    /**
     * Prepare Midtrans parameters
     */
    private function prepareMidtransParams($orderId, $finalTotal, $cart, $fee)
    {
        $items = [];
        
        // Add product items
        foreach ($cart as $item) {
            $items[] = [
                'id' => $item->product_id,
                'price' => (int) $item->price,
                'quantity' => (int) $item->quantity,
                'name' => $item->product->name,
            ];
        }

        // Add admin fee
        $items[] = [
            'id' => 'admin-fee',
            'name' => 'Biaya Admin',
            'price' => (int) $fee,
            'quantity' => 1,
        ];

        return [
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
                'finish' => url('/api/midtrans/redirect'),
            ],
            'expiry' => [
                'start_time' => now()->format('Y-m-d H:i:s O'),
                'duration' => 15,
                'unit' => 'minute'
            ]
        ];
    }

    /**
     * Format transaction response
     */
    private function formatTransactionResponse($transaction)
    {
        return [
            'id' => $transaction->id,
            'transaction_id' => $transaction->transaction_id,
            'total' => $transaction->total,
            'status' => $transaction->status,
            'payment_method' => $transaction->payment_method,
            'snap_token' => $transaction->snap_token,
            'expiry_time' => $transaction->expiry_time->toISOString(),
            'created_at' => $transaction->created_at->toISOString(),
            'items' => $transaction->items->map(function($item) {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'subtotal' => $item->subtotal,
                ];
            })
        ];
    }

    /**
     * Get Transaction Status dengan snap_token
     */
    public function getPaymentStatus($transactionId)
    {
        try {
            $transaction = UserTransaction::with('items.product')
                ->where('transaction_id', $transactionId)
                ->where('user_id', auth()->id())
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => [
                    'transaction' => [
                        'transaction_id' => $transaction->transaction_id,
                        'status' => $transaction->status,
                        'total' => $transaction->total,
                        'payment_method' => $transaction->payment_method,
                        'snap_token' => $transaction->snap_token,
                        'expiry_time' => $transaction->expiry_time?->toISOString(),
                        'created_at' => $transaction->created_at->toISOString(),
                        'items' => $transaction->items->map(function ($item) {
                            return [
                                'product_name' => $item->product->name,
                                'quantity' => $item->quantity,
                                'price' => $item->price,
                                'subtotal' => $item->subtotal,
                            ];
                        })
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found'
            ], 404);
        }
    }

    /**
     * Get Transaction by Snap Token
     */
    public function getTransactionBySnapToken($snapToken)
    {
        try {
            $transaction = UserTransaction::with('items.product')
                ->where('snap_token', $snapToken)
                ->where('user_id', auth()->id())
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => [
                    'transaction' => $transaction
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found'
            ], 404);
        }
    }

    /**
     * Regenerate Snap Token (jika token expired)
     */
    public function regenerateSnapToken($transactionId)
    {
        DB::beginTransaction();
        
        try {
            $transaction = UserTransaction::with('items.product')
                ->where('transaction_id', $transactionId)
                ->where('user_id', auth()->id())
                ->where('status', 'pending')
                ->firstOrFail();

            // Konfigurasi Midtrans
            $this->setupMidtransConfig();

            // Detail items untuk Midtrans
            $items = [];
            foreach ($transaction->items as $item) {
                $items[] = [
                    'id' => $item->product_id,
                    'price' => (int) $item->price,
                    'quantity' => (int) $item->quantity,
                    'name' => $item->product->name,
                ];
            }

            // Parameter untuk Midtrans
            $params = [
                'transaction_details' => [
                    'order_id' => $transaction->transaction_id,
                    'gross_amount' => (int) $transaction->total,
                ],
                'item_details' => $items,
                'customer_details' => [
                    'first_name' => auth()->user()->full_name ?? 'Customer',
                    'email' => auth()->user()->email ?? 'customer@example.com',
                ],
                'expiry' => [
                    'start_time' => now()->format('Y-m-d H:i:s O'),
                    'duration' => 15,
                    'unit' => 'minute'
                ]
            ];

            // Generate new Snap Token
            $newSnapToken = Snap::getSnapToken($params);

            // Update transaction dengan token baru dan perpanjang expiry
            $transaction->update([
                'snap_token' => $newSnapToken,
                'expiry_time' => now()->addMinutes(15),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Snap token regenerated successfully',
                'data' => [
                    'snap_token' => $newSnapToken,
                    'expiry_time' => $transaction->expiry_time->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('🔄 Regenerate Snap Token Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to regenerate snap token: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Confirm Payment (untuk simulasi/testing)
     */
    public function confirmPayment(Request $request, $transactionId)
    {
        try {
            $transaction = UserTransaction::where('transaction_id', $transactionId)
                ->where('user_id', auth()->id())
                ->firstOrFail();

            $validStatuses = ['success', 'failed', 'pending'];
            
            if (!in_array($request->status, $validStatuses)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid status'
                ], 400);
            }

            $transaction->update(['status' => $request->status]);

            // Jika status success, kurangi stok
            if ($request->status === 'success') {
                $this->updateProductStock($transaction);
            }

            return response()->json([
                'success' => true,
                'message' => 'Payment status updated',
                'data' => [
                    'transaction' => $transaction
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to confirm payment'
            ], 500);
        }
    }

    /**
     * Callback handler untuk mobile
     */
    public function mobileCallback(Request $request)
    {
        Log::info('🎯 [MOBILE] Midtrans CALLBACK RECEIVED', $request->all());
        
        try {
            $serverKey = env('MIDTRANS_SERVER_KEY');
            
            // ✅ VERIFIKASI SIGNATURE - Penting untuk security
            $signatureKey = hash('sha512', 
                $request->order_id . 
                $request->status_code . 
                $request->gross_amount . 
                $serverKey
            );

            Log::info('🔐 Signature Verification:', [
                'received' => $request->signature_key,
                'calculated' => $signatureKey
            ]);

            if ($signatureKey !== $request->signature_key) {
                Log::error('❌ INVALID SIGNATURE for order: ' . $request->order_id);
                return response()->json(['success' => false, 'message' => 'Invalid signature'], 400);
            }

            $transaction = UserTransaction::where('transaction_id', $request->order_id)->first();

            if (!$transaction) {
                Log::error('❌ TRANSACTION NOT FOUND: ' . $request->order_id);
                return response()->json(['success' => false, 'message' => 'Transaction not found'], 404);
            }

            Log::info('📦 Transaction Found:', [
                'id' => $transaction->id,
                'current_status' => $transaction->status,
                'new_status' => $request->transaction_status
            ]);

            // ✅ UPDATE PAYMENT METHOD jika ada
            if ($request->payment_type) {
                $transaction->payment_method = $request->payment_type;
            }

            // ✅ UPDATE STATUS TRANSAKSI
            $status = $request->transaction_status;
            $newStatus = $transaction->status;
            
            if ($status == 'capture') {
                if ($request->fraud_status == 'accept') {
                    $newStatus = 'success';
                    Log::info('💰 Payment CAPTURED and ACCEPTED: ' . $request->order_id);
                } else {
                    $newStatus = 'failed';
                    Log::warning('🚫 Payment CAPTURED but FRAUD: ' . $request->order_id);
                }
            } elseif ($status == 'settlement') {
                $newStatus = 'success';
                Log::info('✅ Payment SETTLED: ' . $request->order_id);
            } elseif ($status == 'pending') {
                $newStatus = 'pending';
                Log::info('⏳ Payment PENDING: ' . $request->order_id);
            } elseif ($status == 'deny' || $status == 'cancel') {
                $newStatus = 'failed';
                Log::info('❌ Payment DENIED/CANCELLED: ' . $request->order_id);
            } elseif ($status == 'expire') {
                $newStatus = 'expired';
                Log::info('⌛ Payment EXPIRED: ' . $request->order_id);
            } else {
                Log::warning('🤔 Unknown transaction status: ' . $status);
            }

            // ✅ UPDATE DATABASE JIKA STATUS BERUBAH
            if ($transaction->status !== $newStatus) {
                $transaction->status = $newStatus;
                $transaction->save();
                
                Log::info('📝 Status Updated:', [
                    'from' => $transaction->getOriginal('status'),
                    'to' => $newStatus
                ]);

                // ✅ KURANGI STOK JIKA SUCCESS
                if ($newStatus === 'success') {
                    $this->updateProductStock($transaction);
                    Log::info('📦 Stock updated for successful transaction: ' . $request->order_id);
                }
            } else {
                Log::info('ℹ️ Status unchanged: ' . $newStatus);
            }

            Log::info('🎉 Callback processed successfully for: ' . $request->order_id);
            return response()->json(['success' => true, 'message' => 'Callback processed']);

        } catch (\Exception $e) {
            Log::error('💥 CALLBACK ERROR: ' . $e->getMessage());
            Log::error('💥 Stack trace: ' . $e->getTraceAsString());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    /**
     * Manual status check dari Midtrans
     */
    public function checkTransactionStatus($transactionId)
    {
        try {
            $transaction = UserTransaction::where('transaction_id', $transactionId)
                ->where('user_id', auth()->id())
                ->firstOrFail();

            // ✅ CHECK STATUS DI MIDTRANS LANGSUNG
            $serverKey = env('MIDTRANS_SERVER_KEY');
            Config::$serverKey = $serverKey;
            Config::$isProduction = env('MIDTRANS_IS_PRODUCTION', false);

            $midtransStatus = \Midtrans\Transaction::status($transactionId);
            
            Log::info('🔍 Manual Status Check:', [
                'transaction_id' => $transactionId,
                'local_status' => $transaction->status,
                'midtrans_status' => $midtransStatus->transaction_status
            ]);

            // ✅ UPDATE STATUS JIKA BERBEDA
            $midtransStatus = $midtransStatus->transaction_status;
            if ($transaction->status !== $midtransStatus) {
                $transaction->status = $midtransStatus;
                $transaction->save();
                
                if ($midtransStatus === 'settlement') {
                    $this->updateProductStock($transaction);
                }
                
                Log::info('🔄 Status updated from Midtrans: ' . $midtransStatus);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'transaction' => $transaction->load('items.product'),
                    'midtrans_status' => $midtransStatus
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Manual status check failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to check status'
            ], 500);
        }
    }

    /**
     * Get User Transactions dengan snap_token
     */
    public function getUserTransactions()
    {
        try {
            $transactions = UserTransaction::with('items.product')
                ->where('user_id', auth()->id())
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'transactions' => $transactions->map(function($transaction) {
                        return [
                            'id' => $transaction->id,
                            'transaction_id' => $transaction->transaction_id,
                            'total' => $transaction->total,
                            'status' => $transaction->status,
                            'payment_method' => $transaction->payment_method,
                            'snap_token' => $transaction->snap_token,
                            'expiry_time' => $transaction->expiry_time?->toISOString(),
                            'created_at' => $transaction->created_at->toISOString(),
                            'items' => $transaction->items->map(function($item) {
                                return [
                                    'product_name' => $item->product->name,
                                    'quantity' => $item->quantity,
                                    'price' => $item->price,
                                    'subtotal' => $item->subtotal,
                                ];
                            })
                        ];
                    })
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch transactions'
            ], 500);
        }
    }

    /**
     * Update product stock
     */
    private function updateProductStock(UserTransaction $transaction)
    {
        foreach ($transaction->items as $item) {
            $product = Product::find($item->product_id);
            if ($product) {
                $product->stock -= $item->quantity;
                $product->save();
                Log::info('📦 Stock updated for product: ' . $product->id . ' new stock: ' . $product->stock);
            }
        }
    }

    /**
     * Checkout page
     */
    public function checkout()
    {
        try {
            $cart = Cart::with('product')->where('user_id', auth()->id())->get();
            $total = $cart->sum('subtotal');
            $fee = 2000;
            $finalTotal = $total + $fee;

            return response()->json([
                'success' => true,
                'data' => [
                    'cart' => $cart,
                    'total' => $total,
                    'fee' => $fee,
                    'final_total' => $finalTotal
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load checkout data'
            ], 500);
        }
    }

    /**
     * Test callback endpoint
     */
    public function testCallback(Request $request)
    {
        Log::info('🧪 TEST CALLBACK RECEIVED', $request->all());
        
        return response()->json([
            'success' => true,
            'message' => 'Callback URL is working!',
            'data' => $request->all(),
            'timestamp' => now()->toISOString()
        ]);
    }
}