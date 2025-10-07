<?php
require_once 'config.php';
requireOwner();

$page_title = 'Supplier Management';
$settings = getSettings();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'save_supplier') {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $name = sanitize($_POST['name']);
        $contactPerson = sanitize($_POST['contact_person']);
        $email = isset($_POST['email']) ? sanitize($_POST['email']) : '';
        $phone = sanitize($_POST['phone']);
        $address = isset($_POST['address']) ? sanitize($_POST['address']) : '';
        $paymentTerms = isset($_POST['payment_terms']) ? sanitize($_POST['payment_terms']) : '';
        
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE suppliers SET name=?, contact_person=?, email=?, phone=?, address=?, payment_terms=? WHERE id=?");
            $stmt->bind_param("ssssssi", $name, $contactPerson, $email, $phone, $address, $paymentTerms, $id);
            $message = 'Supplier updated successfully';
        } else {
            $stmt = $conn->prepare("INSERT INTO suppliers (name, contact_person, email, phone, address, payment_terms) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $name, $contactPerson, $email, $phone, $address, $paymentTerms);
            $message = 'Supplier added successfully';
        }
        
        if ($stmt->execute()) {
            logActivity('SUPPLIER_SAVED', $message . ": $name");
            respond(true, $message);
        } else {
            respond(false, 'Failed to save supplier');
        }
    }
    
    if ($_POST['action'] === 'delete_supplier') {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("UPDATE suppliers SET status='inactive' WHERE id=?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            logActivity('SUPPLIER_DELETED', "Deleted supplier ID: $id");
            respond(true, 'Supplier deleted successfully');
        } else {
            respond(false, 'Failed to delete supplier');
        }
    }
    
    exit;
}

// Get suppliers with statistics
$suppliers = $conn->query("SELECT s.*, 
                           COUNT(DISTINCT po.id) as order_count,
                           COALESCE(SUM(po.total_amount), 0) as total_purchases
                           FROM suppliers s
                           LEFT JOIN purchase_orders po ON s.id = po.supplier_id
                           WHERE s.status = 'active'
                           GROUP BY s.id
                           ORDER BY total_purchases DESC");

$totalSuppliers = $conn->query("SELECT COUNT(*) as count FROM suppliers WHERE status='active'")->fetch_assoc()['count'];
$totalPurchases = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM purchase_orders")->fetch_assoc()['total'];

include 'header.php';
?>

<style>
.supplier-card {
    background: white;
    border-radius: 1rem;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: all 0.3s;
}
.supplier-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(0,0,0,0.15);
}
</style>

<!-- Stats -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="supplier-card">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600 mb-1">Total Suppliers</p>
                <h3 class="text-4xl font-bold text-blue-600"><?php echo $totalSuppliers; ?></h3>
            </div>
            <div class="w-14 h-14 bg-blue-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-truck text-blue-600 text-2xl"></i>
            </div>
        </div>
    </div>
    
    <div class="supplier-card">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600 mb-1">Total Purchases</p>
                <h3 class="text-3xl font-bold text-green-600"><?php echo formatCurrency($totalPurchases); ?></h3>
            </div>
            <div class="w-14 h-14 bg-green-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-shopping-cart text-green-600 text-2xl"></i>
            </div>
        </div>
    </div>
    
    <div class="supplier-card">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600 mb-1">Active Orders</p>
                <?php $activeOrders = $conn->query("SELECT COUNT(*) as count FROM purchase_orders WHERE status='pending' OR status='approved'")->fetch_assoc()['count']; ?>
                <h3 class="text-4xl font-bold text-orange-600"><?php echo $activeOrders; ?></h3>
            </div>
            <div class="w-14 h-14 bg-orange-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-box text-orange-600 text-2xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Actions -->
<div class="supplier-card mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-xl font-bold">Supplier Directory</h2>
            <p class="text-sm text-gray-600">Manage your supplier relationships</p>
        </div>
        <div class="flex gap-3">
            <a href="/purchase-orders.php" class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg font-bold transition">
                <i class="fas fa-file-invoice mr-2"></i>Purchase Orders
            </a>
            <button onclick="openSupplierModal()" class="px-6 py-3 rounded-lg font-bold text-white transition hover:opacity-90"
                    style="background: linear-gradient(135deg, <?php echo $settings['primary_color']; ?> 0%, <?php echo $settings['primary_color']; ?>dd 100%)">
                <i class="fas fa-plus mr-2"></i>Add Supplier
            </button>
        </div>
    </div>
</div>

