<?php
require_once 'config.php';
requireOwner();

$page_title = 'Customer Management';
$settings = getSettings();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'save_customer') {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $name = sanitize($_POST['name']);
        $email = isset($_POST['email']) ? sanitize($_POST['email']) : null;
        $phone = sanitize($_POST['phone']);
        $address = isset($_POST['address']) ? sanitize($_POST['address']) : '';
        $birthday = isset($_POST['birthday']) && !empty($_POST['birthday']) ? sanitize($_POST['birthday']) : null;
        
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE customers SET name=?, email=?, phone=?, address=?, birthday=? WHERE id=?");
            $stmt->bind_param("sssssi", $name, $email, $phone, $address, $birthday, $id);
            $message = 'Customer updated successfully';
        } else {
            $customerCode = 'CUST-' . strtoupper(substr(uniqid(), -8));
            $stmt = $conn->prepare("INSERT INTO customers (customer_code, name, email, phone, address, birthday) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $customerCode, $name, $email, $phone, $address, $birthday);
            $message = 'Customer added successfully';
        }
        
        if ($stmt->execute()) {
            logActivity('CUSTOMER_SAVED', $message . ": $name");
            respond(true, $message);
        } else {
            respond(false, 'Failed to save customer');
        }
    }
    
    if ($_POST['action'] === 'delete_customer') {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("UPDATE customers SET status='inactive' WHERE id=?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            logActivity('CUSTOMER_DELETED', "Deleted customer ID: $id");
            respond(true, 'Customer deleted successfully');
        } else {
            respond(false, 'Failed to delete customer');
        }
    }
    
    exit;
}

// Get customers with statistics
$customers = $conn->query("SELECT c.*, 
                           COUNT(DISTINCT s.id) as purchase_count,
                           COALESCE(SUM(s.total_amount), 0) as lifetime_value,
                           MAX(s.sale_date) as last_purchase
                           FROM customers c
                           LEFT JOIN sales s ON c.id = s.customer_id
                           WHERE c.status = 'active'
                           GROUP BY c.id
                           ORDER BY lifetime_value DESC");

// Statistics
$totalCustomers = $conn->query("SELECT COUNT(*) as count FROM customers WHERE status='active'")->fetch_assoc()['count'];
$totalLoyaltyPoints = $conn->query("SELECT SUM(loyalty_points) as total FROM customers WHERE status='active'")->fetch_assoc()['total'];
$avgLifetimeValue = $conn->query("SELECT AVG(total_purchases) as avg FROM customers WHERE status='active'")->fetch_assoc()['avg'];

include 'header.php';
?>

<style>
.customer-card {
    background: white;
    border-radius: 1rem;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: all 0.3s;
}
.customer-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(0,0,0,0.15);
}
</style>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="customer-card">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600 mb-1">Total Customers</p>
                <h3 class="text-4xl font-bold text-blue-600"><?php echo $totalCustomers; ?></h3>
            </div>
            <div class="w-14 h-14 bg-blue-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-users text-blue-600 text-2xl"></i>
            </div>
        </div>
    </div>
    
    <div class="customer-card">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600 mb-1">Total Loyalty Points</p>
                <h3 class="text-4xl font-bold text-purple-600"><?php echo number_format($totalLoyaltyPoints); ?></h3>
            </div>
            <div class="w-14 h-14 bg-purple-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-star text-purple-600 text-2xl"></i>
            </div>
        </div>
    </div>
    
    <div class="customer-card">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600 mb-1">Avg Lifetime Value</p>
                <h3 class="text-3xl font-bold text-green-600"><?php echo formatCurrency($avgLifetimeValue); ?></h3>
            </div>
            <div class="w-14 h-14 bg-green-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-chart-line text-green-600 text-2xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Add Customer Button -->
<div class="customer-card mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-xl font-bold">Customer Database</h2>
            <p class="text-sm text-gray-600">Manage your customers and loyalty program</p>
        </div>
        <button onclick="openCustomerModal()" class="px-6 py-3 rounded-lg font-bold text-white transition hover:opacity-90"
                style="background: linear-gradient(135deg, <?php echo $settings['primary_color']; ?> 0%, <?php echo $settings['primary_color']; ?>dd 100%)">
            <i class="fas fa-plus mr-2"></i>Add Customer
        </button>
    </div>
</div>

