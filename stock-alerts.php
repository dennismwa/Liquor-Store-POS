<?php
require_once 'config.php';
requireOwner();

$page_title = 'Stock Alerts';
$settings = getSettings();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'save_product_alert') {
        $productId = intval($_POST['product_id']);
        $alertLevel = intval($_POST['alert_level']);
        
        $stmt = $conn->prepare("UPDATE products SET reorder_level=? WHERE id=?");
        $stmt->bind_param("ii", $alertLevel, $productId);
        
        if ($stmt->execute()) {
            logActivity('ALERT_UPDATED', "Updated alert level for product ID: $productId to $alertLevel");
            respond(true, 'Alert level updated successfully');
        } else {
            respond(false, 'Failed to update alert level');
        }
    }
    
    if ($_POST['action'] === 'save_category_alert') {
        $categoryId = intval($_POST['category_id']);
        $alertLevel = intval($_POST['alert_level']);
        
        $stmt = $conn->prepare("UPDATE products SET reorder_level=? WHERE category_id=?");
        $stmt->bind_param("ii", $alertLevel, $categoryId);
        
        if ($stmt->execute()) {
            logActivity('ALERT_UPDATED', "Updated alert level for category ID: $categoryId to $alertLevel");
            respond(true, 'Category alert level updated successfully');
        } else {
            respond(false, 'Failed to update category alert level');
        }
    }
    
    exit;
}

// Get alert statistics
$criticalAlerts = $conn->query("SELECT COUNT(*) as count FROM products WHERE stock_quantity = 0 AND status = 'active'")->fetch_assoc()['count'];
$warningAlerts = $conn->query("SELECT COUNT(*) as count FROM products WHERE stock_quantity > 0 AND stock_quantity <= reorder_level AND status = 'active'")->fetch_assoc()['count'];
$totalProducts = $conn->query("SELECT COUNT(*) as count FROM products WHERE status = 'active'")->fetch_assoc()['count'];

// Get products with alerts
$alertProducts = $conn->query("SELECT p.*, c.name as category_name 
                               FROM products p 
                               LEFT JOIN categories c ON p.category_id = c.id 
                               WHERE p.stock_quantity <= p.reorder_level 
                               AND p.status = 'active' 
                               ORDER BY p.stock_quantity ASC, p.name ASC");

// Get categories with alert counts
$categoryAlerts = $conn->query("SELECT c.id, c.name, 
                                COUNT(CASE WHEN p.stock_quantity <= p.reorder_level THEN 1 END) as alert_count,
                                COUNT(p.id) as total_products,
                                AVG(p.reorder_level) as avg_reorder_level
                                FROM categories c
                                LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active'
                                WHERE c.status = 'active'
                                GROUP BY c.id
                                ORDER BY alert_count DESC, c.name ASC");

include 'header.php';
?>

<style>
.alert-card {
    background: white;
    border-radius: 1rem;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: all 0.3s;
    cursor: pointer;
}

.alert-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(0,0,0,0.15);
}

.pulse-animation {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: .5;
    }
}

.progress-ring {
    transform: rotate(-90deg);
}

.progress-ring-circle {
    transition: stroke-dashoffset 0.35s;
    transform-origin: 50% 50%;
}
</style>

