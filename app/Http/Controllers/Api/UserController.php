<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\UserTransaction;
use App\Models\Cart;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Get user dashboard data
     */
    public function dashboard(Request $request)
    {
        try {
            $products = Product::query();

            // Filter by category
            if ($request->has('category') && $request->category != '') {
                $products->where('category', $request->category);
            }

            // Filter by price range
            if ($request->has('price_range') && $request->price_range != '') {
                $range = explode('-', $request->price_range);
                if (count($range) === 2) {
                    $products->whereBetween('selling_price', [intval($range[0]), intval($range[1])]);
                }
            }

            $products = $products->paginate($request->get('per_page', 12));
            $categories = Product::distinct()->pluck('category');
            $cartCount = Cart::where('user_id', auth()->id())->sum('quantity');

            return response()->json([
                'status' => 'success',
                'data' => [
                    'products' => $products,
                    'categories' => $categories,
                    'cart_count' => $cartCount
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve dashboard data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user orders/transactions
     */
    public function orders(Request $request)
    {
        try {
            $transactions = UserTransaction::with(['items.product'])
                ->where('user_id', auth()->id())
                ->orderBy('created_at', 'desc')
                ->get();

            $paymentMethods = [
                'bank_transfer' => 'Bank Transfer',
                'qris' => 'QRIS',
                'credit_card' => 'Credit Card',
                'gopay' => 'GoPay',
                'shopeepay' => 'ShopeePay',
            ];

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
                'message' => 'Failed to retrieve orders',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user profile
     */
    public function profile(Request $request)
    {
        try {
            $user = $request->user()->load(['transactions']);

            return response()->json([
                'status' => 'success',
                'data' => $user
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}