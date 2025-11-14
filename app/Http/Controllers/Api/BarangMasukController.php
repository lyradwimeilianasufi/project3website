<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BarangMasuk;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BarangMasukController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $barangMasuk = BarangMasuk::with(['product', 'supplier'])->get();

            return response()->json([
                'status' => 'success',
                'data' => $barangMasuk
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
                'product_id' => 'required|exists:products,id',
                'supplier_id' => 'required|exists:suppliers,id',
                'quantity' => 'required|integer|min:1',
                'purchase_price' => 'required|numeric|min:0',
                'date' => 'required|date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $product = Product::findOrFail($request->product_id);

            $barangMasuk = BarangMasuk::create([
                'product_id' => $request->product_id,
                'supplier_id' => $request->supplier_id,
                'quantity' => $request->quantity,
                'purchase_price' => $request->purchase_price,
                'date' => $request->date,
            ]);

            // Update product stock
            $product->stock += $request->quantity;
            $product->purchase_price = $request->purchase_price;
            $product->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Barang masuk berhasil ditambahkan',
                'data' => $barangMasuk->load(['product', 'supplier'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create data',
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
            $barangMasuk = BarangMasuk::with(['product', 'supplier'])->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $barangMasuk
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
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $barangMasuk = BarangMasuk::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'product_id' => 'sometimes|exists:products,id',
                'supplier_id' => 'sometimes|exists:suppliers,id',
                'quantity' => 'sometimes|integer|min:1',
                'purchase_price' => 'sometimes|numeric|min:0',
                'date' => 'sometimes|date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Handle stock adjustment if quantity changes
            if ($request->has('quantity') && $request->quantity != $barangMasuk->quantity) {
                $product = Product::findOrFail($barangMasuk->product_id);
                $stockDifference = $request->quantity - $barangMasuk->quantity;
                $product->stock += $stockDifference;
                $product->save();
            }

            $barangMasuk->update($validator->validated());

            return response()->json([
                'status' => 'success',
                'message' => 'Barang masuk berhasil diupdate',
                'data' => $barangMasuk->load(['product', 'supplier'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $barangMasuk = BarangMasuk::findOrFail($id);
            
            // Restore product stock
            $product = Product::findOrFail($barangMasuk->product_id);
            $product->stock -= $barangMasuk->quantity;
            $product->save();

            $barangMasuk->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Barang masuk berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}