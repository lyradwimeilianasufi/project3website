<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Cart;
use App\Models\User;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\BarangMasuk;
use App\Models\BarangKeluar;
use Illuminate\Http\Request;
use App\Models\TransactionItem;
use App\Models\UserTransaction;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function dashboard()
    {
        // Data Statistik Utama
        $totalMembers = User::where('is_admin', false)->count();
        $totalProducts = Product::count();
        $totalSuppliers = Supplier::count();
        
        // Data Keuangan
        $totalSales = UserTransaction::where('status', 'success')->sum('total');
        $totalRevenue = BarangKeluar::sum('total_price');
        $totalPurchase = BarangMasuk::sum('purchase_price');
        
        // Data Stok
        $lowStockItems = Product::where('stock', '<=', DB::raw('min_stock_alert'))->count();
        $outOfStockItems = Product::where('stock', 0)->count();
        
        // Data Transaksi
        $totalTransactions = UserTransaction::count();
        $pendingTransactions = UserTransaction::where('status', 'pending')->count();
        $completedTransactions = UserTransaction::where('status', 'success')->count();
        
        // User Terbaru (7 hari terakhir)
        $newUsers = User::where('is_admin', false)
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->count();
        
        // Data untuk Chart Penjualan Bulanan
        $monthlySales = $this->getMonthlySales();
        
        // Data untuk Chart Produk Terlaris
        $topProducts = $this->getTopProducts();
        
        // Data untuk Chart Kategori Produk
        $productByCategory = $this->getProductsByCategory();
        
        // Data untuk Chart Membership
        $membershipDistribution = $this->getMembershipDistribution();
        
        // Aktivitas Terbaru
        $recentActivities = $this->getRecentActivities();
        $dailySales = $this->getDailySales();
    $membershipSales = $this->getMembershipSales();
    $stockAnalysis = $this->getStockAnalysis();
        // Produk Stok Rendah
        $lowStockProducts = Product::where('stock', '<=', DB::raw('min_stock_alert'))
            ->orderBy('stock', 'asc')
            ->limit(10)
            ->get();
        
        // Transaksi Terbaru
        $recentTransactions = UserTransaction::with(['items.product', 'customer'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        
        // User Terbaru
        $recentUsers = User::where('is_admin', false)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('admin.index', compact(
            'totalMembers',
            'totalProducts',
            'totalSuppliers',
            'totalSales',
            'totalRevenue',
            'totalPurchase',
            'lowStockItems',
            'outOfStockItems',
            'totalTransactions',
            'pendingTransactions',
            'completedTransactions',
            'newUsers',
            'monthlySales',
            'topProducts',
            'productByCategory',
            'membershipDistribution',
            'recentActivities',
            'lowStockProducts',
            'recentTransactions',
            'recentUsers',
            'dailySales',
        'membershipSales',
        'stockAnalysis'
        ));
    }
    // Tambahkan method ini ke AdminController Anda

private function getDailySales($days = 30)
{
    return BarangKeluar::select(
            DB::raw('DATE(date) as date'),
            DB::raw('SUM(total_price) as total')
        )
        ->where('date', '>=', Carbon::now()->subDays($days))
        ->groupBy(DB::raw('DATE(date)'))
        ->orderBy('date')
        ->get();
}

private function getMembershipSales()
{
    return BarangKeluar::join('users', 'barang_keluars.user_id', '=', 'users.id')
        ->select(
            'users.membership_type',
            DB::raw('SUM(barang_keluars.total_price) as total_sales')
        )
        ->groupBy('users.membership_type')
        ->get();
}

private function getStockAnalysis()
{
    $totalProducts = Product::count();
    $lowStock = Product::where('stock', '<=', DB::raw('min_stock_alert'))->count();
    $outOfStock = Product::where('stock', 0)->count();
    $adequateStock = $totalProducts - $lowStock;
    
    return [
        'adequate' => $adequateStock,
        'low' => $lowStock - $outOfStock,
        'out' => $outOfStock
    ];
}
    private function getMonthlySales()
    {
        $currentYear = Carbon::now()->year;
        
        $sales = BarangKeluar::select(
                DB::raw('MONTH(date) as month'),
                DB::raw('SUM(total_price) as total')
            )
            ->whereYear('date', $currentYear)
            ->groupBy(DB::raw('MONTH(date)'))
            ->orderBy(DB::raw('MONTH(date)'))
            ->get();
        
        $monthlyData = array_fill(1, 12, 0);
        
        foreach ($sales as $sale) {
            $monthlyData[$sale->month] = (float) $sale->total;
        }
        
        return array_values($monthlyData);
    }
    
    private function getTopProducts($limit = 5)
    {
        return TransactionItem::select(
                'product_id',
                DB::raw('SUM(quantity) as total_sold'),
                DB::raw('SUM(subtotal) as total_revenue')
            )
            ->with('product')
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->limit($limit)
            ->get();
    }
    
    private function getProductsByCategory()
    {
        return Product::select(
                'category',
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('category')
            ->get();
    }
    
    private function getMembershipDistribution()
    {
        return User::where('is_admin', false)
            ->select(
                'membership_type',
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('membership_type')
            ->get();
    }
    
    private function getRecentActivities()
    {
        $activities = [];
        
        // User registrations (last 24 hours)
        $newUsers = User::where('is_admin', false)
            ->where('created_at', '>=', Carbon::now()->subDay())
            ->get();
        
        foreach ($newUsers as $user) {
            $activities[] = [
                'type' => 'user_registration',
                'message' => "New member registration: {$user->full_name}",
                'time' => $user->created_at,
                'color' => 'blue'
            ];
        }
        
        // Barang Masuk (last 24 hours)
        $barangMasuk = BarangMasuk::with('product')
            ->where('created_at', '>=', Carbon::now()->subDay())
            ->get();
        
        foreach ($barangMasuk as $bm) {
            $activities[] = [
                'type' => 'stock_in',
                'message' => "Product stock updated: {$bm->product->name} (+{$bm->quantity} {$bm->product->unit})",
                'time' => $bm->created_at,
                'color' => 'green'
            ];
        }
        
        // Barang Keluar (last 24 hours)
        $barangKeluar = BarangKeluar::with('product')
            ->where('created_at', '>=', Carbon::now()->subDay())
            ->get();
        
        foreach ($barangKeluar as $bk) {
            $activities[] = [
                'type' => 'sale',
                'message' => "New sale: {$bk->product->name} ({$bk->quantity} {$bk->product->unit})",
                'time' => $bk->created_at,
                'color' => 'yellow'
            ];
        }
        
        // Low stock alerts
        $lowStock = Product::where('stock', '<=', DB::raw('min_stock_alert'))
            ->where('updated_at', '>=', Carbon::now()->subDay())
            ->get();
        
        foreach ($lowStock as $product) {
            $activities[] = [
                'type' => 'low_stock',
                'message' => "Low stock alert: {$product->name} ({$product->stock} {$product->unit} remaining)",
                'time' => $product->updated_at,
                'color' => 'red'
            ];
        }
        
        // Sort by time (newest first) and return top 10
        usort($activities, function($a, $b) {
            return $b['time'] <=> $a['time'];
        });
        
        return array_slice($activities, 0, 10);
    }
    
    public function getDashboardData()
    {
        // Data untuk chart real-time
        $todaySales = BarangKeluar::whereDate('date', Carbon::today())
            ->sum('total_price');
            
        $yesterdaySales = BarangKeluar::whereDate('date', Carbon::yesterday())
            ->sum('total_price');
            
        $salesChange = $yesterdaySales > 0 
            ? (($todaySales - $yesterdaySales) / $yesterdaySales) * 100 
            : 0;
        
        return response()->json([
            'today_sales' => $todaySales,
            'sales_change' => round($salesChange, 2),
            'total_members_today' => User::where('is_admin', false)
                ->whereDate('created_at', Carbon::today())
                ->count()
        ]);
    }
}