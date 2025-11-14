<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BarangKeluar;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BarangKeluarController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $barangKeluar = BarangKeluar::with(['product', 'customer'])->get();

            return response()->json([
                'status' => 'success',
                'data' => $barangKeluar
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'customer_type' => 'required|string|in:member,non_member',
                'transaction_id' => 'required|string',
                'date' => 'required|date',
                'products' => 'required|array',
                'products.*.product_id' => 'required|exists:products,id',
                'products.*.quantity' => 'required|integer|min:1',
                'products.*.price' => 'required|numeric|min:0',
                'customer_id' => 'required_if:customer_type,member|exists:users,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $customer = null;
            if ($request->customer_type == 'member') {
                $customer = User::find($request->customer_id);
                if (!$customer) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Customer not found'
                    ], 404);
                }
            }

            $createdItems = [];
            foreach ($request->products as $productData) {
                $product = Product::findOrFail($productData['product_id']);

                if ($productData['quantity'] > $product->stock) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Insufficient stock for product: ' . $product->name
                    ], 400);
                }

                $totalPrice = $productData['quantity'] * $productData['price'];

                $barangKeluar = BarangKeluar::create([
                    'product_id' => $product->id,
                    'membership_type' => $request->customer_type,
                    'user_id' => $customer ? $customer->id : null,
                    'quantity' => $productData['quantity'],
                    'selling_price' => $productData['price'],
                    'total_price' => $totalPrice,
                    'transaction_id' => $request->transaction_id,
                    'date' => $request->date,
                ]);

                $product->stock -= $productData['quantity'];
                $product->save();

                $createdItems[] = $barangKeluar->load('product');
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Transaction completed successfully',
                'data' => $createdItems
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Transaction failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $barangKeluar = BarangKeluar::with(['product', 'customer'])->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $barangKeluar
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Get by transaction ID
     */
    public function getByTransaction($transactionId)
    {
        try {
            $barangKeluar = BarangKeluar::with(['product', 'customer'])
                ->where('transaction_id', $transactionId)
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $barangKeluar
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}