<?php
require_once 'config.php';
requireAuth();

$page_title = 'Point of Sale';
$settings = getSettings();

// Get categories and products
$categories = $conn->query("SELECT * FROM categories WHERE status = 'active' ORDER BY name");
$products = $conn->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.status = 'active' AND p.stock_quantity > 0 ORDER BY p.name");

include 'header.php';
?>

<style>
.pos-container {
    display: grid;
    grid-template-columns: 1fr 420px;
    gap: 1.5rem;
    height: calc(100vh - 200px);
}

@media (max-width: 1024px) {
    .pos-container {
        grid-template-columns: 1fr;
        height: auto;
    }
}

.products-section {
    display: flex;
    flex-direction: column;
    overflow: hidden;
    background: white;
    border-radius: 1rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.search-bar {
    padding: 1rem;
    border-bottom: 2px solid #f3f4f6;
    background: linear-gradient(135deg, <?php echo $settings['primary_color']; ?> 0%, <?php echo $settings['primary_color']; ?>dd 100%);
}

.category-tabs {
    display: flex;
    gap: 0.5rem;
    overflow-x: auto;
    padding: 0.75rem 1rem;
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
}

.category-tab {
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    white-space: nowrap;
    cursor: pointer;
    transition: all 0.2s;
    background: white;
    border: 2px solid #e5e7eb;
    color: #374151;
}

.category-tab.active {
    background: <?php echo $settings['primary_color']; ?>;
    color: white;
    border-color: <?php echo $settings['primary_color']; ?>;
}

.category-tab:hover:not(.active) {
    background: #f3f4f6;
    border-color: <?php echo $settings['primary_color']; ?>80;
}

.products-grid {
    flex: 1;
    overflow-y: auto;
    padding: 1rem;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 0.75rem;
    align-content: start;
}

.product-card {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 0.75rem;
    padding: 0.75rem;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    flex-direction: column;
    height: 100%;
}

.product-card:hover {
    border-color: <?php echo $settings['primary_color']; ?>;
    box-shadow: 0 4px 12px rgba(234, 88, 12, 0.15);
    transform: translateY(-2px);
}

.product-icon {
    width: 3rem;
    height: 3rem;
    background: linear-gradient(135deg, <?php echo $settings['primary_color']; ?> 0%, <?php echo $settings['primary_color']; ?>dd 100%);
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 0.5rem;
}

.product-name {
    font-size: 0.8rem;
    font-weight: 600;
    color: #1f2937;
    line-height: 1.2;
    margin-bottom: 0.25rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-align: center;
    min-height: 2.4rem;
}

.product-price {
    font-size: 1rem;
    font-weight: 700;
    color: <?php echo $settings['primary_color']; ?>;
    text-align: center;
    margin-top: auto;
}

.product-stock {
    font-size: 0.7rem;
    color: #6b7280;
    text-align: center;
    margin-top: 0.25rem;
}

.cart-section {
    display: flex;
    flex-direction: column;
    background: white;
    border-radius: 1rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
}

.cart-header {
    padding: 1.25rem;
    background: linear-gradient(135deg, <?php echo $settings['primary_color']; ?> 0%, <?php echo $settings['primary_color']; ?>dd 100%);
    color: white;
}

.cart-items {
    flex: 1;
    overflow-y: auto;
    padding: 1rem;
    background: #f9fafb;
}

.cart-item {
    background: white;
    border-radius: 0.75rem;
    padding: 1rem;
    margin-bottom: 0.75rem;
    border: 2px solid #e5e7eb;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.cart-summary {
    padding: 1.25rem;
    border-top: 2px solid #e5e7eb;
    background: white;
}

.barcode-scanner {
    padding: 1rem;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    border-bottom: 2px solid #1d4ed8;
    position: relative;
}

.empty-cart {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem 1rem;
    text-align: center;
    color: #9ca3af;
}

.qty-btn {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.5rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
    font-size: 1rem;
}

.qty-btn:active {
    transform: scale(0.95);
}
</style>

<div class="pos-container">
    <!-- Products Section -->
    <div class="products-section">
        <!-- Search Bar -->
        <div class="search-bar">
            <div class="flex gap-3">
                <div class="flex-1 relative">
                    <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-white"></i>
                    <input type="text" id="searchProduct" placeholder="Search products, SKU, or barcode..." 
                           class="w-full pl-10 pr-4 py-3 bg-white/20 text-white placeholder-white/75 rounded-lg focus:ring-2 focus:ring-white/50 focus:bg-white/30 backdrop-blur-sm border-0 outline-none"
                           autofocus>
                </div>
                <button onclick="toggleBarcodeScanner()" id="barcodeScannerBtn" class="px-4 py-3 bg-white/20 hover:bg-white/30 text-white rounded-lg transition backdrop-blur-sm">
                    <i class="fas fa-barcode text-xl"></i>
                </button>
                <button onclick="toggleCamera()" id="cameraBtn" class="px-4 py-3 bg-white/20 hover:bg-white/30 text-white rounded-lg transition backdrop-blur-sm">
                    <i class="fas fa-camera text-xl"></i>
                </button>
            </div>
        </div>

        <!-- Barcode Scanner -->
        <div id="barcodeScanner" class="barcode-scanner hidden">
            <div class="flex gap-3 items-center">
                <input type="text" id="barcodeInput" placeholder="Scan or enter last 3-4 digits..." 
                       class="flex-1 px-4 py-3 border-2 border-white/30 bg-white/20 text-white placeholder-white/75 rounded-lg focus:ring-2 focus:ring-white focus:border-white backdrop-blur-sm">
                <button onclick="searchByBarcode()" class="px-6 py-3 bg-white hover:bg-white/90 text-blue-600 rounded-lg font-semibold transition">
                    <i class="fas fa-search mr-2"></i>Search
                </button>
                <button onclick="toggleBarcodeScanner()" class="px-4 py-3 bg-red-500 hover:bg-red-600 text-white rounded-lg font-semibold transition">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="barcodeSuggestions" class="mt-2"></div>
        </div>

        <!-- Camera Scanner -->
        <div id="cameraScanner" class="hidden p-4 bg-gray-900">
            <video id="cameraVideo" class="w-full rounded-lg mb-2" autoplay></video>
            <button onclick="toggleCamera()" class="w-full px-4 py-3 bg-red-600 hover:bg-red-700 text-white rounded-lg font-semibold">
                <i class="fas fa-times mr-2"></i>Close Camera
            </button>
        </div>

        <!-- Category Tabs -->
        <div class="category-tabs">
            <button class="category-tab active" onclick="filterByCategory('all')" data-category="all">
                <i class="fas fa-th mr-2"></i>All
            </button>
            <?php while ($cat = $categories->fetch_assoc()): ?>
            <button class="category-tab" onclick="filterByCategory('<?php echo $cat['id']; ?>')" data-category="<?php echo $cat['id']; ?>">
                <?php echo htmlspecialchars($cat['name']); ?>
            </button>
            <?php endwhile; ?>
        </div>

        <!-- Products Grid -->
        <div class="products-grid" id="productsGrid">
            <?php while ($product = $products->fetch_assoc()): ?>
            <div class="product-card" 
                 data-id="<?php echo $product['id']; ?>"
                 data-category="<?php echo $product['category_id']; ?>"
                 data-name="<?php echo strtolower($product['name']); ?>"
                 data-barcode="<?php echo strtolower($product['barcode']); ?>"
                 data-sku="<?php echo strtolower($product['sku']); ?>"
                 data-price="<?php echo $product['selling_price']; ?>"
                 data-stock="<?php echo $product['stock_quantity']; ?>"
                 onclick='addToCart(<?php echo json_encode([
                     "id" => $product["id"],
                     "name" => $product["name"],
                     "selling_price" => $product["selling_price"],
                     "stock_quantity" => $product["stock_quantity"]
                 ]); ?>)'>
                <div class="product-icon">
                    <i class="fas fa-wine-bottle text-white text-2xl"></i>
                </div>
                <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                <div class="product-price"><?php echo $settings['currency']; ?> <?php echo number_format($product['selling_price'], 2); ?></div>
                <div class="product-stock"><i class="fas fa-boxes mr-1"></i><?php echo $product['stock_quantity']; ?></div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Cart Section -->
    <div class="cart-section">
        <!-- Cart Header -->
        <div class="cart-header">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold">Current Sale</h2>
                    <p class="text-white/80 text-sm" id="cartItemCount">0 items</p>
                </div>
                <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center backdrop-blur-sm">
                    <i class="fas fa-shopping-cart text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Cart Items -->
        <div class="cart-items" id="cartItems">
            <div class="empty-cart">
                <i class="fas fa-shopping-cart text-6xl mb-3 opacity-30"></i>
                <p class="font-semibold text-lg">Cart is empty</p>
                <p class="text-sm">Start adding products</p>
            </div>
        </div>

        <!-- Cart Summary -->
        <div class="cart-summary">
            <div class="space-y-3">
                <div class="flex justify-between text-sm text-gray-600">
                    <span class="font-medium">Subtotal:</span>
                    <span class="font-bold text-gray-900" id="cartSubtotal"><?php echo $settings['currency']; ?> 0.00</span>
                </div>
                <div class="flex justify-between text-sm text-gray-600">
                    <span class="font-medium">Tax (<span id="taxRate"><?php echo $settings['tax_rate']; ?></span>%):</span>
                    <span class="font-bold text-gray-900" id="cartTax"><?php echo $settings['currency']; ?> 0.00</span>
                </div>
                <div class="flex justify-between text-xl font-bold pt-3 border-t-2 border-gray-200">
                    <span>Total:</span>
                    <span style="color: <?php echo $settings['primary_color']; ?>" id="cartTotal"><?php echo $settings['currency']; ?> 0.00</span>
                </div>
            </div>
            
            <div class="flex gap-3 mt-4">
                <button onclick="clearCart()" class="flex-1 px-4 py-3 bg-red-500 hover:bg-red-600 text-white rounded-lg font-semibold transition">
                    <i class="fas fa-trash mr-2"></i>Clear
                </button>
                <button onclick="showPaymentModal()" id="checkoutBtn" disabled 
                        class="flex-1 px-4 py-3 text-white rounded-lg font-semibold transition disabled:opacity-50 disabled:cursor-not-allowed hover:opacity-90"
                        style="background: linear-gradient(135deg, <?php echo $settings['primary_color']; ?> 0%, <?php echo $settings['primary_color']; ?>dd 100%)">
                    <i class="fas fa-check-circle mr-2"></i>Checkout
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div id="paymentModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4" style="backdrop-filter: blur(4px);">
    <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl">
        <div class="p-6 rounded-t-2xl text-white" style="background: linear-gradient(135deg, <?php echo $settings['primary_color']; ?> 0%, <?php echo $settings['primary_color']; ?>dd 100%)">
            <div class="flex justify-between items-center">
                <div>
                    <h3 class="text-2xl font-bold">Complete Payment</h3>
                    <p class="text-white/80 text-sm">Choose payment method</p>
                </div>
                <button onclick="closePaymentModal()" class="text-white/80 hover:text-white transition">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
        </div>

        <div class="p-6">
            <div class="bg-gray-50 rounded-xl p-4 mb-6">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 font-medium">Total Amount:</span>
                    <span class="text-3xl font-bold" style="color: <?php echo $settings['primary_color']; ?>" id="modalTotal"><?php echo $settings['currency']; ?> 0.00</span>
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-3">Payment Method</label>
                <div class="grid grid-cols-3 gap-3">
                    <button type="button" onclick="selectPaymentMethod('cash')" class="payment-method-btn active px-4 py-4 border-2 rounded-xl hover:border-green-500 transition text-center">
                        <i class="fas fa-money-bill-wave text-3xl mb-2 text-green-600"></i>
                        <div class="text-xs font-semibold">Cash</div>
                    </button>
                    <button type="button" onclick="selectPaymentMethod('mpesa')" class="payment-method-btn px-4 py-4 border-2 rounded-xl hover:border-green-500 transition text-center">
                        <i class="fas fa-mobile-alt text-3xl mb-2 text-green-600"></i>
                        <div class="text-xs font-semibold">M-Pesa</div>
                    </button>
                    <button type="button" onclick="selectPaymentMethod('card')" class="payment-method-btn px-4 py-4 border-2 rounded-xl hover:border-blue-500 transition text-center">
                        <i class="fas fa-credit-card text-3xl mb-2 text-blue-600"></i>
                        <div class="text-xs font-semibold">Card</div>
                    </button>
                </div>
            </div>

            <div id="mpesaRefField" class="mb-4 hidden">
                <label class="block text-sm font-semibold text-gray-700 mb-2">M-Pesa Reference</label>
                <input type="text" id="mpesaReference" class="w-full px-4 py-3 border-2 rounded-xl focus:ring-2 focus:border-green-500" placeholder="e.g., QA12BC34DE">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Amount Paid</label>
                <input type="number" id="amountPaid" class="w-full px-4 py-3 border-2 rounded-xl focus:ring-2 text-lg font-semibold" 
                       style="border-color: <?php echo $settings['primary_color']; ?>33; focus:border-color: <?php echo $settings['primary_color']; ?>" 
                       step="0.01" min="0">
            </div>

            <div id="changeDisplay" class="bg-green-50 border-2 border-green-200 rounded-xl p-4 mb-4 hidden">
                <div class="flex justify-between items-center">
                    <span class="text-gray-700 font-semibold">Change:</span>
                    <span class="text-2xl font-bold text-green-600" id="changeAmount"><?php echo $settings['currency']; ?> 0.00</span>
                </div>
            </div>

            <button onclick="completeSale()" id="completeSaleBtn" class="w-full px-6 py-4 text-white rounded-xl font-bold text-lg transition hover:opacity-90 shadow-lg"
                    style="background: linear-gradient(135deg, <?php echo $settings['primary_color']; ?> 0%, <?php echo $settings['primary_color']; ?>dd 100%)">
                <i class="fas fa-check-circle mr-2"></i>Complete Sale
            </button>
        </div>
    </div>