<!-- Alert Statistics -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-6">
    <div class="alert-card">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600 mb-1 font-medium">Critical Alerts</p>
                <h3 class="text-4xl font-bold text-red-600"><?php echo $criticalAlerts; ?></h3>
                <p class="text-xs text-gray-500 mt-1">Out of stock</p>
            </div>
            <div class="w-14 h-14 bg-red-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-exclamation-triangle text-red-600 text-2xl pulse-animation"></i>
            </div>
        </div>
    </div>
    
    <div class="alert-card">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600 mb-1 font-medium">Warning Alerts</p>
                <h3 class="text-4xl font-bold text-orange-600"><?php echo $warningAlerts; ?></h3>
                <p class="text-xs text-gray-500 mt-1">Low stock</p>
            </div>
            <div class="w-14 h-14 bg-orange-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-exclamation-circle text-orange-600 text-2xl"></i>
            </div>
        </div>
    </div>
    
    <div class="alert-card">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600 mb-1 font-medium">Total Products</p>
                <h3 class="text-4xl font-bold text-blue-600"><?php echo $totalProducts; ?></h3>
                <p class="text-xs text-gray-500 mt-1">Active inventory</p>
            </div>
            <div class="w-14 h-14 bg-blue-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-boxes text-blue-600 text-2xl"></i>
            </div>
        </div>
    </div>
    
    <div class="alert-card">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600 mb-1 font-medium">Alert Rate</p>
                <?php $alertRate = $totalProducts > 0 ? (($criticalAlerts + $warningAlerts) / $totalProducts) * 100 : 0; ?>
                <h3 class="text-4xl font-bold <?php echo $alertRate > 50 ? 'text-red-600' : ($alertRate > 25 ? 'text-orange-600' : 'text-green-600'); ?>">
                    <?php echo round($alertRate); ?>%
                </h3>
                <p class="text-xs text-gray-500 mt-1">Products with alerts</p>
            </div>
            <div class="w-14 h-14 <?php echo $alertRate > 50 ? 'bg-red-100' : ($alertRate > 25 ? 'bg-orange-100' : 'bg-green-100'); ?> rounded-xl flex items-center justify-center">
                <svg class="w-8 h-8">
                    <circle cx="16" cy="16" r="14" fill="none" stroke="currentColor" stroke-width="3" 
                            class="<?php echo $alertRate > 50 ? 'text-red-600' : ($alertRate > 25 ? 'text-orange-600' : 'text-green-600'); ?>" 
                            opacity="0.2"/>
                    <circle cx="16" cy="16" r="14" fill="none" stroke="currentColor" stroke-width="3" 
                            class="progress-ring-circle <?php echo $alertRate > 50 ? 'text-red-600' : ($alertRate > 25 ? 'text-orange-600' : 'text-green-600'); ?>" 
                            style="stroke-dasharray: <?php echo 88 * ($alertRate / 100); ?> 88; transform: rotate(-90deg); transform-origin: 50% 50%;"/>
                </svg>
            </div>
        </div>
    </div>
</div>

<!-- Category Alerts -->
<div class="alert-card mb-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Category Alert Settings</h2>
            <p class="text-sm text-gray-600">Configure alert levels for entire categories</p>
        </div>
        <button onclick="openBulkAlertModal()" class="px-6 py-3 rounded-lg font-bold text-white transition hover:opacity-90"
                style="background: linear-gradient(135deg, <?php echo $settings['primary_color']; ?> 0%, <?php echo $settings['primary_color']; ?>dd 100%)">
            <i class="fas fa-cog mr-2"></i>Bulk Settings
        </button>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php while ($category = $categoryAlerts->fetch_assoc()): ?>
        <div class="bg-gray-50 rounded-xl p-4 hover:bg-gray-100 transition cursor-pointer" onclick="editCategoryAlert(<?php echo json_encode($category); ?>)">
            <div class="flex items-center justify-between mb-3">
                <div class="flex-1">
                    <h3 class="font-bold text-gray-900 text-lg"><?php echo htmlspecialchars($category['name']); ?></h3>
                    <p class="text-sm text-gray-600"><?php echo $category['total_products']; ?> products</p>
                </div>
                <?php if ($category['alert_count'] > 0): ?>
                <div class="px-3 py-1 <?php echo $category['alert_count'] >= 5 ? 'bg-red-100 text-red-800' : 'bg-orange-100 text-orange-800'; ?> rounded-full text-xs font-bold">
                    <?php echo $category['alert_count']; ?> alerts
                </div>
                <?php endif; ?>
            </div>
            <div class="flex items-center justify-between text-sm">
                <span class="text-gray-600">Avg. Alert Level:</span>
                <span class="font-bold" style="color: <?php echo $settings['primary_color']; ?>">
                    <?php echo round($category['avg_reorder_level']); ?> units
                </span>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<!-- Product Alerts Table -->