<!-- Customers Table -->
<div class="customer-card">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 border-b-2 border-gray-200">
                <tr>
                    <th class="text-left py-3 px-4 text-sm font-bold text-gray-700">Customer</th>
                    <th class="text-left py-3 px-4 text-sm font-bold text-gray-700">Contact</th>
                    <th class="text-center py-3 px-4 text-sm font-bold text-gray-700">Purchases</th>
                    <th class="text-center py-3 px-4 text-sm font-bold text-gray-700">Loyalty Points</th>
                    <th class="text-right py-3 px-4 text-sm font-bold text-gray-700">Lifetime Value</th>
                    <th class="text-left py-3 px-4 text-sm font-bold text-gray-700">Last Purchase</th>
                    <th class="text-center py-3 px-4 text-sm font-bold text-gray-700">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($customer = $customers->fetch_assoc()): ?>
                <tr class="border-b border-gray-100 hover:bg-gray-50">
                    <td class="py-4 px-4">
                        <div>
                            <p class="font-bold text-gray-900"><?php echo htmlspecialchars($customer['name']); ?></p>
                            <p class="text-xs text-gray-500"><?php echo htmlspecialchars($customer['customer_code']); ?></p>
                        </div>
                    </td>
                    <td class="py-4 px-4">
                        <div class="text-sm">
                            <?php if ($customer['phone']): ?>
                            <p class="text-gray-700"><i class="fas fa-phone mr-1"></i><?php echo htmlspecialchars($customer['phone']); ?></p>
                            <?php endif; ?>
                            <?php if ($customer['email']): ?>
                            <p class="text-gray-600 text-xs"><?php echo htmlspecialchars($customer['email']); ?></p>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="py-4 px-4 text-center">
                        <span class="text-lg font-bold text-blue-600"><?php echo $customer['purchase_count']; ?></span>
                    </td>
                    <td class="py-4 px-4 text-center">
                        <span class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full font-bold">
                            <i class="fas fa-star mr-1"></i><?php echo number_format($customer['loyalty_points']); ?>
                        </span>
                    </td>
                    <td class="py-4 px-4 text-right">
                        <span class="font-bold text-green-600"><?php echo formatCurrency($customer['lifetime_value']); ?></span>
                    </td>
                    <td class="py-4 px-4 text-sm text-gray-600">
                        <?php echo $customer['last_purchase'] ? date('M d, Y', strtotime($customer['last_purchase'])) : 'Never'; ?>
                    </td>
                    <td class="py-4 px-4 text-center">
                        <div class="flex justify-center gap-2">
                            <button onclick='editCustomer(<?php echo json_encode($customer); ?>)' 
                                    class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="viewCustomerHistory(<?php echo $customer['id']; ?>)" 
                                    class="p-2 text-green-600 hover:bg-green-50 rounded-lg" title="View History">
                                <i class="fas fa-history"></i>
                            </button>
                            <button onclick="deleteCustomer(<?php echo $customer['id']; ?>, '<?php echo htmlspecialchars(addslashes($customer['name'])); ?>')" 
                                    class="p-2 text-red-600 hover:bg-red-50 rounded-lg" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Customer Modal -->
<div id="customerModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl w-full max-w-2xl shadow-2xl">
        <div class="p-6 rounded-t-2xl text-white" style="background: linear-gradient(135deg, <?php echo $settings['primary_color']; ?> 0%, <?php echo $settings['primary_color']; ?>dd 100%)">
            <div class="flex justify-between items-center">
                <h3 class="text-2xl font-bold" id="modalTitle">Add Customer</h3>
                <button onclick="closeCustomerModal()" class="text-white/80 hover:text-white">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
        </div>
        
        <form id="customerForm" class="p-6">
            <input type="hidden" id="customerId" name="id">
            <input type="hidden" name="action" value="save_customer">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Full Name *</label>
                    <input type="text" name="name" id="customerName" required 
                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:outline-none">
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Phone *</label>
                    <input type="tel" name="phone" id="customerPhone" required 
                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:outline-none">
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Email</label>
                    <input type="email" name="email" id="customerEmail" 
                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:outline-none">
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Birthday</label>
                    <input type="date" name="birthday" id="customerBirthday" 
                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:outline-none">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Address</label>
                    <textarea name="address" id="customerAddress" rows="2" 
                              class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:outline-none"></textarea>
                </div>
            </div>
            
            <div class="flex gap-3 mt-6">
                <button type="button" onclick="closeCustomerModal()" 
                        class="flex-1 px-6 py-3 border-2 border-gray-300 rounded-lg font-bold text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" 
                        class="flex-1 px-6 py-3 rounded-lg font-bold text-white transition hover:opacity-90"
                        style="background: linear-gradient(135deg, <?php echo $settings['primary_color']; ?> 0%, <?php echo $settings['primary_color']; ?>dd 100%)">
                    <i class="fas fa-save mr-2"></i>Save Customer
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openCustomerModal() {
    document.getElementById('customerModal').classList.remove('hidden');
    document.getElementById('customerModal').classList.add('flex');
    document.getElementById('customerForm').reset();
    document.getElementById('customerId').value = '';
    document.getElementById('modalTitle').textContent = 'Add Customer';
}

function closeCustomerModal() {
    document.getElementById('customerModal').classList.add('hidden');
    document.getElementById('customerModal').classList.remove('flex');
}

function editCustomer(customer) {
    document.getElementById('customerModal').classList.remove('hidden');
    document.getElementById('customerModal').classList.add('flex');
    document.getElementById('modalTitle').textContent = 'Edit Customer';
    document.getElementById('customerId').value = customer.id;
    document.getElementById('customerName').value = customer.name;
    document.getElementById('customerPhone').value = customer.phone || '';
    document.getElementById('customerEmail').value = customer.email || '';
    document.getElementById('customerBirthday').value = customer.birthday || '';
    document.getElementById('customerAddress').value = customer.address || '';
}

function deleteCustomer(id, name) {
    if (!confirm(`Delete customer "${name}"?`)) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_customer');
    formData.append('id', id);

    fetch('', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message);
        }
    });
}

function viewCustomerHistory(id) {
    window.location.href = `/sales.php?customer=${id}`;
}

document.getElementById('customerForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);

    fetch('', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message);
        }
    });
});
</script>

<?php include 'footer.php'; ?>
