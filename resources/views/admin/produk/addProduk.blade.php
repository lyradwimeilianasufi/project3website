@extends('layouts.app')
@section('title', 'Tambah Produk Baru')
@section('style')
<link rel="stylesheet" href="{{ asset('css/layouts/sidebar.css') }}">
@endsection
@section('content')

    @include('layouts.sidebar')

    <div id="main-content" class="min-h-screen">
        <div class="bg-white shadow-md py-4 px-6 flex justify-between items-center">
            <button onclick="toggleSidebar()" class="text-blue-600 hover:text-blue-900 focus:outline-none">
                <i class="fas fa-bars text-xl"></i>
            </button>
            <h2 class="font-poppins text-xl font-semibold">Tambah Produk</h2>
            <div class="flex items-center">
                <span class="text-gray-600 mr-4">{{ Auth::user()->name }}</span>
                <img src="https://ui-avatars.com/api/?name={{ Auth::user()->name }}&background=0D47A1&color=fff"
                     alt="Admin" class="w-8 h-8 rounded-full">
            </div>
        </div>

        <div class="p-6">
            <div class="bg-white rounded-lg shadow-md p-6">
                <form action="{{ route('admin.produk.store') }}" method="POST" enctype="multipart/form-data" onsubmit="return validatePrices()">
                    @csrf
                    <div class="border-b border-gray-200 pb-6">
                        <h3 class="text-lg font-semibold mb-4">Informasi Dasar</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nama Produk</label>
                                <input type="text" name="product_name" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter product name" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Kategori</label>
                                <select name="category" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                    <option value="">Pilih Kategori</option>
                                    <option value="groceries">Bahan Makanan</option>
                                    <option value="beverages">Minuman</option>
                                    <option value="snacks">Makanan Ringan</option>
                                    <option value="household">Rumah Tangga</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Merk</label>
                                <input type="text" name="brand" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter brand name" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">SKU</label>
                                <input type="text" name="sku" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter SKU" required>
                            </div>
                        </div>
                    </div>

                    <div class="border-b border-gray-200 pb-6">
                        <h3 class="text-lg font-semibold mb-4">Harga dan Stok</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Harga Pembelian</label>
                                <input type="number" name="purchase_price" id="purchase_price" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter purchase price" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Harga Penjualan</label>
                                <input type="number" name="selling_price" id="selling_price" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter selling price" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Stok Awal</label>
                                <input type="number" name="initial_stock" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter initial stock" required>
                            </div>
                        </div>
                    </div>

                    <div class="border-b border-gray-200 pb-6">
                        <h3 class="text-lg font-semibold mb-4">Detail Produk</h3>
                        <div class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Keterangan</label>
                                <textarea name="description" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter product description"></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Gambar Produk</label>
                                <div id="imagePreview" class="image-preview mb-4">
                                    <div class="text-center text-gray-500">
                                        <i class="fas fa-image text-4xl mb-2"></i>
                                        <p>Klik untuk mengunggah atau seret dan lepas</p>
                                        <p class="text-sm">PNG, JPG hingga 5MB</p>
                                    </div>
                                </div>
                                <input type="file" name="image" accept="image/*" onchange="previewImage(event)" class="w-full">
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-lg font-semibold mb-4">Informasi Tambahan</h3>
                        <select name="unit" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            <option value="">Pilih Unit</option>
                            <option value="pcs">Pieces</option>
                            <option value="kg">Kilogram</option>
                            <option value="g">Gram</option>
                            <option value="l">Liter</option>
                        </select>
                        <input type="number" name="min_stock_alert" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 mt-4" placeholder="Masukan nilai Stok Minimum" required>
                    </div>

                    <div class="flex justify-end space-x-4 pt-6">
                        <a href="{{ route('admin.produk') }}" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Batal</a>
                        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Simpan Produk</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('-translate-x-full');
        document.getElementById('main-content').classList.toggle('ml-0');
    }
    window.toggleSidebar = toggleSidebar;

    function previewImage(event) {
        const imagePreviewDiv = document.getElementById('imagePreview');
        imagePreviewDiv.innerHTML = ''; // Clear previous preview

        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.classList.add('w-full', 'h-auto', 'rounded');
                imagePreviewDiv.appendChild(img);
            }
            reader.readAsDataURL(file);
        } else {
            imagePreviewDiv.innerHTML = `
                <div class="text-center text-gray-500">
                    <i class="fas fa-image text-4xl mb-2"></i>
                    <p>Klik untuk mengunggah atau seret dan lepas</p>
                    <p class="text-sm">PNG, JPG hingga 5MB</p>
                </div>
            `;
        }
    }

    function validatePrices() {
        const purchasePrice = parseFloat(document.getElementById('purchase_price').value);
        const sellingPrice = parseFloat(document.getElementById('selling_price').value);

        if (purchasePrice < 0) {
            alert('Harga Pembelian tidak boleh kurang dari 0.');
            return false;
        }

        if (sellingPrice < 0) {
            alert('Harga Penjualan tidak boleh kurang dari 0.');
            return false;
        }

        return true;
    }
</script>
@endsection