</div>

<script>
let cart = [];
let selectedPaymentMethod = 'cash';
const settings = <?php echo json_encode($settings); ?>;
let cameraStream = null;

// Add to cart
function addToCart(product) {
    const existingItem = cart.find(item => item.id === product.id);
    
    if (existingItem) {
        if (existingItem.quantity < product.stock_quantity) {
            existingItem.quantity++;
            showNotification(`Added another ${product.name}`, 'success');
        } else {
            showNotification('Insufficient stock', 'error');
            return;
        }
    } else {
        cart.push({
            id: product.id,
            name: product.name,
            price: parseFloat(product.selling_price),
            quantity: 1,
            stock: product.stock_quantity
        });
        showNotification(`Added ${product.name} to cart`, 'success');
    }
    
    updateCart();
}

// Update cart display
function updateCart() {
    const cartItemsDiv = document.getElementById('cartItems');
    const checkoutBtn = document.getElementById('checkoutBtn');
    const cartItemCount = document.getElementById('cartItemCount');
    
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    cartItemCount.textContent = `${totalItems} item${totalItems !== 1 ? 's' : ''}`;
    
    if (cart.length === 0) {
        cartItemsDiv.innerHTML = `
            <div class="empty-cart">
                <i class="fas fa-shopping-cart text-6xl mb-3 opacity-30"></i>
                <p class="font-semibold text-lg">Cart is empty</p>
                <p class="text-sm">Start adding products</p>
            </div>
        `;
        checkoutBtn.disabled = true;
    } else {
        cartItemsDiv.innerHTML = cart.map(item => `
            <div class="cart-item">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center gap-3 flex-1">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0" style="background: linear-gradient(135deg, ${settings.primary_color} 0%, ${settings.primary_color}dd 100%)">
                            <i class="fas fa-wine-bottle text-white"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="font-bold text-sm text-gray-900 mb-1">${item.name}</h4>
                            <p class="text-xs text-gray-600">${settings.currency} ${item.price.toFixed(2)} each</p>
                        </div>
                    </div>
                    <button onclick="removeFromCart(${item.id}); event.stopPropagation();" class="text-red-500 hover:text-red-700 transition ml-2">
                        <i class="fas fa-times-circle text-xl"></i>
                    </button>
                </div>
                <div class="flex items-center justify-between bg-gray-50 rounded-lg p-2">
                    <div class="flex items-center gap-2">
                        <button onclick="updateQuantity(${item.id}, -1); event.stopPropagation();" class="qty-btn bg-red-500 hover:bg-red-600 text-white">
                            <i class="fas fa-minus"></i>
                        </button>
                        <span class="w-14 text-center font-bold text-xl text-gray-900">${item.quantity}</span>
                        <button onclick="updateQuantity(${item.id}, 1); event.stopPropagation();" class="qty-btn bg-green-500 hover:bg-green-600 text-white">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-xl" style="color: ${settings.primary_color}">${settings.currency} ${(item.price * item.quantity).toFixed(2)}</p>
                    </div>
                </div>
            </div>
        `).join('');
        checkoutBtn.disabled = false;
    }
    
    updateTotals();
}

