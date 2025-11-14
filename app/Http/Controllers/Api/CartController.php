<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $cart = Cart::with('product')
                ->where('user_id', Auth::id())
                ->get();

            $total = $cart->sum('subtotal');
            $cartCount = $cart->sum('quantity');

            return response()->json([
                'status' => 'success',
                'data' => [
                    'cart' => $cart,
                    'total' => $total,
                    'cart_count' => $cartCount
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve cart',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add product to cart
     */
    public function addToCart(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'product_id' => 'required|exists:products,id',
                'quantity' => 'required|integer|min:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $product = Product::findOrFail($request->product_id);

            // Check stock availability
            if ($request->quantity > $product->stock) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Insufficient stock. Available: ' . $product->stock
                ], 400);
            }

            $cart = Cart::where('user_id', Auth::id())
                ->where('product_id', $product->id)
                ->first();

            if ($cart) {
                $newQuantity = $cart->quantity + $request->quantity;
                
                // Check stock again with new quantity
                if ($newQuantity > $product->stock) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Insufficient stock. Available: ' . $product->stock . ', in cart: ' . $cart->quantity
                    ], 400);
                }

                $cart->quantity = $newQuantity;
                $cart->subtotal = $newQuantity * $cart->price;
                $cart->save();
            } else {
                $cart = Cart::create([
                    'user_id' => Auth::id(),
                    'product_id' => $product->id,
                    'quantity' => $request->quantity,
                    'price' => $product->selling_price,
                    'subtotal' => $product->selling_price * $request->quantity,
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Product added to cart',
                'data' => $cart->load('product')
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to add product to cart',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update cart item quantity
     */
    public function update(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'quantity' => 'required|integer|min:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $cart = Cart::where('user_id', Auth::id())
                ->where('id', $id)
                ->firstOrFail();

            $product = $cart->product;

            if ($request->quantity > $product->stock) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Insufficient stock. Available: ' . $product->stock
                ], 400);
            }

            $cart->quantity = $request->quantity;
            $cart->subtotal = $request->quantity * $cart->price;
            $cart->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Cart updated successfully',
                'data' => $cart->load('product')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update cart',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove item from cart
     */
    public function remove($id)
    {
        try {
            $cart = Cart::where('user_id', Auth::id())
                ->where('id', $id)
                ->firstOrFail();

            $cart->delete();

            // Get updated cart data
            $updatedCart = Cart::with('product')
                ->where('user_id', Auth::id())
                ->get();

            $total = $updatedCart->sum('subtotal');
            $cartCount = $updatedCart->sum('quantity');

            return response()->json([
                'status' => 'success',
                'message' => 'Item removed from cart',
                'data' => [
                    'cart' => $updatedCart,
                    'total' => $total,
                    'cart_count' => $cartCount
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to remove item from cart',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear entire cart
     */
    public function clear()
    {
        try {
            Cart::where('user_id', Auth::id())->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Cart cleared successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to clear cart',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get cart count
     */
    public function getCartCount()
    {
        try {
            $cartCount = Cart::where('user_id', Auth::id())->sum('quantity');

            return response()->json([
                'status' => 'success',
                'data' => [
                    'cart_count' => $cartCount
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get cart count',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}