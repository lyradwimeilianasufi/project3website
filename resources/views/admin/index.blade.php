@extends('layouts.app')
@section('title', 'Dashboard Admin')
@section('style')
<link rel="stylesheet" href="{{ asset('css/layouts/sidebar.css') }}">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
@endsection
@section('content')
    @include('layouts.sidebar')

    <!-- Main Content -->
    <div id="main-content" class="min-h-screen bg-gray-50">
        <!-- Top Navigation -->
        <div class="bg-white shadow-md py-4 px-6 flex justify-between items-center">
            <button onclick="toggleSidebar()" class="text-blue-600 hover:text-blue-900 focus:outline-none">
                <i class="fas fa-bars text-xl"></i>
            </button>
            <h2 class="font-poppins text-xl font-semibold">Dashboard Overview</h2>
            <div class="flex items-center">
                {{-- <span class="text-gray-600 mr-4">{{ Auth::user()->full_name }}</span> --}}
                <img src="https://ui-avatars.com/api/?name=&background=0D47A1&color=fff" 
                    alt="Admin" class="w-8 h-8 rounded-full">
            </div>
        </div>

        <!-- Dashboard Content -->
        <div class="p-6">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <!-- Total Members -->
                <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
                    <div class="flex items-center">
                        <div class="p-3 bg-blue-100 rounded-full">
                            <i class="fas fa-users text-blue-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-gray-500 text-sm">Total Members</h3>
                            <p class="text-2xl font-semibold">{{ $totalMembers }}</p>
                            <p class="text-sm text-green-600">+{{ $newUsers }} new this week</p>
                        </div>
                    </div>
                </div>

                <!-- Total Products -->
                <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
                    <div class="flex items-center">
                        <div class="p-3 bg-green-100 rounded-full">
                            <i class="fas fa-box text-green-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-gray-500 text-sm">Total Products</h3>
                            <p class="text-2xl font-semibold">{{ $totalProducts }}</p>
                            <p class="text-sm text-gray-600">{{ $totalSuppliers }} suppliers</p>
                        </div>
                    </div>
                </div>

                <!-- Total Sales -->
                <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
                    <div class="flex items-center">
                        <div class="p-3 bg-yellow-100 rounded-full">
                            <i class="fas fa-shopping-cart text-yellow-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-gray-500 text-sm">Total Sales</h3>
                            <p class="text-2xl font-semibold">Rp {{ number_format($totalSales, 0, ',', '.') }}</p>
                            <p class="text-sm text-gray-600">{{ $completedTransactions }} completed transactions</p>
                        </div>
                    </div>
                </div>

                <!-- Low Stock Items -->
                <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
                    <div class="flex items-center">
                        <div class="p-3 bg-red-100 rounded-full">
                            <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-gray-500 text-sm">Low Stock Items</h3>
                            <p class="text-2xl font-semibold">{{ $lowStockItems }}</p>
                            <p class="text-sm text-red-600">{{ $outOfStockItems }} out of stock</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Second Row Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <!-- Revenue -->
                <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
                    <div class="flex items-center">
                        <div class="p-3 bg-purple-100 rounded-full">
                            <i class="fas fa-money-bill-wave text-purple-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-gray-500 text-sm">Total Revenue</h3>
                            <p class="text-xl font-semibold">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Total Purchase -->
                <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
                    <div class="flex items-center">
                        <div class="p-3 bg-orange-100 rounded-full">
                            <i class="fas fa-truck-loading text-orange-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-gray-500 text-sm">Total Purchase</h3>
                            <p class="text-xl font-semibold">Rp {{ number_format($totalPurchase, 0, ',', '.') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Pending Transactions -->
                <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
                    <div class="flex items-center">
                        <div class="p-3 bg-indigo-100 rounded-full">
                            <i class="fas fa-clock text-indigo-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-gray-500 text-sm">Pending Transactions</h3>
                            <p class="text-xl font-semibold">{{ $pendingTransactions }}</p>
                            <p class="text-sm text-gray-600">of {{ $totalTransactions }} total</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Monthly Sales Chart -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-poppins text-lg font-semibold">Monthly Sales Performance</h3>
                        <select id="salesYear" class="border rounded px-2 py-1 text-sm">
                            @for($year = date('Y'); $year >= 2020; $year--)
                                <option value="{{ $year }}" {{ $year == date('Y') ? 'selected' : '' }}>{{ $year }}</option>
                            @endfor
                        </select>
                    </div>
                    <canvas id="monthlySalesChart" height="300"></canvas>
                </div>

                <!-- Revenue vs Purchase Comparison -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="font-poppins text-lg font-semibold mb-4">Revenue vs Purchase Comparison</h3>
                    <canvas id="revenuePurchaseChart" height="300"></canvas>
                </div>
            </div>

            <!-- Product Analytics Row -->
            {{-- <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Product Categories Distribution -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="font-poppins text-lg font-semibold mb-4">Products by Category</h3>
                    <canvas id="categoryChart" height="250"></canvas>
                </div>

                <!-- Stock Level Analysis -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="font-poppins text-lg font-semibold mb-4">Stock Level Analysis</h3>
                    <canvas id="stockAnalysisChart" height="250"></canvas>
                </div>
            </div> --}}

            <!-- Sales Analytics Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Top Selling Products -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="font-poppins text-lg font-semibold mb-4">Top Selling Products</h3>
                    <canvas id="topProductsChart" height="300"></canvas>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="font-poppins text-lg font-semibold mb-4">Transaction Status</h3>
                    <canvas id="transactionStatusChart" height="250"></canvas>
                </div>
            </div>

            <!-- Membership & Transaction Analytics -->
            {{-- <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Membership Distribution -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="font-poppins text-lg font-semibold mb-4">Membership Distribution</h3>
                    <canvas id="membershipChart" height="250"></canvas>
                </div>

                <!-- Transaction Status Distribution -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="font-poppins text-lg font-semibold mb-4">Transaction Status</h3>
                    <canvas id="transactionStatusChart" height="250"></canvas>
                </div>
            </div> --}}

            <!-- Daily Sales Trend -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-poppins text-lg font-semibold">Daily Sales Trend (Last 30 Days)</h3>
                </div>
                <canvas id="dailySalesChart" height="100"></canvas>
            </div>

            <!-- Bottom Section: Tables and Recent Activities -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Recent Activities -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="font-poppins text-lg font-semibold mb-4">Recent Activities</h3>
                    <div class="space-y-4 max-h-96 overflow-y-auto">
                        @foreach($recentActivities as $activity)
                        <div class="flex items-center p-3 hover:bg-gray-50 rounded-lg transition-colors">
                            <div class="w-3 h-3 bg-{{ $activity['color'] }}-500 rounded-full"></div>
                            <p class="ml-3 text-gray-600 flex-1">{{ $activity['message'] }}</p>
                            <span class="ml-auto text-sm text-gray-500">{{ $activity['time']->diffForHumans() }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Low Stock Products -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-poppins text-lg font-semibold">Low Stock Alert</h3>
                        <span class="bg-red-100 text-red-800 px-2 py-1 rounded-full text-sm">{{ $lowStockItems }} items</span>
                    </div>
                    <div class="overflow-x-auto max-h-96">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b bg-gray-50">
                                    <th class="text-left py-3 px-4 font-semibold">Product</th>
                                    <th class="text-left py-3 px-4 font-semibold">Stock</th>
                                    <th class="text-left py-3 px-4 font-semibold">Min Alert</th>
                                    <th class="text-left py-3 px-4 font-semibold">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($lowStockProducts as $product)
                                <tr class="border-b hover:bg-gray-50 transition-colors">
                                    <td class="py-3 px-4">
                                        <div class="flex items-center">
                                            @if($product->image)
                                                <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="w-8 h-8 rounded mr-3">
                                            @else
                                                <div class="w-8 h-8 bg-gray-200 rounded mr-3 flex items-center justify-center">
                                                    <i class="fas fa-box text-gray-400 text-sm"></i>
                                                </div>
                                            @endif
                                            <span class="font-medium">{{ \Illuminate\Support\Str::limit($product->name, 25) }}</span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">{{ $product->stock }} {{ $product->unit }}</td>
                                    <td class="py-3 px-4">{{ $product->min_stock_alert }} {{ $product->unit }}</td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-medium {{ $product->stock == 0 ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800' }}">
                                            {{ $product->stock == 0 ? 'Out of Stock' : 'Low Stock' }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Recent Users & Transactions -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
                <!-- Recent Users -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="font-poppins text-lg font-semibold mb-4">Recent Members</h3>
                    <div class="space-y-3">
                        @foreach($recentUsers as $user)
                        <div class="flex items-center p-3 hover:bg-gray-50 rounded-lg transition-colors">
                            <img src="https://ui-avatars.com/api/?name={{ $user->full_name }}&background=0D47A1&color=fff" 
                                alt="{{ $user->full_name }}" class="w-10 h-10 rounded-full">
                            <div class="ml-3 flex-1">
                                <p class="font-medium">{{ $user->full_name }}</p>
                                <p class="text-sm text-gray-500">
                                    <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs">{{ $user->membership_type }}</span>
                                    • {{ $user->created_at->diffForHumans() }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-gray-500">{{ $user->phone_number }}</p>
                                <p class="text-xs text-gray-400">{{ $user->city }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Recent Transactions -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="font-poppins text-lg font-semibold mb-4">Recent Transactions</h3>
                    <div class="space-y-3">
                        @foreach($recentTransactions as $transaction)
                        <div class="flex justify-between items-center p-3 hover:bg-gray-50 rounded-lg transition-colors">
                            <div class="flex-1">
                                <p class="font-medium">#{{ $transaction->transaction_id }}</p>
                                <p class="text-sm text-gray-500">{{ $transaction->customer->full_name ?? 'N/A' }}</p>
                                <p class="text-xs text-gray-400">{{ $transaction->created_at->format('M d, Y H:i') }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-medium text-lg">Rp {{ number_format($transaction->total, 0, ',', '.') }}</p>
                                <span class="px-2 py-1 rounded-full text-xs font-medium
                                    {{ $transaction->status == 'completed' ? 'bg-green-100 text-green-800' : 
                                       ($transaction->status == 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                       'bg-red-100 text-red-800') }}">
                                    {{ ucfirst($transaction->status) }}
                                </span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Color Palette
        const colors = {
            primary: '#3B82F6',
            success: '#10B981',
            warning: '#F59E0B',
            danger: '#EF4444',
            info: '#8B5CF6',
            secondary: '#6B7280',
            blue: '#3B82F6',
            green: '#10B981',
            yellow: '#F59E0B',
            red: '#EF4444',
            purple: '#8B5CF6',
            indigo: '#6366F1',
            pink: '#EC4899',
            orange: '#F97316',
            teal: '#14B8A6'
        };

        // Monthly Sales Chart
        const monthlySalesCtx = document.getElementById('monthlySalesChart').getContext('2d');
        const monthlySalesChart = new Chart(monthlySalesCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [{
                    label: 'Sales Revenue',
                    data: @json($monthlySales),
                    borderColor: colors.primary,
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: colors.primary,
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                return 'Rp ' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + (value / 1000000).toFixed(1) + 'M';
                            }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // Revenue vs Purchase Chart
        const revenuePurchaseCtx = document.getElementById('revenuePurchaseChart').getContext('2d');
        new Chart(revenuePurchaseCtx, {
            type: 'bar',
            data: {
                labels: ['Revenue', 'Purchase'],
                datasets: [{
                    label: 'Amount (Rp)',
                    data: [{{ $totalRevenue }}, {{ $totalPurchase }}],
                    backgroundColor: [colors.success, colors.warning],
                    borderColor: [colors.success, colors.warning],
                    borderWidth: 1,
                    borderRadius: 8,
                    barPercentage: 0.6
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Rp ' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + (value / 1000000).toFixed(1) + 'M';
                            }
                        }
                    }
                }
            }
        });

        // Category Chart
        // const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        // new Chart(categoryCtx, {
        //     type: 'doughnut',
        //     data: {
        //         labels: @json($productByCategory->pluck('category')),
        //         datasets: [{
        //             data: @json($productByCategory->pluck('total')),
        //             backgroundColor: [
        //                 colors.primary, colors.success, colors.warning, colors.danger, colors.info,
        //                 colors.secondary, colors.pink, colors.orange, colors.teal, colors.indigo
        //             ],
        //             borderWidth: 2,
        //             borderColor: '#fff'
        //         }]
        //     },
        //     options: {
        //         responsive: true,
        //         cutout: '60%',
        //         plugins: {
        //             legend: {
        //                 position: 'bottom',
        //                 labels: {
        //                     padding: 20,
        //                     usePointStyle: true
        //                 }
        //             }
        //         }
        //     }
        // });

        // Stock Analysis Chart
        // const stockAnalysisCtx = document.getElementById('stockAnalysisChart').getContext('2d');
        // new Chart(stockAnalysisCtx, {
        //     type: 'doughnut',
        //     data: {
        //         labels: ['Adequate Stock', 'Low Stock', 'Out of Stock'],
        //         datasets: [{
        //             data: [
        //                 {{ $totalProducts - $lowStockItems }},
        //                 {{ $lowStockItems - $outOfStockItems }},
        //                 {{ $outOfStockItems }}
        //             ],
        //             backgroundColor: [colors.success, colors.warning, colors.danger],
        //             borderWidth: 2,
        //             borderColor: '#fff'
        //         }]
        //     },
        //     options: {
        //         responsive: true,
        //         cutout: '60%',
        //         plugins: {
        //             legend: {
        //                 position: 'bottom'
        //             }
        //         }
        //     }
        // });

        // Top Products Chart
        const topProductsCtx = document.getElementById('topProductsChart').getContext('2d');
        new Chart(topProductsCtx, {
            type: 'bar',
            data: {
                labels: @json($topProducts->pluck('product.name')),
                datasets: [{
                    label: 'Quantity Sold',
                    data: @json($topProducts->pluck('total_sold')),
                    backgroundColor: colors.info,
                    borderRadius: 8,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                indexAxis: 'y',
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Membership Sales Chart
        // const membershipSalesCtx = document.getElementById('membershipSalesChart').getContext('2d');
        // new Chart(membershipSalesCtx, {
        //     type: 'polarArea',
        //     data: {
        //         labels: @json($membershipSales->pluck('membership_type')),
        //         datasets: [{
        //             data: @json($membershipSales->pluck('total_sales')),
        //             backgroundColor: [
        //                 colors.primary, colors.success, colors.warning, colors.danger, colors.info
        //             ],
        //             borderWidth: 2,
        //             borderColor: '#fff'
        //         }]
        //     },
        //     options: {
        //         responsive: true,
        //         plugins: {
        //             legend: {
        //                 position: 'bottom'
        //             }
        //         }
        //     }
        // });

        // Membership Distribution Chart
        // const membershipCtx = document.getElementById('membershipChart').getContext('2d');
        // new Chart(membershipCtx, {
        //     type: 'pie',
        //     data: {
        //         labels: @json($membershipDistribution->pluck('membership_type')),
        //         datasets: [{
        //             data: @json($membershipDistribution->pluck('total')),
        //             backgroundColor: [colors.primary, colors.success, colors.warning, colors.danger],
        //             borderWidth: 3,
        //             borderColor: '#fff'
        //         }]
        //     },
        //     options: {
        //         responsive: true,
        //         plugins: {
        //             legend: {
        //                 position: 'bottom'
        //             }
        //         }
        //     }
        // });

        // Transaction Status Chart
        const transactionStatusCtx = document.getElementById('transactionStatusChart').getContext('2d');
        new Chart(transactionStatusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Completed', 'Pending', 'Failed'],
                datasets: [{
                    data: [{{ $completedTransactions }}, {{ $pendingTransactions }}, {{ $totalTransactions - $completedTransactions - $pendingTransactions }}],
                    backgroundColor: [colors.success, colors.warning, colors.danger],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Daily Sales Chart
        const dailySalesCtx = document.getElementById('dailySalesChart').getContext('2d');
        new Chart(dailySalesCtx, {
            type: 'line',
            data: {
                labels: @json($dailySales->pluck('date')),
                datasets: [{
                    label: 'Daily Sales',
                    data: @json($dailySales->pluck('total')),
                    borderColor: colors.primary,
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + (value / 1000).toFixed(0) + 'K';
                            }
                        }
                    }
                }
            }
        });

        // Update chart when year changes
        document.getElementById('salesYear').addEventListener('change', function() {
            // You can implement AJAX call here to update the chart data
            console.log('Year changed to:', this.value);
        });
    </script>
@endsection