// Update quantity with event stopping
function updateQuantity(productId, change) {
    const item = cart.find(i => i.id === productId);
    if (!item) return;
    
    const newQuantity = item.quantity + change;
    
    if (newQuantity <= 0) {
        removeFromCart(productId);
    } else if (newQuantity <= item.stock) {
        item.quantity = newQuantity;
        updateCart();
    } else {
        showNotification('Insufficient stock', 'error');
    }
}

// Remove from cart
function removeFromCart(productId) {
    cart = cart.filter(item => item.id !== productId);
    updateCart();
    showNotification('Item removed', 'info');
}

// Clear cart
function clearCart() {
    if (cart.length === 0) return;
    if (confirm('Clear all items from cart?')) {
        cart = [];
        updateCart();
        showNotification('Cart cleared', 'info');
    }
}

// Update totals
function updateTotals() {
    const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const taxRate = parseFloat(settings.tax_rate) || 0;
    const tax = subtotal * (taxRate / 100);
    const total = subtotal + tax;
    
    document.getElementById('cartSubtotal').textContent = `${settings.currency} ${subtotal.toFixed(2)}`;
    document.getElementById('cartTax').textContent = `${settings.currency} ${tax.toFixed(2)}`;
    document.getElementById('cartTotal').textContent = `${settings.currency} ${total.toFixed(2)}`;
}