<div class="alert-card">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900 mb-2">Product Alerts</h2>
        <p class="text-sm text-gray-600">Products that need attention</p>
    </div>
    
    <?php if ($alertProducts->num_rows > 0): ?>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 border-b-2 border-gray-200">
                <tr>
                    <th class="text-left py-3 px-4 text-sm font-bold text-gray-700">Product</th>
                    <th class="text-left py-3 px-4 text-sm font-bold text-gray-700">Category</th>
                    <th class="text-center py-3 px-4 text-sm font-bold text-gray-700">Current Stock</th>
                    <th class="text-center py-3 px-4 text-sm font-bold text-gray-700">Alert Level</th>
                    <th class="text-center py-3 px-4 text-sm font-bold text-gray-700">Needed</th>
                    <th class="text-center py-3 px-4 text-sm font-bold text-gray-700">Status</th>
                    <th class="text-center py-3 px-4 text-sm font-bold text-gray-700">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($product = $alertProducts->fetch_assoc()): 
                    $needed = max(0, $product['reorder_level'] - $product['stock_quantity']);
                    $urgency = $product['stock_quantity'] == 0 ? 'critical' : ($product['stock_quantity'] <= $product['reorder_level'] / 2 ? 'high' : 'medium');
                ?>
                <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                    <td class="py-4 px-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center <?php echo $urgency === 'critical' ? 'bg-red-100' : ($urgency === 'high' ? 'bg-orange-100' : 'bg-yellow-100'); ?>">
                                <i class="fas fa-wine-bottle <?php echo $urgency === 'critical' ? 'text-red-600' : ($urgency === 'high' ? 'text-orange-600' : 'text-yellow-600'); ?>"></i>
                            </div>
                            <div>
                                <p class="font-bold text-sm text-gray-900"><?php echo htmlspecialchars($product['name']); ?></p>
                                <?php if ($product['sku']): ?>
                                <p class="text-xs text-gray-500 font-mono"><?php echo htmlspecialchars($product['sku']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td class="py-4 px-4">
                        <span class="px-2 py-1 bg-gray-100 rounded text-xs font-medium">
                            <?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?>
                        </span>
                    </td>
                    <td class="py-4 px-4 text-center">
                        <span class="font-bold text-2xl <?php echo $urgency === 'critical' ? 'text-red-600' : ($urgency === 'high' ? 'text-orange-600' : 'text-yellow-600'); ?>">
                            <?php echo $product['stock_quantity']; ?>
                        </span>
                    </td>
                    <td class="py-4 px-4 text-center">
                        <span class="text-sm font-semibold text-gray-700"><?php echo $product['reorder_level']; ?></span>
                    </td>
                    <td class="py-4 px-4 text-center">
                        <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-bold">
                            <?php echo $needed; ?> units
                        </span>
                    </td>
                    <td class="py-4 px-4 text-center">
                        <span class="px-3 py-1 rounded-full text-xs font-bold <?php echo $urgency === 'critical' ? 'bg-red-100 text-red-800' : ($urgency === 'high' ? 'bg-orange-100 text-orange-800' : 'bg-yellow-100 text-yellow-800'); ?>">
                            <?php echo strtoupper($urgency); ?>
                        </span>
                    </td>
                    <td class="py-4 px-4 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <button onclick='editProductAlert(<?php echo json_encode($product); ?>)' 
                                    class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition" 
                                    title="Edit Alert">
                                <i class="fas fa-bell"></i>
                            </button>
                            <a href="/inventory.php" 
                               class="p-2 text-green-600 hover:bg-green-50 rounded-lg transition" 
                               title="Adjust Stock">
                                <i class="fas fa-box-open"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="text-center py-20">
        <i class="fas fa-check-circle text-6xl text-green-500 mb-4"></i>
        <p class="text-2xl text-gray-700 font-bold mb-2">All Clear!</p>
        <p class="text-gray-500">No stock alerts at this time</p>
    </div>
    <?php endif; ?>
</div>

<!-- Edit Product Alert Modal -->
<div id="productAlertModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4" style="backdrop-filter: blur(4px);">
    <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl">
        <div class="p-6 rounded-t-2xl text-white" style="background: linear-gradient(135deg, <?php echo $settings['primary_color']; ?> 0%, <?php echo $settings['primary_color']; ?>dd 100%)">
            <div class="flex justify-between items-center">
                <div>
                    <h3 class="text-2xl font-bold">Edit Product Alert</h3>
                    <p class="text-white/80 text-sm">Set custom alert level</p>
                </div>
                <button onclick="closeProductAlertModal()" class="text-white/80 hover:text-white transition">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
        </div>
        
        <form id="productAlertForm" class="p-6">
            <input type="hidden" id="productId" name="product_id">
            <input type="hidden" name="action" value="save_product_alert">
            
            <div class="mb-6 p-4 bg-gray-50 rounded-xl">
                <p class="font-bold text-lg text-gray-900" id="productName"></p>
                <div class="flex items-center gap-4 mt-2 text-sm">
                    <div>
                        <span class="text-gray-600">Current Stock:</span>
                        <span class="font-bold text-xl ml-2" id="currentStock" style="color: <?php echo $settings['primary_color']; ?>"></span>
                    </div>
                    <div>
                        <span class="text-gray-600">Current Alert:</span>
                        <span class="font-bold text-xl ml-2" id="currentAlert"></span>
                    </div>
                </div>
            </div>
            
            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 mb-2">New Alert Level *</label>
                <input type="number" name="alert_level" id="alertLevel" required min="0" 
                       class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:outline-none text-lg font-bold"
                       style="border-color: <?php echo $settings['primary_color']; ?>33;">
                <p class="text-xs text-gray-500 mt-2">
                    <i class="fas fa-info-circle mr-1"></i>
                    Alert will trigger when stock reaches or falls below this level
                </p>
            </div>
            
            <div class="flex gap-3">
                <button type="button" onclick="closeProductAlertModal()" 
                        class="flex-1 px-6 py-3 border-2 border-gray-300 rounded-lg font-bold text-gray-700 hover:bg-gray-50 transition">
                    Cancel
                </button>
                <button type="submit" 
                        class="flex-1 px-6 py-3 rounded-lg font-bold text-white transition hover:opacity-90"
                        style="background: linear-gradient(135deg, <?php echo $settings['primary_color']; ?> 0%, <?php echo $settings['primary_color']; ?>dd 100%)">
                    <i class="fas fa-save mr-2"></i>Save Alert
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Category Alert Modal -->
<div id="categoryAlertModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4" style="backdrop-filter: blur(4px);">
    <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl">
        <div class="p-6 rounded-t-2xl text-white" style="background: linear-gradient(135deg, <?php echo $settings['primary_color']; ?> 0%, <?php echo $settings['primary_color']; ?>dd 100%)">
            <div class="flex justify-between items-center">
                <div>
                    <h3 class="text-2xl font-bold">Edit Category Alerts</h3>
                    <p class="text-white/80 text-sm">Apply to all products in category</p>
                </div>
                <button onclick="closeCategoryAlertModal()" class="text-white/80 hover:text-white transition">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
        </div>
        
        <form id="categoryAlertForm" class="p-6">
            <input type="hidden" id="categoryId" name="category_id">
            <input type="hidden" name="action" value="save_category_alert">
            
            <div class="mb-6 p-4 bg-gray-50 rounded-xl">
                <p class="font-bold text-lg text-gray-900" id="categoryName"></p>
                <div class="mt-2 text-sm">
                    <span class="text-gray-600">Products in category:</span>
                    <span class="font-bold text-lg ml-2" id="categoryProducts" style="color: <?php echo $settings['primary_color']; ?>"></span>
                </div>
            </div>
            
            <div class="bg-yellow-50 border-2 border-yellow-200 rounded-xl p-4 mb-6">
                <div class="flex items-start gap-3">
                    <i class="fas fa-exclamation-triangle text-yellow-600 text-xl mt-1"></i>
                    <div>
                        <p class="font-bold text-yellow-800 text-sm mb-1">Warning</p>
                        <p class="text-xs text-yellow-700">This will update the alert level for ALL products in this category.</p>
                    </div>
                </div>
            </div>
            
            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 mb-2">Alert Level for All Products *</label>
                <input type="number" name="alert_level" id="categoryAlertLevel" required min="0" 
                       class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:outline-none text-lg font-bold"
                       style="border-color: <?php echo $settings['primary_color']; ?>33;">
            </div>
            
            <div class="flex gap-3">
                <button type="button" onclick="closeCategoryAlertModal()" 
                        class="flex-1 px-6 py-3 border-2 border-gray-300 rounded-lg font-bold text-gray-700 hover:bg-gray-50 transition">
                    Cancel
                </button>
                <button type="submit" 
                        class="flex-1 px-6 py-3 rounded-lg font-bold text-white transition hover:opacity-90"
                        style="background: linear-gradient(135deg, <?php echo $settings['primary_color']; ?> 0%, <?php echo $settings['primary_color']; ?>dd 100%)">
                    <i class="fas fa-save mr-2"></i>Apply to All
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Bulk Alert Settings Modal -->
<div id="bulkAlertModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4" style="backdrop-filter: blur(4px);">
    <div class="bg-white rounded-2xl max-w-lg w-full shadow-2xl">
        <div class="p-6 rounded-t-2xl text-white" style="background: linear-gradient(135deg, <?php echo $settings['primary_color']; ?> 0%, <?php echo $settings['primary_color']; ?>dd 100%)">
            <div class="flex justify-between items-center">
                <div>
                    <h3 class="text-2xl font-bold">Bulk Alert Settings</h3>
                    <p class="text-white/80 text-sm">Quick configuration options</p>
                </div>
                <button onclick="closeBulkAlertModal()" class="text-white/80 hover:text-white transition">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
        </div>
        
        <div class="p-6">
            <div class="space-y-4">
                <button onclick="applyPreset('conservative')" class="w-full p-4 border-2 border-gray-200 rounded-xl hover:border-blue-500 transition text-left">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-shield-alt text-blue-600 text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-bold text-gray-900 mb-1">Conservative (High Stock)</h4>
                            <p class="text-sm text-gray-600">Alert at 20 units - Best for high-demand products</p>
                        </div>
                    </div>
                </button>
                
                <button onclick="applyPreset('balanced')" class="w-full p-4 border-2 border-gray-200 rounded-xl hover:border-green-500 transition text-left">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-balance-scale text-green-600 text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-bold text-gray-900 mb-1">Balanced (Medium Stock)</h4>
                            <p class="text-sm text-gray-600">Alert at 10 units - Recommended for most products</p>
                        </div>
                    </div>
                </button>
                
                <button onclick="applyPreset('minimal')" class="w-full p-4 border-2 border-gray-200 rounded-xl hover:border-orange-500 transition text-left">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-compress-alt text-orange-600 text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-bold text-gray-900 mb-1">Minimal (Low Stock)</h4>
                            <p class="text-sm text-gray-600">Alert at 5 units - For slow-moving items</p>
                        </div>
                    </div>
                </button>
            </div>
            
            <button onclick="closeBulkAlertModal()" class="w-full mt-6 px-6 py-3 border-2 border-gray-300 rounded-lg font-bold text-gray-700 hover:bg-gray-50 transition">
                Cancel
            </button>
        </div>
    </div>
</div>

<script>
function editProductAlert(product) {
    document.getElementById('productAlertModal').classList.remove('hidden');
    document.getElementById('productAlertModal').classList.add('flex');
    document.getElementById('productId').value = product.id;
    document.getElementById('productName').textContent = product.name;
    document.getElementById('currentStock').textContent = product.stock_quantity;
    document.getElementById('currentAlert').textContent = product.reorder_level;
    document.getElementById('alertLevel').value = product.reorder_level;
}

function closeProductAlertModal() {
    document.getElementById('productAlertModal').classList.add('hidden');
    document.getElementById('productAlertModal').classList.remove('flex');
}

function editCategoryAlert(category) {
    document.getElementById('categoryAlertModal').classList.remove('hidden');
    document.getElementById('categoryAlertModal').classList.add('flex');
    document.getElementById('categoryId').value = category.id;
    document.getElementById('categoryName').textContent = category.name;
    document.getElementById('categoryProducts').textContent = category.total_products;
    document.getElementById('categoryAlertLevel').value = Math.round(category.avg_reorder_level);
}

function closeCategoryAlertModal() {
    document.getElementById('categoryAlertModal').classList.add('hidden');
    document.getElementById('categoryAlertModal').classList.remove('flex');
}

function openBulkAlertModal() {
    document.getElementById('bulkAlertModal').classList.remove('hidden');
    document.getElementById('bulkAlertModal').classList.add('flex');
}

function closeBulkAlertModal() {
    document.getElementById('bulkAlertModal').classList.add('hidden');
    document.getElementById('bulkAlertModal').classList.remove('flex');
}

function applyPreset(preset) {
    const levels = {
        conservative: 20,
        balanced: 10,
        minimal: 5
    };
    
    if (!confirm(`Apply ${preset} preset (${levels[preset]} units) to ALL products?`)) return;
    
    const formData = new FormData();
    formData.append('action', 'save_category_alert');
    formData.append('category_id', 0); // 0 means all categories
    formData.append('alert_level', levels[preset]);
    
    // Update all products
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast(`Applied ${preset} preset successfully`, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(data.message, 'error');
        }
    });
}

document.getElementById('productAlertForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message, 'error');
        }
    });
});

document.getElementById('categoryAlertForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message, 'error');
        }
    });
});

function showToast(message, type) {
    const colors = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        info: 'bg-blue-500'
    };
    
    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 ${colors[type]} text-white px-6 py-3 rounded-lg shadow-lg z-[200]`;
    toast.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} mr-2"></i>${message}`;
    
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}

// ESC to close modals
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeProductAlertModal();
        closeCategoryAlertModal();
        closeBulkAlertModal();
    }
});
</script>

<?php include 'footer.php'; ?>
