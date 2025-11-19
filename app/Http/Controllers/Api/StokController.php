<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class StokController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $products = Product::all();
            $lowStockProducts = Product::where('stock', '<=', \DB::raw('min_stock_alert'))->get();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'products' => $products,
                    'low_stock_products' => $lowStockProducts,
                    'low_stock_count' => $lowStockProducts->count()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve stock data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update product stock
     */
    public function updateStock(Request $request, $id)
    {
        try {
            $product = Product::findOrFail($id);

            $request->validate([
                'stock' => 'required|integer|min:0',
                'action' => 'sometimes|string|in:add,subtract,set'
            ]);

            $action = $request->get('action', 'set');
            $newStock = $request->stock;

            switch ($action) {
                case 'add':
                    $product->stock += $newStock;
                    break;
                case 'subtract':
                    if ($newStock > $product->stock) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Cannot subtract more than current stock'
                        ], 400);
                    }
                    $product->stock -= $newStock;
                    break;
                case 'set':
                default:
                    $product->stock = $newStock;
                    break;
            }

            $product->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Stock updated successfully',
                'data' => $product
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update stock',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get low stock alerts
     */
    public function lowStockAlerts()
    {
        try {
            $lowStockProducts = Product::where('stock', '<=', \DB::raw('min_stock_alert'))
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $lowStockProducts
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve low stock alerts',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}