@extends('layouts.app')
@section('title', 'Toko24 Assistant')
@section('style')
<link rel="stylesheet" href="{{ asset('css/dashboard/style.css') }}">

<style>
    body{
        overflow: hidden;
    }
    .chat-header {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        padding: 1rem 2rem;
        border-bottom: 1px solid #e5e7eb;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .chat-main {
        flex: 1;
        display: flex;
        max-width: 100vw;
        /* margin: 0 auto; */
        width: 100%;
        padding: 1rem;
        gap: 1rem;
        height: 100%;
        max-height: 86vh;
    }

    .chat-sidebar {
        width: 300px;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        display: flex;
        flex-direction: column;
    }

    .chat-messages {
        flex: 1;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .messages-container {
        flex: 1;
        overflow-y: auto;
        padding: 1rem 0;
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .message {
        max-width: 70%;
        padding: 1rem 1.25rem;
        border-radius: 18px;
        word-wrap: break-word;
        line-height: 1.5;
        animation: messageSlide 0.3s ease-out;
    }

    @keyframes messageSlide {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .user-message {
        align-self: flex-end;
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        color: white;
        border-bottom-right-radius: 4px;
    }

    .bot-message {
        align-self: flex-start;
        background: #f8fafc;
        color: #374151;
        border: 1px solid #e5e7eb;
        border-bottom-left-radius: 4px;
    }

    .chat-input-container {
        padding: 1rem 0 0;
        border-top: 1px solid #e5e7eb;
        margin-top: 1rem;
    }

    .chat-input-wrapper {
        display: flex;
        gap: 0.75rem;
        align-items: flex-end;
    }

    .chat-input {
        flex: 1;
        border: 1px solid #d1d5db;
        border-radius: 12px;
        padding: 0.75rem 1rem;
        font-size: 14px;
        outline: none;
        resize: none;
        min-height: 44px;
        max-height: 120px;
        transition: all 0.2s;
        background: white;
    }

    .chat-input:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .send-button {
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        color: white;
        border: none;
        border-radius: 12px;
        padding: 0.75rem 1.25rem;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 44px;
    }

    .send-button:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
    }

    .send-button:disabled {
        background: #9ca3af;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }

    .typing-indicator {
        display: inline-flex;
        gap: 4px;
        align-items: center;
        padding: 0.75rem 1.25rem;
        color: #6b7280;
        font-style: italic;
    }

    .typing-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background-color: #9ca3af;
        animation: typing 1.4s infinite ease-in-out;
    }

    .typing-dot:nth-child(1) {
        animation-delay: 0s;
    }

    .typing-dot:nth-child(2) {
        animation-delay: 0.2s;
    }

    .typing-dot:nth-child(3) {
        animation-delay: 0.4s;
    }

    @keyframes typing {

        0%,
        60%,
        100% {
            transform: translateY(0);
        }

        30% {
            transform: translateY(-5px);
        }
    }

    .product-suggestion {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 1rem;
        margin-top: 0.75rem;
        background: white;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    .suggestion-title {
        font-weight: 600;
        margin-bottom: 0.75rem;
        color: #374151;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .product-item {
        display: flex;
        align-items: center;
        padding: 0.75rem;
        border-radius: 8px;
        margin-bottom: 0.5rem;
        background: #f8fafc;
        border: 1px solid #f1f5f9;
        transition: all 0.2s;
        cursor: pointer;
        position: relative;
    }

    .product-item:hover {
        border-color: #3b82f6;
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(59, 130, 246, 0.2);
    }

    .product-item img {
        width: 50px;
        height: 50px;
        object-fit: contain;
        margin-right: 1rem;
        border-radius: 8px;
        background: white;
        padding: 0.25rem;
        border: 1px solid #e5e7eb;
    }

    .product-info {
        flex: 1;
    }

    .product-name {
        font-weight: 500;
        color: #374151;
        font-size: 14px;
        margin-bottom: 0.25rem;
    }

    .product-price {
        color: #3b82f6;
        font-weight: 600;
        font-size: 13px;
        margin-bottom: 0.25rem;
    }

    .product-category {
        color: #6b7280;
        font-size: 12px;
    }

    .add-to-cart-btn {
        background: #10b981;
        color: white;
        border: none;
        border-radius: 6px;
        padding: 0.5rem 0.75rem;
        font-size: 12px;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 0.25rem;
        margin-left: 0.5rem;
    }

    .add-to-cart-btn:hover {
        background: #059669;
        transform: translateY(-1px);
    }

    .add-to-cart-btn:disabled {
        background: #9ca3af;
        cursor: not-allowed;
        transform: none;
    }

    .quick-actions {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        margin-bottom: 1.5rem;
    }

    .quick-action-btn {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 0.75rem 1rem;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.2s;
        text-align: left;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .quick-action-btn:hover {
        background: #3b82f6;
        color: white;
        border-color: #3b82f6;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }

    .assistant-info {
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        color: white;
        padding: 1.5rem;
        border-radius: 12px;
        margin-bottom: 1.5rem;
    }

    .assistant-info h3 {
        font-size: 1.25rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .feature-list {
        list-style: none;
        padding: 0;
        margin: 1rem 0;
    }

    .feature-list li {
        padding: 0.25rem 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .welcome-message {
        text-align: center;
        padding: 2rem;
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        border-radius: 12px;
        margin-bottom: 1.5rem;
    }

    .welcome-message h2 {
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .welcome-message p {
        opacity: 0.9;
        margin-bottom: 1rem;
    }

    .cart-notification {
        position: fixed;
        top: 100px;
        right: 20px;
        background: #10b981;
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        display: flex;
        align-items: center;
        gap: 0.5rem;
        z-index: 1000;
        animation: slideIn 0.3s ease-out;
    }

    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }

        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @media (max-width: 768px) {
        .chat-main {
            flex-direction: column;
            padding: 0.5rem;
        }

        .chat-sidebar {
            width: 100%;
            order: 2;
        }

        .chat-messages {
            order: 1;
            flex: 1;
        }

        .message {
            max-width: 85%;
        }
    }

</style>
@endsection

@section('content')
@include('layouts.navbar')

<div class="chat-container">


    <div class="chat-main">
        <!-- Sidebar -->
        <div class="chat-sidebar">
            <div class="assistant-info">
                <h3><i class="fas fa-robot"></i> Toko24 Assistant</h3>
                <p class="text-sm opacity-90">Saya siap membantu Anda menemukan produk yang tepat!</p>
            </div>

            <div class="quick-actions">
                <button class="quick-action-btn" data-action="rekomendasi produk terbaik">
                    <i class="fas fa-star"></i> Rekomendasi Terbaik
                </button>
                <button class="quick-action-btn" data-action="produk elektronik">
                    <i class="fas fa-laptop"></i> Produk Elektronik
                </button>
                <button class="quick-action-btn" data-action="pakaian fashion">
                    <i class="fas fa-tshirt"></i> Pakaian & Fashion
                </button>
                <button class="quick-action-btn" data-action="produk dengan harga terjangkau">
                    <i class="fas fa-wallet"></i> Harga Terjangkau
                </button>
                <button class="quick-action-btn" data-action="produk terbaru">
                    <i class="fas fa-newspaper"></i> Produk Terbaru
                </button>
                <button class="quick-action-btn" data-action="bantuan pemesanan">
                    <i class="fas fa-shopping-cart"></i> Bantuan Pemesanan
                </button>
            </div>

            <div class="mt-auto text-xs text-gray-500 text-center">
                <p>Toko24</p>
            </div>
        </div>

        <!-- Chat Messages -->
        <div class="chat-messages ">
            <div class="messages-container overflow-scroll max-h-full" id="messagesContainer">
                <!-- Welcome Message -->
                <div class="welcome-message">
                    <i class="fas fa-robot fa-3x mb-4"></i>
                    <h2>Halo! Saya Asisten Toko24 🤖</h2>
                    <p>Saya akan membantu Anda menemukan produk yang sesuai dengan kebutuhan</p>
                    <div class="flex flex-wrap gap-2 justify-center mt-4">
                        <span class="px-3 py-1 bg-white bg-opacity-20 rounded-full text-sm">Cari Produk</span>
                        <span class="px-3 py-1 bg-white bg-opacity-20 rounded-full text-sm">Rekomendasi</span>
                        <span class="px-3 py-1 bg-white bg-opacity-20 rounded-full text-sm">Tambah ke Keranjang</span>
                    </div>
                </div>

                <!-- Messages will be appended here -->
            </div>

            <!-- Chat Input -->
            <div class="chat-input-container">
                <div class="chat-input-wrapper">
                    <textarea id="chatInput" placeholder="Tanyakan tentang produk yang Anda cari..." class="chat-input" rows="1"></textarea>
                    <button id="sendMessage" class="send-button">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
                <div class="flex justify-between items-center mt-2 text-xs text-gray-500">
                    <span>Tekan Enter untuk mengirim</span>
                    <span>Toko24 Assistant AI</span>
                </div>
            </div>
        </div>
    </div>
</div>

@php
$productData = $products->map(function ($product) {
return [
'id' => $product->id,
'name' => $product->name,
'category' => $product->category,
'brand' => $product->brand,
'selling_price' => $product->selling_price,
'formatted_price' => 'Rp ' . number_format($product->selling_price, 0, ',', '.'),
'description' => $product->description,
'image' => 'imgProduk/' . basename($product->image),
'stock' => $product->stock,
];
});
$baseURL = asset('');

@endphp

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const chatInput = document.getElementById('chatInput');
        const sendMessage = document.getElementById('sendMessage');
        const messagesContainer = document.getElementById('messagesContainer');

        // API Configuration
        const API_KEY = 'AIzaSyBdgghKvypUoJQefd_q7leY96NW_W4AMp8';
        const API_URL = `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=${API_KEY}`;

        // System prompt for Toko24 assistant
        const SYSTEM_PROMPT = `Anda adalah asisten AI untuk Toko24, toko online yang menjual berbagai produk. Anda HARUS merespons dalam bahasa Indonesia.

Tugas utama Anda:
1. Membantu pengguna mencari produk spesifik
2. Memberikan rekomendasi produk berdasarkan kebutuhan
3. Menjelaskan fitur dan spesifikasi produk
4. Membandingkan produk
5. Membantu dengan proses pemesanan

Gaya respons:
- Ramah, informatif, dan membantu
- Fokus pada produk dan kebutuhan pengguna
- Gunakan bahasa yang mudah dipahami
- Tawarkan bantuan lebih lanjut

JANGAN membuat respons terlalu panjang. Berikan informasi yang relevan dan langsung ke intinya.`;

        const products = @json($productData);

        // Auto-resize textarea
        chatInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 120) + 'px';
        });

        // Send message on button click
        sendMessage.addEventListener('click', sendUserMessage);

        // Send message on Enter key (with Shift for new line)
        chatInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendUserMessage();
            }
        });

        // Quick action buttons
        document.addEventListener('click', function(e) {
            if (e.target.closest('.quick-action-btn')) {
                const button = e.target.closest('.quick-action-btn');
                const action = button.getAttribute('data-action');
                chatInput.value = action;
                sendUserMessage();
            }
        });

        // Function to send user message
        async function sendUserMessage() {
            const message = chatInput.value.trim();
            if (message === '') return;

            // Add user message to chat
            addMessage(message, 'user');

            // Clear input and disable send button
            chatInput.value = '';
            chatInput.style.height = 'auto';
            sendMessage.disabled = true;

            // Show typing indicator
            showTypingIndicator();

            try {
                // Generate response using Gemini API
                const response = await generateGeminiResponse(message);
                removeTypingIndicator();

                // Add bot response with product suggestions
                addBotResponseWithProducts(response, message);
            } catch (error) {
                removeTypingIndicator();
                addMessage('Maaf, terjadi kesalahan saat memproses permintaan Anda. Silakan coba lagi.', 'bot');
                console.error('Error:', error);
            } finally {
                sendMessage.disabled = false;
                chatInput.focus();
            }
        }

        // Function to add message to chat
        function addMessage(text, sender) {
            // Remove welcome message if it's the first user message
            const welcomeMessage = messagesContainer.querySelector('.welcome-message');
            if (welcomeMessage && sender === 'user') {
                welcomeMessage.remove();
            }

            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${sender}-message`;
            messageDiv.textContent = text;
            messagesContainer.appendChild(messageDiv);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        // Function to add bot response with product suggestions
        function addBotResponseWithProducts(response, userMessage) {
            const messageDiv = document.createElement('div');
            messageDiv.className = 'message bot-message';

            // Format the response text
            const formattedResponse = formatBotResponse(response);
            messageDiv.innerHTML = formattedResponse;

            // Get relevant products based on user message
            const relevantProducts = getRelevantProducts(userMessage, response);
const BASE_URL = "{{ $baseURL }}"; // menghasilkan: https://domainmu.com/

            // Add product suggestions if we have relevant products
            if (relevantProducts.length > 0) {
                const suggestionsDiv = document.createElement('div');
                suggestionsDiv.className = 'product-suggestion';
                suggestionsDiv.innerHTML = `
                    <div class="suggestion-title">
                        <i class="fas fa-gift"></i>
                        Produk Rekomendasi (${relevantProducts.length} ditemukan)
                    </div>
                `;

                relevantProducts.slice(0, 4).forEach(product => {
                    const productDiv = document.createElement('div');
                    productDiv.className = 'product-item';
                    productDiv.innerHTML = `
                    <img src="${BASE_URL}${product.image}" alt="${product.name}"
                        onerror="this.src='${BASE_URL}img/placeholder-product.jpg'">
                    <div class="product-info">
                        <div class="product-name">${product.name}</div>
                        <div class="product-price">${product.formatted_price}</div>
                        <div class="product-category">${product.category} • Stok: ${product.stock}</div>
                    </div>
                    <button class="add-to-cart-btn" data-product-id="${product.id}">
                        <i class="fas fa-cart-plus"></i>
                        Add
                    </button>
                `;

                    suggestionsDiv.appendChild(productDiv);
                });

                messageDiv.appendChild(suggestionsDiv);

                // Add event listeners to add-to-cart buttons
                setTimeout(() => {
                    document.querySelectorAll('.add-to-cart-btn').forEach(button => {
                        button.addEventListener('click', function(e) {
                            e.stopPropagation();
                            const productId = this.getAttribute('data-product-id');
                            addToCart(productId);
                        });
                    });
                }, 100);
            }

            messagesContainer.appendChild(messageDiv);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        // Function to format bot response
        function formatBotResponse(text) {
            return text
                .replace(/\n/g, '<br>')
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/(Halo|Hi|Hey|Hai)/gi, '<strong>$1</strong>')
                .replace(/(Toko24)/gi, '<strong class="text-blue-600">$1</strong>');
        }

        // Function to show typing indicator
        function showTypingIndicator() {
            const typingDiv = document.createElement('div');
            typingDiv.className = 'message bot-message typing-indicator';
            typingDiv.id = 'typingIndicator';
            typingDiv.innerHTML = `
                Asisten mengetik...
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
            `;
            messagesContainer.appendChild(typingDiv);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        // Function to remove typing indicator
        function removeTypingIndicator() {
            const typingIndicator = document.getElementById('typingIndicator');
            if (typingIndicator) {
                typingIndicator.remove();
            }
        }

        // Function to generate response using Gemini API
        async function generateGeminiResponse(userMessage) {
            const productInfo = products.map(p =>
                `${p.name} (${p.category}) - ${p.formatted_price} - Stok: ${p.stock}`
            ).join('\n');

            const prompt = `${SYSTEM_PROMPT}

INFORMASI PRODUK YANG TERSEDIA:
${productInfo}

PERTANYAAN PENGGUNA: "${userMessage}"

JAWABAN (bahasa Indonesia, maksimal 3 paragraf, fokus pada rekomendasi produk jika relevan):`;

            const requestBody = {
                contents: [{
                    parts: [{
                        text: prompt
                    }]
                }]
                , generationConfig: {
                    temperature: 0.7
                    , topK: 40
                    , topP: 0.95
                    , maxOutputTokens: 800
                , }
            };

            const response = await fetch(API_URL, {
                method: 'POST'
                , headers: {
                    'Content-Type': 'application/json'
                , }
                , body: JSON.stringify(requestBody)
            });

            if (!response.ok) {
                throw new Error(`API request failed with status ${response.status}`);
            }

            const data = await response.json();

            if (data.candidates && data.candidates[0] && data.candidates[0].content) {
                return data.candidates[0].content.parts[0].text;
            } else {
                throw new Error('Invalid response format from API');
            }
        }

        // Function to get relevant products based on user message
        function getRelevantProducts(userMessage, botResponse) {
            const lowerMessage = userMessage.toLowerCase();
            const lowerResponse = botResponse.toLowerCase();

            let filteredProducts = products.filter(p => p.stock > 0);

            // Filter by category and keywords
            if (lowerMessage.includes('elektronik') || lowerMessage.includes('laptop') || lowerMessage.includes('hp') || lowerMessage.includes('smartphone')) {
                filteredProducts = filteredProducts.filter(p =>
                    p.category.toLowerCase().includes('elektronik') ||
                    p.name.toLowerCase().includes('laptop') ||
                    p.name.toLowerCase().includes('smartphone') ||
                    p.name.toLowerCase().includes('headphone') ||
                    p.name.toLowerCase().includes('tablet')
                );
            } else if (lowerMessage.includes('pakaian') || lowerMessage.includes('baju') || lowerMessage.includes('fashion') || lowerMessage.includes('kaos')) {
                filteredProducts = filteredProducts.filter(p =>
                    p.category.toLowerCase().includes('pakaian') ||
                    p.name.toLowerCase().includes('baju') ||
                    p.name.toLowerCase().includes('kaos') ||
                    p.name.toLowerCase().includes('kemeja') ||
                    p.name.toLowerCase().includes('celana')
                );
            } else if (lowerMessage.includes('murah') || lowerMessage.includes('harga terjangkau') || lowerMessage.includes('budget')) {
                filteredProducts = filteredProducts.filter(p => {
                    return p.selling_price < 500000;
                }).sort((a, b) => a.selling_price - b.selling_price);
            }

            // If no specific filter, return products with good stock
            if (filteredProducts.length === 0) {
                filteredProducts = products.filter(p => p.stock > 0)
                    .sort(() => Math.random() - 0.5)
                    .slice(0, 4);
            }

            return filteredProducts.slice(0, 4);
        }

        // Function to add product to cart
        async function addToCart(productId) {
            const product = products.find(p => p.id == productId);
            if (!product) return;

            const button = document.querySelector(`[data-product-id="${productId}"]`);
            const originalText = button.innerHTML;

            // Disable button and show loading
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            try {
                // Send AJAX request to add to cart
                const response = await fetch('{{ route("cart.add") }}', {
                    method: 'POST'
                    , headers: {
                        'Content-Type': 'application/json'
                        , 'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                    , body: JSON.stringify({
                        product: {
                            id: product.id
                            , name: product.name
                            , price: product.selling_price
                            , image: product.image
                        }
                        , quantity: 1
                    })
                });

                if (response.ok) {
                    // Show success notification
                    showCartNotification(`${product.name} berhasil ditambahkan ke keranjang!`);

                    // Update button to show success
                    button.innerHTML = '<i class="fas fa-check"></i> Added';
                    button.style.background = '#10b981';

                    setTimeout(() => {
                        button.innerHTML = originalText;
                        button.style.background = '';
                        button.disabled = false;
                    }, 2000);
                } else {
                    throw new Error('Failed to add to cart');
                }
            } catch (error) {
                console.error('Error adding to cart:', error);
                button.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error';
                button.style.background = '#ef4444';

                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.style.background = '';
                    button.disabled = false;
                }, 2000);
            }
        }

        // Function to show cart notification
        function showCartNotification(message) {
            const notification = document.createElement('div');
            notification.className = 'cart-notification';
            notification.innerHTML = `
                <i class="fas fa-check-circle"></i>
                <span>${message}</span>
            `;

            document.body.appendChild(notification);

            setTimeout(() => {
                notification.remove();
            }, 3000);
        }
    });

</script>
@endsection
