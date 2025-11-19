<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    public function index()
    {
        $products = Product::where('stock', '>', 0)
            ->orderBy('created_at', 'desc')
            ->paginate(12);
            
        return view('page.chat.chat', compact('products'));
    }
}