// Filter by category
function filterByCategory(categoryId) {
    const products = document.querySelectorAll('.product-card');
    const buttons = document.querySelectorAll('.category-tab');
    
    buttons.forEach(btn => btn.classList.remove('active'));
    document.querySelector(`[data-category="${categoryId}"]`).classList.add('active');
    
    products.forEach(product => {
        if (categoryId === 'all' || product.dataset.category === categoryId) {
            product.style.display = 'flex';
        } else {
            product.style.display = 'none';
        }
    });
}

// Fast search
document.getElementById('searchProduct').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase().trim();
    const products = document.querySelectorAll('.product-card');
    
    if (searchTerm === '') {
        products.forEach(p => p.style.display = 'flex');
        return;
    }
    
    products.forEach(product => {
        const productName = product.dataset.name;
        const barcode = product.dataset.barcode;
        const sku = product.dataset.sku;
        
        if (productName.includes(searchTerm) || 
            barcode.includes(searchTerm) || 
            sku.includes(searchTerm)) {
            product.style.display = 'flex';
        } else {
            product.style.display = 'none';
        }
    });
});

// Barcode scanner
function toggleBarcodeScanner() {
    const scanner = document.getElementById('barcodeScanner');
    const isHidden = scanner.classList.contains('hidden');
    
    if (isHidden) {
        scanner.classList.remove('hidden');
        document.getElementById('barcodeInput').focus();
    } else {
        scanner.classList.add('hidden');
        document.getElementById('barcodeInput').value = '';
        document.getElementById('barcodeSuggestions').innerHTML = '';
    }
}

