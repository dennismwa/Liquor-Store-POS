<?php
require_once 'config.php';
requireAuth();

$page_title = 'Point of Sale';
$settings = getSettings();
$isOwner = $_SESSION['role'] === 'owner';

// Get categories ordered by total sales (best selling first)
$categories = $conn->query("SELECT c.*, 
                            COALESCE(SUM(si.quantity), 0) as total_sold,
                            COUNT(DISTINCT s.id) as sales_count
                            FROM categories c
                            LEFT JOIN products p ON c.id = p.category_id
                            LEFT JOIN sale_items si ON p.id = si.product_id
                            LEFT JOIN sales s ON si.sale_id = s.id
                            WHERE c.status = 'active'
                            GROUP BY c.id
                            ORDER BY total_sold DESC, sales_count DESC, c.name ASC");

// Get products ordered by sales within each category
$products = $conn->query("SELECT p.*, 
                         c.name as category_name,
                         COALESCE(SUM(si.quantity), 0) as total_sold
                         FROM products p 
                         LEFT JOIN categories c ON p.category_id = c.id
                         LEFT JOIN sale_items si ON p.id = si.product_id
                         WHERE p.status = 'active' AND p.stock_quantity > 0
                         GROUP BY p.id
                         ORDER BY total_sold DESC, p.name ASC");

// Check if seller should default to fullscreen
$defaultFullscreen = !$isOwner;

include 'header.php';
?>

<style>
/* Fullscreen POS Mode */
.pos-fullscreen {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 100;
    background: white;
    overflow: hidden;
}

.pos-fullscreen .pos-container {
    height: 100vh;
    padding: 0;
    margin: 0;
    gap: 0;
}

.pos-container {
    display: grid;
    grid-template-columns: 1fr 450px;
    gap: 0;
    height: calc(100vh - 140px);
}

@media (max-width: 1280px) {
    .pos-container {
        grid-template-columns: 1fr 400px;
    }
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
    border-right: 2px solid #e5e7eb;
}

.pos-fullscreen .products-section {
    border-right: 2px solid #e5e7eb;
}

.search-bar {
    padding: 1rem;
    border-bottom: 2px solid #f3f4f6;
    background: linear-gradient(135deg, <?php echo $settings['primary_color']; ?> 0%, <?php echo $settings['primary_color']; ?>dd 100%);
    flex-shrink: 0;
}

.category-tabs {
    display: flex;
    gap: 0.5rem;
    overflow-x: auto;
    padding: 0.75rem 1rem;
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
    flex-shrink: 0;
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

.cart-section {
    display: flex;
    flex-direction: column;
    background: white;
    overflow: hidden;
    height: 100%;
}

.cart-header {
    padding: 1.25rem;
    background: linear-gradient(135deg, <?php echo $settings['primary_color']; ?> 0%, <?php echo $settings['primary_color']; ?>dd 100%);
    color: white;
    flex-shrink: 0;
}

.cart-items {
    flex: 1;
    overflow-y: auto;
    padding: 1rem;
    background: #f9fafb;
    min-height: 0;
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
    flex-shrink: 0;
}

/* Fullscreen Toggle Button */
.fullscreen-toggle {
    position: fixed;
    bottom: 2rem;
    left: 50%;
    transform: translateX(-50%);
    z-index: 101;
    padding: 0.75rem 1.5rem;
    background: rgba(0, 0, 0, 0.8);
    color: white;
    border-radius: 2rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    backdrop-filter: blur(10px);
    display: none; /* Hidden as now in header */
}

.fullscreen-toggle:hover {
    background: rgba(0, 0, 0, 0.95);
    transform: translateX(-50%) translateY(-2px);
}

.pos-fullscreen .fullscreen-toggle {
    bottom: 1rem;
    display: none; /* Keep hidden */
}

/* Draft Orders Badge */
.draft-badge {
    position: fixed;
    top: 1rem;
    right: 1rem;
    z-index: 102;
    padding: 0.75rem 1.5rem;
    background: #f59e0b;
    color: white;
    border-radius: 2rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
}

.draft-badge:hover {
    background: #d97706;
    transform: translateY(-2px);
}

@media (max-width: 768px) {
    .pos-container {
        grid-template-columns: 1fr;
    }
    
    .cart-section {
        max-height: 60vh;
    }
    
    .fullscreen-toggle {
        bottom: 5rem;
    }
}
</style>

<div id="posContainer" class="<?php echo $defaultFullscreen ? 'pos-fullscreen' : ''; ?>">
    <!-- Fullscreen Toggle Button (Removed - Now in header) -->

    <!-- Draft Orders Badge -->
    <div id="draftBadge" class="draft-badge no-print hidden" onclick="showDraftOrders()">
        <i class="fas fa-file-invoice mr-2"></i>
        <span id="draftCount">0</span> Drafts
    </div>

    <div class="pos-container">
        <!-- Products Section -->
        <div class="products-section">
            <!-- Search Bar -->
            <div class="search-bar">
                <div class="flex gap-3 items-center">
                    <div class="flex-1 relative">
                        <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-white"></i>
                        <input type="text" id="searchProduct" placeholder="Search products, SKU, or barcode..." 
                               class="w-full pl-10 pr-4 py-3 bg-white/20 text-white placeholder-white/75 rounded-lg focus:ring-2 focus:ring-white/50 focus:bg-white/30 backdrop-blur-sm border-0 outline-none"
                               autofocus>
                    </div>
                    <button onclick="toggleBarcodeScanner()" id="barcodeScannerBtn" class="px-4 py-3 bg-white/20 hover:bg-white/30 text-white rounded-lg transition backdrop-blur-sm" title="Barcode Scanner">
                        <i class="fas fa-barcode text-xl"></i>
                    </button>
                    <button onclick="toggleFullscreen()" class="px-4 py-3 bg-white/20 hover:bg-white/30 text-white rounded-lg transition backdrop-blur-sm" title="Toggle Fullscreen">
                        <i class="fas fa-expand text-xl" id="fullscreenIconTop"></i>
                    </button>
                </div>
            </div>

            <!-- Barcode Scanner -->
            <div id="barcodeScanner" class="hidden p-4" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">
                <div class="flex gap-3 items-center">
                    <input type="text" id="barcodeInput" placeholder="Scan or enter barcode..." 
                           class="flex-1 px-4 py-3 border-2 border-white/30 bg-white/20 text-white placeholder-white/75 rounded-lg focus:ring-2 focus:ring-white focus:border-white backdrop-blur-sm">
                    <button onclick="searchByBarcode()" class="px-6 py-3 bg-white hover:bg-white/90 text-blue-600 rounded-lg font-semibold transition">
                        <i class="fas fa-search mr-2"></i>Search
                    </button>
                    <button onclick="toggleBarcodeScanner()" class="px-4 py-3 bg-red-500 hover:bg-red-600 text-white rounded-lg font-semibold transition">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <!-- Category Tabs -->
            <div class="category-tabs">
                <button class="category-tab active" onclick="filterByCategory('all')" data-category="all">
                    <i class="fas fa-th mr-2"></i>All
                </button>
                <?php while ($cat = $categories->fetch_assoc()): ?>
                <button class="category-tab" onclick="filterByCategory('<?php echo $cat['id']; ?>')" data-category="<?php echo $cat['id']; ?>" title="<?php echo $cat['total_sold']; ?> items sold">
                    <?php echo htmlspecialchars($cat['name']); ?>
                    <?php if ($cat['total_sold'] > 0): ?>
                    <span class="ml-1 px-1.5 py-0.5 bg-white/20 rounded text-xs"><?php echo $cat['total_sold']; ?></span>
                    <?php endif; ?>
                </button>
                <?php endwhile; ?>
            </div>

            <!-- Products Grid -->
            <div class="products-grid" id="productsGrid">
                <?php while ($product = $products->fetch_assoc()): 
                    $stockStatus = getStockStatus($product['stock_quantity'], $product['reorder_level']);
                ?>
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
                    <div class="text-center">
                        <div class="font-semibold text-sm text-gray-900 mb-2 line-clamp-2" style="min-height: 2.5rem;">
                            <?php echo htmlspecialchars($product['name']); ?>
                        </div>
                        <div class="font-bold text-lg mb-1" style="color: <?php echo $settings['primary_color']; ?>">
                            <?php echo $settings['currency']; ?> <?php echo number_format($product['selling_price'], 2); ?>
                        </div>
                        <div class="text-xs px-2 py-1 rounded-full <?php echo $stockStatus['color'] === 'green' ? 'bg-green-100 text-green-800' : ($stockStatus['color'] === 'orange' ? 'bg-orange-100 text-orange-800' : 'bg-red-100 text-red-800'); ?>">
                            <i class="fas fa-boxes mr-1"></i><?php echo $product['stock_quantity']; ?>
                        </div>
                    </div>
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
                <div class="flex flex-col items-center justify-center py-12">
                    <i class="fas fa-shopping-cart text-6xl text-gray-300 mb-3 opacity-30"></i>
                    <p class="font-semibold text-lg text-gray-500">Cart is empty</p>
                    <p class="text-sm text-gray-400">Start adding products</p>
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
                
                <div class="grid grid-cols-2 gap-3 mt-4">
                    <button onclick="saveDraft()" class="px-4 py-3 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg font-semibold transition">
                        <i class="fas fa-save mr-2"></i>Save Draft
                    </button>
                    <button onclick="clearCart()" class="px-4 py-3 bg-red-500 hover:bg-red-600 text-white rounded-lg font-semibold transition">
                        <i class="fas fa-trash mr-2"></i>Clear
                    </button>
                </div>
                
                <button onclick="showPaymentModal()" id="checkoutBtn" disabled 
                        class="w-full mt-3 px-4 py-3 text-white rounded-lg font-semibold transition disabled:opacity-50 disabled:cursor-not-allowed hover:opacity-90"
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
                    <button type="button" onclick="selectPaymentMethod('mpesa_till')" class="payment-method-btn px-4 py-4 border-2 rounded-xl hover:border-green-500 transition text-center">
                        <i class="fas fa-store text-3xl mb-2 text-blue-600"></i>
                        <div class="text-xs font-semibold">Till No.</div>
                    </button>
                </div>
            </div>

            <div id="mpesaRefField" class="mb-4 hidden">
                <label class="block text-sm font-semibold text-gray-700 mb-2">M-Pesa Reference</label>
                <input type="text" id="mpesaReference" class="w-full px-4 py-3 border-2 rounded-xl focus:ring-2 focus:border-green-500" placeholder="e.g., QA12BC34DE">
            </div>

            <div id="tillNumberField" class="mb-4 hidden">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Till Number</label>
                <input type="text" id="tillNumber" class="w-full px-4 py-3 border-2 rounded-xl focus:ring-2 focus:border-blue-500" placeholder="e.g., 123456">
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

<!-- Draft Orders Modal -->
<div id="draftModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4" style="backdrop-filter: blur(4px);">
    <div class="bg-white rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto shadow-2xl">
        <div class="sticky top-0 bg-white p-6 border-b flex items-center justify-between z-10">
            <h3 class="text-2xl font-bold text-gray-900">Draft Orders</h3>
            <button onclick="closeDraftModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>
        
        <div id="draftList" class="p-6"></div>
    </div>
</div>

<script>
let cart = [];
let selectedPaymentMethod = 'cash';
let draftOrders = JSON.parse(localStorage.getItem('draftOrders') || '[]');
let currentDraftId = null;
const settings = <?php echo json_encode($settings); ?>;
const isFullscreen = <?php echo $defaultFullscreen ? 'true' : 'false'; ?>;

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    if (isFullscreen) {
        document.getElementById('posContainer').classList.add('pos-fullscreen');
        document.getElementById('fullscreenIconTop').className = 'fas fa-compress text-xl';
    }
    updateDraftBadge();
});

function toggleFullscreen() {
    const container = document.getElementById('posContainer');
    const iconTop = document.getElementById('fullscreenIconTop');
    
    container.classList.toggle('pos-fullscreen');
    
    if (container.classList.contains('pos-fullscreen')) {
        iconTop.className = 'fas fa-compress text-xl';
    } else {
        iconTop.className = 'fas fa-expand text-xl';
    }
}

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

function updateCart() {
    const cartItemsDiv = document.getElementById('cartItems');
    const checkoutBtn = document.getElementById('checkoutBtn');
    const cartItemCount = document.getElementById('cartItemCount');
    
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    cartItemCount.textContent = `${totalItems} item${totalItems !== 1 ? 's' : ''}`;
    
    if (cart.length === 0) {
        cartItemsDiv.innerHTML = `
            <div class="flex flex-col items-center justify-center py-12">
                <i class="fas fa-shopping-cart text-6xl text-gray-300 mb-3 opacity-30"></i>
                <p class="font-semibold text-lg text-gray-500">Cart is empty</p>
                <p class="text-sm text-gray-400">Start adding products</p>
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
                        <button onclick="updateQuantity(${item.id}, -1); event.stopPropagation();" class="w-10 h-10 bg-red-500 hover:bg-red-600 text-white rounded-lg font-bold transition">
                            <i class="fas fa-minus"></i>
                        </button>
                        <span class="w-14 text-center font-bold text-xl text-gray-900">${item.quantity}</span>
                        <button onclick="updateQuantity(${item.id}, 1); event.stopPropagation();" class="w-10 h-10 bg-green-500 hover:bg-green-600 text-white rounded-lg font-bold transition">
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

function removeFromCart(productId) {
    cart = cart.filter(item => item.id !== productId);
    updateCart();
    showNotification('Item removed', 'info');
}

function clearCart() {
    if (cart.length === 0) return;
    if (confirm('Clear all items from cart?')) {
        cart = [];
        updateCart();
        showNotification('Cart cleared', 'info');
    }
}

function updateTotals() {
    const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const taxRate = parseFloat(settings.tax_rate) || 0;
    const tax = subtotal * (taxRate / 100);
    const total = subtotal + tax;
    
    document.getElementById('cartSubtotal').textContent = `${settings.currency} ${subtotal.toFixed(2)}`;
    document.getElementById('cartTax').textContent = `${settings.currency} ${tax.toFixed(2)}`;
    document.getElementById('cartTotal').textContent = `${settings.currency} ${total.toFixed(2)}`;
}

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

// Search functionality
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

function toggleBarcodeScanner() {
    const scanner = document.getElementById('barcodeScanner');
    scanner.classList.toggle('hidden');
    if (!scanner.classList.contains('hidden')) {
        document.getElementById('barcodeInput').focus();
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
            name: product.querySelector('.font-semibold').textContent.trim(),
            selling_price: product.dataset.price,
            stock_quantity: parseInt(product.dataset.stock)
        });
        document.getElementById('barcodeInput').value = '';
        toggleBarcodeScanner();
    } else {
        showNotification(`Found ${matches.length} products. Please refine search.`, 'info');
    }
}

// Draft Orders
function saveDraft() {
    if (cart.length === 0) {
        showNotification('Cart is empty', 'error');
        return;
    }
    
    const draftName = prompt('Enter draft name (optional):') || `Draft ${new Date().toLocaleString()}`;
    
    const draft = {
        id: Date.now(),
        name: draftName,
        items: [...cart],
        timestamp: new Date().toISOString()
    };
    
    draftOrders.push(draft);
    localStorage.setItem('draftOrders', JSON.stringify(draftOrders));
    
    cart = [];
    updateCart();
    updateDraftBadge();
    showNotification('Draft saved successfully', 'success');
}

function updateDraftBadge() {
    const badge = document.getElementById('draftBadge');
    const count = document.getElementById('draftCount');
    
    if (draftOrders.length > 0) {
        badge.classList.remove('hidden');
        count.textContent = draftOrders.length;
    } else {
        badge.classList.add('hidden');
    }
}

function showDraftOrders() {
    const modal = document.getElementById('draftModal');
    const list = document.getElementById('draftList');
    
    if (draftOrders.length === 0) {
        list.innerHTML = `
            <div class="text-center py-12">
                <i class="fas fa-file-invoice text-6xl text-gray-300 mb-4"></i>
                <p class="text-xl text-gray-500 font-semibold">No draft orders</p>
            </div>
        `;
    } else {
        list.innerHTML = draftOrders.map(draft => `
            <div class="bg-gray-50 rounded-xl p-4 mb-4">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h4 class="font-bold text-gray-900">${draft.name}</h4>
                        <p class="text-sm text-gray-500">${new Date(draft.timestamp).toLocaleString()}</p>
                        <p class="text-sm font-semibold text-blue-600 mt-1">${draft.items.length} items</p>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="loadDraft(${draft.id})" class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg font-semibold transition">
                            <i class="fas fa-upload mr-2"></i>Load
                        </button>
                        <button onclick="if(confirm('Delete this draft?')) deleteDraft(${draft.id})" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg font-semibold transition">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <div class="space-y-1">
                    ${draft.items.map(item => `
                        <div class="text-sm text-gray-700 flex justify-between">
                            <span>${item.name} x${item.quantity}</span>
                            <span class="font-semibold">${settings.currency} ${(item.price * item.quantity).toFixed(2)}</span>
                        </div>
                    `).join('')}
                </div>
            </div>
        `).join('');
    }
    
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeDraftModal() {
    document.getElementById('draftModal').classList.add('hidden');
    document.getElementById('draftModal').classList.remove('flex');
}

function loadDraft(draftId) {
    const draft = draftOrders.find(d => d.id === draftId);
    if (!draft) return;
    
    currentDraftId = draftId;
    cart = [...draft.items];
    updateCart();
    closeDraftModal();
    showNotification('Draft loaded successfully', 'success');
}

function deleteDraft(draftId, showMessage = true) {
    draftOrders = draftOrders.filter(d => d.id !== draftId);
    localStorage.setItem('draftOrders', JSON.stringify(draftOrders));
    updateDraftBadge();
    if (showMessage) {
        showDraftOrders();
        showNotification('Draft deleted', 'info');
    }
}

// Payment
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
    
    document.getElementById('mpesaRefField').classList.add('hidden');
    document.getElementById('tillNumberField').classList.add('hidden');
    
    if (method === 'mpesa') {
        document.getElementById('mpesaRefField').classList.remove('hidden');
    } else if (method === 'mpesa_till') {
        document.getElementById('tillNumberField').classList.remove('hidden');
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
    
    if (selectedPaymentMethod === 'mpesa_till' && !document.getElementById('tillNumber').value) {
        showNotification('Please enter Till Number', 'error');
        return;
    }
    
    const btn = document.getElementById('completeSaleBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
    
    const formData = new FormData();
    formData.append('items', JSON.stringify(cart));
    formData.append('subtotal', subtotal.toFixed(2));
    formData.append('tax_amount', tax.toFixed(2));
    formData.append('total_amount', total.toFixed(2));
    formData.append('payment_method', selectedPaymentMethod);
    formData.append('amount_paid', amountPaid.toFixed(2));
    formData.append('change_amount', (amountPaid - total).toFixed(2));
    
    if (selectedPaymentMethod === 'mpesa') {
        formData.append('mpesa_reference', document.getElementById('mpesaReference').value);
    } else if (selectedPaymentMethod === 'mpesa_till') {
        formData.append('mpesa_reference', 'TILL-' + document.getElementById('tillNumber').value);
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
            
            // Remove from drafts if it was loaded from one
            if (currentDraftId) {
                deleteDraft(currentDraftId, false);
                currentDraftId = null;
            }
            
            if (confirm(`Sale completed!\n\nSale #${data.data.sale_number}\nTotal: ${settings.currency} ${data.data.total.toFixed(2)}\n\nPrint receipt?`)) {
                window.open(`/receipt.php?id=${data.data.sale_id}`, '_blank');
            }
        } else {
            showNotification(data.message || 'Failed to complete sale', 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check-circle mr-2"></i>Complete Sale';
        }
    })
    .catch(err => {
        showNotification('Connection error. Please try again.', 'error');
        console.error(err);
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check-circle mr-2"></i>Complete Sale';
    });
}

function showNotification(message, type = 'info') {
    const colors = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        info: 'bg-blue-500'
    };
    
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 ${colors[type]} text-white px-6 py-3 rounded-lg shadow-lg z-[200] transform transition-all duration-300`;
    notification.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'} mr-2"></i>${message}`;
    
    document.body.appendChild(notification);
    
    setTimeout(() => notification.remove(), 3000);
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
        closeDraftModal();
        if (!document.getElementById('barcodeScanner').classList.contains('hidden')) {
            toggleBarcodeScanner();
        }
    }
    
    if (e.key === 'F3') {
        e.preventDefault();
        toggleBarcodeScanner();
    }
    
    if (e.key === 'F11') {
        e.preventDefault();
        toggleFullscreen();
    }
});

document.getElementById('barcodeInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        searchByBarcode();
    }
});
</script>

<?php include 'footer.php'; ?>
