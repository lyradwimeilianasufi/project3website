<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserTransaction;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    /**
     * Display the specified invoice.
     */
    public function show($order_id)
    {
        try {
            $transaction = UserTransaction::with(['items.product', 'customer'])
                ->where('transaction_id', $order_id)
                ->firstOrFail();

            $paymentMethods = [
                'bank_transfer' => 'Bank Transfer',
                'qris' => 'QRIS',
                'credit_card' => 'Credit Card',
                'gopay' => 'GoPay',
                'shopeepay' => 'ShopeePay',
            ];

            $paymentMethodName = $paymentMethods[$transaction->payment_method] ?? 'Unknown Payment Method';

            return response()->json([
                'status' => 'success',
                'data' => [
                    'transaction' => $transaction,
                    'payment_method_name' => $paymentMethodName
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invoice not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Get user's invoices
     */
    public function userInvoices(Request $request)
    {
        try {
            $user = $request->user();
            
            $invoices = UserTransaction::with(['items.product'])
                ->where('user_id', $user->id)
                ->latest()
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $invoices
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve invoices',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download invoice as PDF (placeholder - you can implement PDF generation later)
     */
    public function download($order_id)
    {
        try {
            $transaction = UserTransaction::with(['items.product', 'customer'])
                ->where('transaction_id', $order_id)
                ->firstOrFail();

            // For now, return the invoice data
            // You can implement PDF generation using DomPDF or other libraries later
            return response()->json([
                'status' => 'success',
                'message' => 'PDF download functionality to be implemented',
                'data' => $transaction
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate invoice PDF',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}