function searchByBarcode() {
    const input = document.getElementById('barcodeInput').value.trim().toLowerCase();
    if (input.length < 3) {
        showNotification('Enter at least 3 characters', 'error');
        return;
    }
    
    const products = document.querySelectorAll('.product-card');
    const matches = [];
    
    products.forEach(product => {
        const barcode = product.dataset.barcode;
        const sku = product.dataset.sku;
        const name = product.dataset.name;
        if (barcode.includes(input) || sku.includes(input) || name.includes(input)) {
            matches.push(product);
        }
    });
    
    if (matches.length === 0) {
        showNotification('No products found', 'error');
    } else if (matches.length === 1) {
        const product = matches[0];
        addToCart({
            id: parseInt(product.dataset.id),
            name: product.querySelector('.product-name').textContent,
            selling_price: product.dataset.price,
            stock_quantity: parseInt(product.dataset.stock)
        });
        document.getElementById('barcodeInput').value = '';
        document.getElementById('barcodeSuggestions').innerHTML = '';
    } else {
        showBarcodeSuggestions(matches);
    }
}

function showBarcodeSuggestions(products) {
    const container = document.getElementById('barcodeSuggestions');
    container.innerHTML = `
        <div class="bg-white rounded-lg border-2 border-white/30 p-2 max-h-60 overflow-y-auto backdrop-blur-sm">
            <p class="text-sm font-semibold text-gray-900 mb-2 px-2">Found ${products.length} products:</p>
            ${Array.from(products).map(p => {
                const productData = {
                    id: parseInt(p.dataset.id),
                    name: p.querySelector('.product-name').textContent,
                    selling_price: p.dataset.price,
                    stock_quantity: parseInt(p.dataset.stock)
                };
                return `
                    <button onclick='addToCart(${JSON.stringify(productData)}); document.getElementById("barcodeSuggestions").innerHTML = ""; document.getElementById("barcodeInput").value = "";' 
                            class="w-full text-left p-3 hover:bg-blue-50 rounded text-sm font-medium text-gray-900 transition">
                        ${p.querySelector('.product-name').textContent}
                    </button>
                `;
            }).join('')}
        </div>
    `;
}