<!-- Suppliers Grid -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <?php while ($supplier = $suppliers->fetch_assoc()): ?>
    <div class="supplier-card">
        <div class="flex items-start justify-between mb-4">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-gradient-to-br from-blue-500 to-blue-600 text-white text-xl font-bold">
                    <?php echo strtoupper(substr($supplier['name'], 0, 2)); ?>
                </div>
                <div>
                    <h3 class="font-bold text-lg text-gray-900"><?php echo htmlspecialchars($supplier['name']); ?></h3>
                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($supplier['contact_person']); ?></p>
                </div>
            </div>
            <div class="flex gap-2">
                <button onclick='editSupplier(<?php echo json_encode($supplier); ?>)' 
                        class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg" title="Edit">
                    <i class="fas fa-edit"></i>
                </button>
                <button onclick="deleteSupplier(<?php echo $supplier['id']; ?>, '<?php echo htmlspecialchars(addslashes($supplier['name'])); ?>')" 
                        class="p-2 text-red-600 hover:bg-red-50 rounded-lg" title="Delete">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
        
        <div class="space-y-2 mb-4">
            <?php if ($supplier['phone']): ?>
            <div class="flex items-center gap-2 text-sm text-gray-700">
                <i class="fas fa-phone w-4"></i>
                <span><?php echo htmlspecialchars($supplier['phone']); ?></span>
            </div>
            <?php endif; ?>
            <?php if ($supplier['email']): ?>
            <div class="flex items-center gap-2 text-sm text-gray-700">
                <i class="fas fa-envelope w-4"></i>
                <span><?php echo htmlspecialchars($supplier['email']); ?></span>
            </div>
            <?php endif; ?>
            <?php if ($supplier['payment_terms']): ?>
            <div class="flex items-center gap-2 text-sm text-gray-700">
                <i class="fas fa-credit-card w-4"></i>
                <span><?php echo htmlspecialchars($supplier['payment_terms']); ?></span>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="grid grid-cols-2 gap-3 pt-3 border-t border-gray-200">
            <div class="text-center">
                <p class="text-2xl font-bold text-blue-600"><?php echo $supplier['order_count']; ?></p>
                <p class="text-xs text-gray-600">Orders</p>
            </div>
            <div class="text-center">
                <p class="text-xl font-bold text-green-600"><?php echo formatCurrency($supplier['total_purchases']); ?></p>
                <p class="text-xs text-gray-600">Total Value</p>
            </div>
        </div>
        
        <a href="/purchase-orders.php?supplier=<?php echo $supplier['id']; ?>" 
           class="block mt-4 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-center rounded-lg font-semibold transition">
            <i class="fas fa-eye mr-2"></i>View Orders
        </a>
    </div>
    <?php endwhile; ?>
</div>

<!-- Supplier Modal -->
<div id="supplierModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl w-full max-w-2xl shadow-2xl">
        <div class="p-6 rounded-t-2xl text-white" style="background: linear-gradient(135deg, <?php echo $settings['primary_color']; ?> 0%, <?php echo $settings['primary_color']; ?>dd 100%)">
            <div class="flex justify-between items-center">
                <h3 class="text-2xl font-bold" id="modalTitle">Add Supplier</h3>
                <button onclick="closeSupplierModal()" class="text-white/80 hover:text-white">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
        </div>
        
        <form id="supplierForm" class="p-6">
            <input type="hidden" id="supplierId" name="id">
            <input type="hidden" name="action" value="save_supplier">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Supplier Name *</label>
                    <input type="text" name="name" id="supplierName" required 
                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:outline-none">
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Contact Person *</label>
                    <input type="text" name="contact_person" id="contactPerson" required 
                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:outline-none">
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Phone *</label>
                    <input type="tel" name="phone" id="supplierPhone" required 
                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:outline-none">
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Email</label>
                    <input type="email" name="email" id="supplierEmail" 
                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:outline-none">
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Payment Terms</label>
                    <input type="text" name="payment_terms" id="paymentTerms" placeholder="e.g., Net 30 days"
                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:outline-none">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Address</label>
                    <textarea name="address" id="supplierAddress" rows="2" 
                              class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:outline-none"></textarea>
                </div>
            </div>
            
            <div class="flex gap-3 mt-6">
                <button type="button" onclick="closeSupplierModal()" 
                        class="flex-1 px-6 py-3 border-2 border-gray-300 rounded-lg font-bold text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" 
                        class="flex-1 px-6 py-3 rounded-lg font-bold text-white transition hover:opacity-90"
                        style="background: linear-gradient(135deg, <?php echo $settings['primary_color']; ?> 0%, <?php echo $settings['primary_color']; ?>dd 100%)">
                    <i class="fas fa-save mr-2"></i>Save Supplier
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openSupplierModal() {
    document.getElementById('supplierModal').classList.remove('hidden');
    document.getElementById('supplierModal').classList.add('flex');
    document.getElementById('supplierForm').reset();
    document.getElementById('supplierId').value = '';
    document.getElementById('modalTitle').textContent = 'Add Supplier';
}

function closeSupplierModal() {
    document.getElementById('supplierModal').classList.add('hidden');
    document.getElementById('supplierModal').classList.remove('flex');
}

function editSupplier(supplier) {
    document.getElementById('supplierModal').classList.remove('hidden');
    document.getElementById('supplierModal').classList.add('flex');
    document.getElementById('modalTitle').textContent = 'Edit Supplier';
    document.getElementById('supplierId').value = supplier.id;
    document.getElementById('supplierName').value = supplier.name;
    document.getElementById('contactPerson').value = supplier.contact_person || '';
    document.getElementById('supplierPhone').value = supplier.phone || '';
    document.getElementById('supplierEmail').value = supplier.email || '';
    document.getElementById('paymentTerms').value = supplier.payment_terms || '';
    document.getElementById('supplierAddress').value = supplier.address || '';
}

function deleteSupplier(id, name) {
    if (!confirm(`Delete supplier "${name}"?`)) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_supplier');
    formData.append('id', id);

    fetch('', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if (data.success) location.reload();
        else alert(data.message);
    });
}

document.getElementById('supplierForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);

    fetch('', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if (data.success) location.reload();
        else alert(data.message);
    });
});
</script>

<?php include 'footer.php'; ?>
