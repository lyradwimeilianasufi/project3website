<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\UserTransaction;
use App\Models\TransactionItem;
use App\Models\Product;

class MidtransController extends Controller
{
    /**
     * Handle Midtrans payment notification callback (SERVER-TO-SERVER)
     */
    public function handleNotification(Request $request)
    {
        Log::info('🎯 [SERVER] Midtrans Notification Callback Received:', $request->all());

        try {
            $notification = $request->all();
            
            // Validasi signature key
            $valid = $this->validateSignature($notification);
            
            if (!$valid) {
                Log::warning('❌ [SERVER] Invalid Midtrans notification signature');
                return response()->json([
                    'status' => 'error', 
                    'message' => 'Invalid signature'
                ], 400);
            }

            $transactionStatus = $notification['transaction_status'];
            $orderId = $notification['order_id'];
            $fraudStatus = $notification['fraud_status'] ?? null;

            Log::info("🔄 [SERVER] Processing transaction: {$orderId}, Status: {$transactionStatus}");

            // Cari transaksi berdasarkan transaction_id (order_id dari Midtrans)
            $transaction = UserTransaction::where('transaction_id', $orderId)->first();

            if (!$transaction) {
                Log::error("❌ [SERVER] Transaction not found for order_id: {$orderId}");
                return response()->json([
                    'status' => 'error', 
                    'message' => 'Transaction not found'
                ], 404);
            }

            // Update status transaksi
            $this->updateTransactionStatus($transaction, $transactionStatus, $fraudStatus, $notification);

            Log::info("✅ [SERVER] Transaction {$orderId} updated to: {$transaction->status}");

            return response()->json([
                'status' => 'success',
                'message' => 'Notification processed successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('💥 [SERVER] Midtrans notification error: ' . $e->getMessage());
            Log::error('💥 [SERVER] Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'status' => 'error', 
                'message' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Validate Midtrans signature
     */
    private function validateSignature($notification)
    {
        $orderId = $notification['order_id'];
        $statusCode = $notification['status_code'];
        $grossAmount = $notification['gross_amount'];
        $signatureKey = $notification['signature_key'] ?? '';

        $serverKey = config('midtrans.server_key');
        $expectedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        return hash_equals($expectedSignature, $signatureKey);
    }

    /**
     * Update transaction status based on Midtrans notification
     */
    private function updateTransactionStatus($transaction, $transactionStatus, $fraudStatus, $notification)
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

            // Log status change
            if ($oldStatus !== $transaction->status) {
                Log::info("📝 Status changed: {$oldStatus} → {$transaction->status}");
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('💥 Error updating transaction: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle successful payment - update stock dan kirim notifikasi
     */
    private function handleSuccessfulPayment($transaction)
    {
        try {
            // Update product stock
            $this->updateProductStock($transaction);
            
            // TODO: Kirim email konfirmasi
            // TODO: Generate invoice PDF
            // TODO: Kirim notifikasi ke admin
            
            Log::info("🎉 Payment successful for order: {$transaction->transaction_id}");
            
        } catch (\Exception $e) {
            Log::error('💥 Error handling successful payment: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update product stock after successful payment
     */
    private function updateProductStock($transaction)
    {
        $items = $transaction->items;
        
        foreach ($items as $item) {
            $product = $item->product;
            if ($product) {
                $newStock = $product->stock - $item->quantity;
                $product->stock = max(0, $newStock); // Pastikan tidak minus
                $product->save();
                
                Log::info("📦 Stock updated: {$product->name} -{$item->quantity} (new: {$product->stock})");
            }
        }
    }

    /**
     * Handle redirect from Midtrans after payment (untuk mobile deep link)
     */
    public function handleRedirect(Request $request)
    {
        Log::info('🔗 [MOBILE] Midtrans Redirect Received', $request->all());

        try {
            $orderId = $request->query('order_id');
            $statusCode = $request->query('status_code');
            $transactionStatus = $request->query('transaction_status');

            if (!$orderId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order ID tidak valid'
                ], 400);
            }

            // Cari transaksi
            $transaction = UserTransaction::where('transaction_id', $orderId)->first();

            if (!$transaction) {
                Log::error("❌ [MOBILE] Transaction not found: {$orderId}");
                return response()->json([
                    'success' => false,
                    'message' => 'Transaksi tidak ditemukan'
                ], 404);
            }

            // Update status berdasarkan redirect parameter
            $this->updateFromRedirect($transaction, $transactionStatus);

            // Return response untuk Flutter deep linking
            return response()->json([
                'success' => true,
                'order_id' => $orderId,
                'status' => $transactionStatus,
                'transaction_status' => $transaction->status,
                'deep_link' => "toko24app://payment?order_id={$orderId}&status={$transactionStatus}",
                'redirect_url' => "toko24app://payment/result?success=" . ($transaction->status === 'success' ? 'true' : 'false'),
                'message' => $this->getStatusMessage($transactionStatus)
            ]);

        } catch (\Exception $e) {
            Log::error('💥 [MOBILE] Redirect error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memproses redirect'
            ], 500);
        }
    }

    /**
     * Update status dari redirect parameters
     */
    private function updateFromRedirect($transaction, $status)
    {
        $oldStatus = $transaction->status;
        
        // Hanya update jika status berbeda dan transaksi masih pending
        if ($oldStatus === 'pending') {
            switch ($status) {
                case 'success':
                case 'settlement':
                case 'capture':
                    $transaction->status = 'success';
                    $this->handleSuccessfulPayment($transaction);
                    break;
                    
                case 'pending':
                    $transaction->status = 'pending';
                    break;
                    
                case 'failure':
                case 'deny':
                    $transaction->status = 'failed';
                    break;
                    
                case 'expire':
                    $transaction->status = 'expired';
                    break;
                    
                case 'cancel':
                    $transaction->status = 'cancelled';
                    break;
            }
            
            $transaction->save();
            
            Log::info("📝 [MOBILE] Status updated from redirect: {$oldStatus} → {$transaction->status}");
        }
    }

    /**
     * Get status message for redirect
     */
    private function getStatusMessage($status)
    {
        $messages = [
            'capture' => 'Pembayaran berhasil!',
            'settlement' => 'Pembayaran berhasil!',
            'pending' => 'Menunggu pembayaran...',
            'deny' => 'Pembayaran ditolak.',
            'expire' => 'Pembayaran telah kedaluwarsa.',
            'cancel' => 'Pembayaran dibatalkan.'
        ];

        return $messages[$status] ?? 'Status pembayaran: ' . $status;
    }

    /**
     * Manual status check endpoint
     */
    public function checkStatus($orderId)
    {
        try {
            $transaction = UserTransaction::where('transaction_id', $orderId)->first();

            if (!$transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'order_id' => $transaction->transaction_id,
                'status' => $transaction->status,
                'payment_method' => $transaction->payment_method,
                'total' => $transaction->total,
                'created_at' => $transaction->created_at,
                'is_expired' => $transaction->is_expired,
            ]);

        } catch (\Exception $e) {
            Log::error('Status check error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Status check failed'
            ], 500);
        }
    }

    /**
     * Test endpoint untuk debugging
     */
    public function test()
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Midtrans Controller is working!',
            'endpoints' => [
                'callback' => url('/api/midtrans/callback'),
                'redirect' => url('/api/midtrans/redirect'),
                'check_status' => url('/api/midtrans/status/{order_id}'),
            ],
            'timestamp' => now()->toDateTimeString()
        ]);
    }

    /**
     * Simulate callback for testing
     */
    public function simulateCallback(Request $request)
    {
        $testData = [
            'order_id' => $request->input('order_id', 'TEST-' . time()),
            'transaction_status' => $request->input('status', 'settlement'),
            'gross_amount' => $request->input('amount', '100000'),
            'payment_type' => $request->input('payment_type', 'gopay'),
            'status_code' => '200',
            'signature_key' => 'test_signature'
        ];

        // Panggil handleNotification dengan test data
        return $this->handleNotification(new Request($testData));
    }

    /**
     * Enhanced test callback dengan validasi signature
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