// Camera scanner
function toggleCamera() {
    const cameraDiv = document.getElementById('cameraScanner');
    const video = document.getElementById('cameraVideo');
    
    if (cameraDiv.classList.contains('hidden')) {
        cameraDiv.classList.remove('hidden');
        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
            .then(stream => {
                cameraStream = stream;
                video.srcObject = stream;
            })
            .catch(err => {
                showNotification('Camera access denied', 'error');
                cameraDiv.classList.add('hidden');
            });
    } else {
        if (cameraStream) {
            cameraStream.getTracks().forEach(track => track.stop());
            cameraStream = null;
        }
        video.srcObject = null;
        cameraDiv.classList.add('hidden');
    }
}

// Payment modal
function showPaymentModal() {
    if (cart.length === 0) return;
    
    const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const taxRate = parseFloat(settings.tax_rate) || 0;
    const tax = subtotal * (taxRate / 100);
    const total = subtotal + tax;
    
    document.getElementById('modalTotal').textContent = `${settings.currency} ${total.toFixed(2)}`;
    document.getElementById('amountPaid').value = total.toFixed(2);
    document.getElementById('paymentModal').classList.remove('hidden');
    document.getElementById('paymentModal').classList.add('flex');
    
    selectPaymentMethod('cash');
    calculateChange();
}

function closePaymentModal() {
    document.getElementById('paymentModal').classList.add('hidden');
    document.getElementById('paymentModal').classList.remove('flex');
}

function selectPaymentMethod(method) {
    selectedPaymentMethod = method;
    
    const buttons = document.querySelectorAll('.payment-method-btn');
    buttons.forEach(btn => {
        btn.classList.remove('active');
        btn.style.borderColor = '';
        btn.style.backgroundColor = '';
    });
    
    event.target.closest('.payment-method-btn').classList.add('active');
    event.target.closest('.payment-method-btn').style.borderColor = settings.primary_color;
    event.target.closest('.payment-method-btn').style.backgroundColor = settings.primary_color + '11';
    
    if (method === 'mpesa') {
        document.getElementById('mpesaRefField').classList.remove('hidden');
    } else {
        document.getElementById('mpesaRefField').classList.add('hidden');
    }
}

document.getElementById('amountPaid').addEventListener('input', calculateChange);

function calculateChange() {
    const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const taxRate = parseFloat(settings.tax_rate) || 0;
    const tax = subtotal * (taxRate / 100);
    const total = subtotal + tax;
    
    const amountPaid = parseFloat(document.getElementById('amountPaid').value) || 0;
    const change = amountPaid - total;
    
    const changeDisplay = document.getElementById('changeDisplay');
    const changeAmount = document.getElementById('changeAmount');
    
    if (change >= 0 && amountPaid > 0) {
        changeAmount.textContent = `${settings.currency} ${change.toFixed(2)}`;
        changeDisplay.classList.remove('hidden');
    } else {
        changeDisplay.classList.add('hidden');
    }
}

// Complete sale
function completeSale() {
    if (cart.length === 0) {
        showNotification('Cart is empty', 'error');
        return;
    }
    
    const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const taxRate = parseFloat(settings.tax_rate) || 0;
    const tax = subtotal * (taxRate / 100);
    const total = subtotal + tax;
    const amountPaid = parseFloat(document.getElementById('amountPaid').value) || 0;
    
    if (amountPaid < total) {
        showNotification('Insufficient payment amount', 'error');
        return;
    }
    
    if (selectedPaymentMethod === 'mpesa' && !document.getElementById('mpesaReference').value) {
        showNotification('Please enter M-Pesa reference', 'error');
        return;
    }
    
    const btn = document.getElementById('completeSaleBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
    
    const formData = new FormData();
    formData.append('action', 'complete_sale');
    formData.append('items', JSON.stringify(cart));
    formData.append('subtotal', subtotal);
    formData.append('tax_amount', tax);
    formData.append('total_amount', total);
    formData.append('payment_method', selectedPaymentMethod);
    formData.append('amount_paid', amountPaid);
    formData.append('change_amount', amountPaid - total);
    
    if (selectedPaymentMethod === 'mpesa') {
        formData.append('mpesa_reference', document.getElementById('mpesaReference').value);
    }
    
    fetch('/api/complete-sale.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showNotification('Sale completed successfully!', 'success');
            cart = [];
            updateCart();
            closePaymentModal();
            
            if (confirm(`Sale completed!\n\nSale #${data.data.sale_number}\nTotal: ${settings.currency} ${data.data.total.toFixed(2)}\n\nPrint receipt?`)) {
                window.open(`/receipt.php?id=${data.data.sale_id}`, '_blank');
            }
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(err => {
        showNotification('Connection error. Please try again.', 'error');
        console.error(err);
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check-circle mr-2"></i>Complete Sale';
    });
}

// Notification
function showNotification(message, type = 'info') {
    const colors = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        info: 'bg-blue-500'
    };
    
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 ${colors[type]} text-white px-6 py-3 rounded-lg shadow-lg z-50 transform transition-all duration-300`;
    notification.style.animation = 'slideIn 0.3s ease-out';
    notification.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'} mr-2"></i>${message}`;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease-out';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if (e.key === 'F2') {
        e.preventDefault();
        document.getElementById('searchProduct').focus();
    }
    
    if (e.key === 'F4' && cart.length > 0) {
        e.preventDefault();
        showPaymentModal();
    }
    
    if (e.key === 'Escape') {
        closePaymentModal();
        const scanner = document.getElementById('barcodeScanner');
        if (!scanner.classList.contains('hidden')) {
            toggleBarcodeScanner();
        }
        const camera = document.getElementById('cameraScanner');
        if (!camera.classList.contains('hidden')) {
            toggleCamera();
        }
    }
    
    if (e.key === 'F3') {
        e.preventDefault();
        toggleBarcodeScanner();
    }
});

// Enter key on barcode input
document.getElementById('barcodeInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        searchByBarcode();
    }
});
</script>

<style>
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

@keyframes slideOut {
    from {
        transform: translateX(0);
        opacity: 1;
    }
    to {
        transform: translateX(100%);
        opacity: 0;
    }
}

.payment-method-btn.active {
    border-color: <?php echo $settings['primary_color']; ?>;
    background-color: <?php echo $settings['primary_color']; ?>11;
}
</style>

<?php include 'footer.php'; ?>