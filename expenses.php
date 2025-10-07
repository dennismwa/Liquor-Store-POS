<?php
require_once 'config.php';
requireOwner();

$page_title = 'Expenses';
$settings = getSettings();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Clean any output buffers
    if (ob_get_level()) ob_clean();
    
    header('Content-Type: application/json');
    
    try {
        $action = $_POST['action'];
        
        if ($action === 'add_expense') {
            $category = sanitize($_POST['category']);
            $amount = floatval($_POST['amount']);
            $description = isset($_POST['description']) ? sanitize($_POST['description']) : '';
            $expenseDate = sanitize($_POST['expense_date']);
            $receipt = isset($_POST['receipt_number']) ? sanitize($_POST['receipt_number']) : '';
            
            if (empty($category) || $amount <= 0 || empty($expenseDate)) {
                echo json_encode(['success' => false, 'message' => 'Category, amount, and date are required']);
                exit;
            }
            
            $stmt = $conn->prepare("INSERT INTO expenses (user_id, category, amount, description, expense_date) VALUES (?, ?, ?, ?, ?)");
            if (!$stmt) {
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
                exit;
            }
            
            $stmt->bind_param("isdss", $_SESSION['user_id'], $category, $amount, $description, $expenseDate);
            
            if ($stmt->execute()) {
                logActivity('EXPENSE_ADDED', "Added expense: $category - " . formatCurrency($amount));
                echo json_encode(['success' => true, 'message' => 'Expense added successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add expense: ' . $stmt->error]);
            }
            $stmt->close();
            exit;
        }
        
        if ($action === 'update_expense') {
            $id = (int)$_POST['id'];
            $category = sanitize($_POST['category']);
            $amount = floatval($_POST['amount']);
            $description = isset($_POST['description']) ? sanitize($_POST['description']) : '';
            $expenseDate = sanitize($_POST['expense_date']);
            $receipt = isset($_POST['receipt_number']) ? sanitize($_POST['receipt_number']) : '';
            
            $stmt = $conn->prepare("UPDATE expenses SET category=?, amount=?, description=?, expense_date=? WHERE id=?");
            if (!$stmt) {
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
                exit;
            }
            
            $stmt->bind_param("sdssi", $category, $amount, $description, $expenseDate, $id);
            
            if ($stmt->execute()) {
                logActivity('EXPENSE_UPDATED', "Updated expense ID: $id");
                echo json_encode(['success' => true, 'message' => 'Expense updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update expense: ' . $stmt->error]);
            }
            $stmt->close();
            exit;
        }
        
        if ($action === 'delete_expense') {
            $id = (int)$_POST['id'];
            
            $stmt = $conn->prepare("DELETE FROM expenses WHERE id=?");
            if (!$stmt) {
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
                exit;
            }
            
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                logActivity('EXPENSE_DELETED', "Deleted expense ID: $id");
                echo json_encode(['success' => true, 'message' => 'Expense deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete expense: ' . $stmt->error]);
            }
            $stmt->close();
            exit;
        }
        
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit;
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
        exit;
    }
}

// Get filter parameters
$dateFrom = isset($_GET['from']) ? sanitize($_GET['from']) : date('Y-m-01');
$dateTo = isset($_GET['to']) ? sanitize($_GET['to']) : date('Y-m-d');
$categoryFilter = isset($_GET['category']) ? sanitize($_GET['category']) : '';

// Build query
$where = ["expense_date BETWEEN '$dateFrom' AND '$dateTo'"];
if ($categoryFilter) {
    $where[] = "category = '$categoryFilter'";
}
$whereClause = implode(' AND ', $where);

// Get expenses
$expenses = $conn->query("SELECT e.*, u.name as added_by 
                          FROM expenses e 
                          LEFT JOIN users u ON e.user_id = u.id 
                          WHERE $whereClause 
                          ORDER BY expense_date DESC, created_at DESC");

// Get summary
$summary = $conn->query("SELECT 
                         COALESCE(SUM(amount), 0) as total,
                         COUNT(*) as count
                         FROM expenses 
                         WHERE $whereClause")->fetch_assoc();

// Get category breakdown
$categoryBreakdown = $conn->query("SELECT category, SUM(amount) as total, COUNT(*) as count 
                                   FROM expenses 
                                   WHERE $whereClause 
                                   GROUP BY category 
                                   ORDER BY total DESC");

// Get unique categories
$categories = $conn->query("SELECT DISTINCT category FROM expenses ORDER BY category");

include 'header.php';
?>

<style>
.expense-card {
    background: white;
    border-radius: 1rem;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    transition: all 0.3s;
}

.category-badge {
    padding: 0.5rem 1rem;
    border-radius: 0.75rem;
    font-size: 0.875rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

/* Fixed Modal Styles */
.modal-backdrop {
    overflow-y: auto;
    padding: 1rem;
}

.modal-content {
    margin: auto;
    max-width: 32rem;
    width: 100%;
}

.modal-form-container {
    max-height: calc(100vh - 220px);
    overflow-y: auto;
}

.modal-actions-sticky {
    position: sticky;
    bottom: 0;
    background: white;
    padding-top: 1.5rem;
    margin-top: 1.5rem;
    border-top: 2px solid #e5e7eb;
}

@media (max-width: 640px) {
    .modal-backdrop {
        padding: 0.5rem;
    }
    
    .modal-form-container {
        max-height: calc(100vh - 180px);
    }
}
</style>

<!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="expense-card">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600 mb-1">Total Expenses</p>
                <h3 class="text-3xl font-bold text-red-600"><?php echo formatCurrency($summary['total']); ?></h3>
                <p class="text-xs text-gray-500 mt-1"><?php echo $summary['count']; ?> transactions</p>
            </div>
            <div class="w-14 h-14 bg-red-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-money-bill-wave text-red-600 text-2xl"></i>
            </div>
        </div>
    </div>
    
    <div class="expense-card">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600 mb-1">Average per Day</p>
                <?php
                $days = max(1, (strtotime($dateTo) - strtotime($dateFrom)) / 86400 + 1);
                $avgPerDay = $summary['total'] / $days;
                ?>
                <h3 class="text-2xl font-bold text-gray-900"><?php echo formatCurrency($avgPerDay); ?></h3>
                <p class="text-xs text-gray-500 mt-1">over <?php echo round($days); ?> days</p>
            </div>
            <div class="w-14 h-14 bg-blue-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-chart-line text-blue-600 text-2xl"></i>
            </div>
        </div>
    </div>
    
    <div class="expense-card">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600 mb-1">Categories</p>
                <h3 class="text-3xl font-bold text-gray-900"><?php echo $categoryBreakdown->num_rows; ?></h3>
                <p class="text-xs text-gray-500 mt-1">expense types</p>
            </div>
            <div class="w-14 h-14 bg-purple-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-tags text-purple-600 text-2xl"></i>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <!-- Filters & Add Button -->
    <div class="lg:col-span-2 expense-card">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[150px]">
                <label class="block text-sm font-semibold text-gray-700 mb-2">From Date</label>
                <input type="date" name="from" value="<?php echo $dateFrom; ?>" 
                       class="w-full px-4 py-2 border-2 border-gray-200 rounded-lg focus:outline-none transition"
                       style="focus:border-color: <?php echo $settings['primary_color']; ?>">
            </div>
            
            <div class="flex-1 min-w-[150px]">
                <label class="block text-sm font-semibold text-gray-700 mb-2">To Date</label>
                <input type="date" name="to" value="<?php echo $dateTo; ?>" 
                       class="w-full px-4 py-2 border-2 border-gray-200 rounded-lg focus:outline-none transition"
                       style="focus:border-color: <?php echo $settings['primary_color']; ?>">
            </div>
            
            <div class="flex-1 min-w-[150px]">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Category</label>
                <select name="category" class="w-full px-4 py-2 border-2 border-gray-200 rounded-lg focus:outline-none transition">
                    <option value="">All Categories</option>
                    <?php 
                    $categories->data_seek(0);
                    while ($cat = $categories->fetch_assoc()): 
                    ?>
                    <option value="<?php echo htmlspecialchars($cat['category']); ?>" <?php echo $categoryFilter === $cat['category'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['category']); ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="flex gap-2">
                <button type="submit" 
                        class="px-6 py-2 rounded-lg font-semibold text-white transition hover:opacity-90"
                        style="background-color: <?php echo $settings['primary_color']; ?>">
                    <i class="fas fa-filter mr-2"></i>Filter
                </button>
                <a href="/expenses.php" 
                   class="px-4 py-2 border-2 border-gray-300 rounded-lg font-semibold text-gray-700 hover:bg-gray-50 transition flex items-center justify-center">
                    <i class="fas fa-redo"></i>
                </a>
            </div>
        </form>
        
        <div class="mt-4 pt-4 border-t border-gray-200">
            <button onclick="openExpenseModal()" 
                    class="w-full px-6 py-3 rounded-lg font-bold text-white transition hover:opacity-90 shadow-lg"
                    style="background: linear-gradient(135deg, <?php echo $settings['primary_color']; ?> 0%, <?php echo $settings['primary_color']; ?>dd 100%)">
                <i class="fas fa-plus mr-2"></i>Add New Expense
            </button>
        </div>
    </div>
    
    <!-- Category Breakdown -->
    <div class="expense-card">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Category Breakdown</h3>
        <div class="space-y-3">
            <?php 
            $categoryBreakdown->data_seek(0);
            if ($categoryBreakdown->num_rows > 0):
                $categoryColors = ['bg-blue-500', 'bg-green-500', 'bg-purple-500', 'bg-orange-500', 'bg-pink-500', 'bg-indigo-500'];
                $colorIndex = 0;
                while ($cat = $categoryBreakdown->fetch_assoc()): 
                    $percentage = $summary['total'] > 0 ? ($cat['total'] / $summary['total']) * 100 : 0;
                    $color = $categoryColors[$colorIndex % count($categoryColors)];
                    $colorIndex++;
            ?>
            <div>
                <div class="flex justify-between items-center mb-1">
                    <span class="text-sm font-semibold text-gray-700"><?php echo htmlspecialchars($cat['category']); ?></span>
                    <span class="text-sm font-bold text-gray-900"><?php echo formatCurrency($cat['total']); ?></span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="<?php echo $color; ?> h-2 rounded-full transition-all" style="width: <?php echo min(100, $percentage); ?>%"></div>
                </div>
                <div class="flex justify-between mt-1">
                    <span class="text-xs text-gray-500"><?php echo $cat['count']; ?> transactions</span>
                    <span class="text-xs text-gray-500"><?php echo round($percentage, 1); ?>%</span>
                </div>
            </div>
            <?php endwhile; else: ?>
            <p class="text-center text-gray-400 py-4">No expenses yet</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Expenses Table -->
<div class="expense-card">
    <div class="mb-4">
        <h3 class="text-lg font-bold text-gray-900">Expense Records</h3>
        <p class="text-sm text-gray-600">Showing <?php echo $expenses->num_rows; ?> expenses</p>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 border-b-2 border-gray-200">
                <tr>
                    <th class="text-left py-3 px-4 text-sm font-bold text-gray-700">Date</th>
                    <th class="text-left py-3 px-4 text-sm font-bold text-gray-700">Category</th>
                    <th class="text-left py-3 px-4 text-sm font-bold text-gray-700">Description</th>
                    <th class="text-left py-3 px-4 text-sm font-bold text-gray-700">Receipt</th>
                    <th class="text-right py-3 px-4 text-sm font-bold text-gray-700">Amount</th>
                    <th class="text-left py-3 px-4 text-sm font-bold text-gray-700">Added By</th>
                    <th class="text-center py-3 px-4 text-sm font-bold text-gray-700">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($expenses->num_rows > 0): 
                    while ($expense = $expenses->fetch_assoc()): 
                ?>
                <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                    <td class="py-3 px-4">
                        <span class="text-sm font-semibold text-gray-900"><?php echo date('M d, Y', strtotime($expense['expense_date'])); ?></span>
                    </td>
                    <td class="py-3 px-4">
                        <span class="category-badge bg-blue-100 text-blue-800">
                            <i class="fas fa-tag"></i>
                            <?php echo htmlspecialchars($expense['category']); ?>
                        </span>
                    </td>
                    <td class="py-3 px-4">
                        <p class="text-sm text-gray-900 font-medium"><?php echo htmlspecialchars($expense['description']); ?></p>
                    </td>
                    <td class="py-3 px-4">
                        <?php if ($expense['receipt_number']): ?>
                        <span class="text-xs font-mono bg-gray-100 px-2 py-1 rounded"><?php echo htmlspecialchars($expense['receipt_number']); ?></span>
                        <?php else: ?>
                        <span class="text-xs text-gray-400">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="py-3 px-4 text-right">
                        <span class="text-lg font-bold text-red-600"><?php echo formatCurrency($expense['amount']); ?></span>
                    </td>
                    <td class="py-3 px-4">
                        <span class="text-sm text-gray-700"><?php echo htmlspecialchars($expense['added_by']); ?></span>
                    </td>
                    <td class="py-3 px-4 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <button onclick='editExpense(<?php echo json_encode($expense); ?>)' 
                                    class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition" 
                                    title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="deleteExpense(<?php echo $expense['id']; ?>, '<?php echo htmlspecialchars(addslashes($expense['description'])); ?>')" 
                                    class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition" 
                                    title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                <tr>
                    <td colspan="7" class="text-center py-20">
                        <i class="fas fa-money-bill-wave text-6xl text-gray-300 mb-4"></i>
                        <p class="text-xl text-gray-500 font-semibold mb-2">No expenses recorded</p>
                        <p class="text-gray-400 text-sm">Start tracking your expenses by adding one</p>
                        <button onclick="openExpenseModal()" 
                                class="mt-4 px-6 py-3 rounded-lg font-semibold text-white transition hover:opacity-90"
                                style="background-color: <?php echo $settings['primary_color']; ?>">
                            <i class="fas fa-plus mr-2"></i>Add First Expense
                        </button>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Expense Modal - FIXED FOR SCROLLING -->
<div id="expenseModal" class="modal-backdrop fixed inset-0 bg-black/50 z-50 hidden" style="backdrop-filter: blur(4px);">
    <div class="modal-content my-8">
        <div class="bg-white rounded-2xl shadow-2xl">
            <div class="p-6 rounded-t-2xl text-white" style="background: linear-gradient(135deg, <?php echo $settings['primary_color']; ?> 0%, <?php echo $settings['primary_color']; ?>dd 100%)">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="text-2xl font-bold" id="modalTitle">Add Expense</h3>
                        <p class="text-white/80 text-sm">Record a business expense</p>
                    </div>
                    <button onclick="closeExpenseModal()" class="text-white/80 hover:text-white transition">
                        <i class="fas fa-times text-2xl"></i>
                    </button>
                </div>
            </div>

            <form id="expenseForm" class="p-6">
                <input type="hidden" id="expenseId" name="id">
                <input type="hidden" id="expenseAction" name="action" value="add_expense">

                <div class="modal-form-container">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Category *</label>
                            <select name="category" id="expenseCategory" required 
                                    class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:outline-none transition"
                                    style="focus:border-color: <?php echo $settings['primary_color']; ?>">
                                <option value="">Select Category</option>
                                <option value="Utilities">Utilities</option>
                                <option value="Rent">Rent</option>
                                <option value="Salaries">Salaries</option>
                                <option value="Supplies">Supplies</option>
                                <option value="Transport">Transport</option>
                                <option value="Marketing">Marketing</option>
                                <option value="Maintenance">Maintenance</option>
                                <option value="Insurance">Insurance</option>
                                <option value="Licenses">Licenses & Permits</option>
                                <option value="Equipment">Equipment</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Amount (<?php echo $settings['currency']; ?>) *</label>
                            <input type="number" step="0.01" name="amount" id="expenseAmount" required min="0.01"
                                   class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:outline-none transition text-lg font-semibold"
                                   style="focus:border-color: <?php echo $settings['primary_color']; ?>"
                                   placeholder="0.00">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Date *</label>
                            <input type="date" name="expense_date" id="expenseDate" value="<?php echo date('Y-m-d'); ?>" required 
                                   class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:outline-none transition"
                                   style="focus:border-color: <?php echo $settings['primary_color']; ?>"
                                   max="<?php echo date('Y-m-d'); ?>">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Receipt Number</label>
                            <input type="text" name="receipt_number" id="expenseReceipt"
                                   class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:outline-none transition"
                                   style="focus:border-color: <?php echo $settings['primary_color']; ?>"
                                   placeholder="Optional">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Description *</label>
                            <textarea name="description" id="expenseDescription" rows="3" required 
                                      class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:outline-none transition"
                                      style="focus:border-color: <?php echo $settings['primary_color']; ?>"
                                      placeholder="What was this expense for?"></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-actions-sticky">
                    <div class="flex gap-3">
                        <button type="button" onclick="closeExpenseModal()" 
                                class="flex-1 px-6 py-3 border-2 border-gray-300 rounded-lg font-bold text-gray-700 hover:bg-gray-50 transition">
                            Cancel
                        </button>
                        <button type="submit" id="submitBtn"
                                class="flex-1 px-6 py-3 rounded-lg font-bold text-white transition hover:opacity-90 shadow-lg"
                                style="background: linear-gradient(135deg, <?php echo $settings['primary_color']; ?> 0%, <?php echo $settings['primary_color']; ?>dd 100%)">
                            <i class="fas fa-save mr-2"></i>Save Expense
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const primaryColor = '<?php echo $settings['primary_color']; ?>';

function openExpenseModal() {
    const modal = document.getElementById('expenseModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex', 'items-start');
    document.getElementById('modalTitle').textContent = 'Add Expense';
    document.getElementById('expenseAction').value = 'add_expense';
    document.getElementById('expenseForm').reset();
    document.getElementById('expenseId').value = '';
    document.getElementById('expenseDate').value = '<?php echo date('Y-m-d'); ?>';
    document.body.style.overflow = 'hidden';
}

function closeExpenseModal() {
    const modal = document.getElementById('expenseModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex', 'items-start');
    document.body.style.overflow = '';
}

function editExpense(expense) {
    const modal = document.getElementById('expenseModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex', 'items-start');
    document.getElementById('modalTitle').textContent = 'Edit Expense';
    document.getElementById('expenseAction').value = 'update_expense';
    document.getElementById('expenseId').value = expense.id;
    document.getElementById('expenseCategory').value = expense.category;
    document.getElementById('expenseAmount').value = expense.amount;
    document.getElementById('expenseDate').value = expense.expense_date;
    document.getElementById('expenseReceipt').value = expense.receipt_number || '';
    document.getElementById('expenseDescription').value = expense.description;
    document.body.style.overflow = 'hidden';
}

function deleteExpense(id, description) {
    if (!confirm(`Delete expense: "${description}"?\n\nThis action cannot be undone.`)) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_expense');
    formData.append('id', id);

    fetch('', { 
        method: 'POST', 
        body: formData 
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast('Expense deleted successfully', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message || 'Failed to delete expense', 'error');
        }
    })
    .catch(err => {
        showToast('Connection error. Please try again.', 'error');
        console.error(err);
    });
}

document.getElementById('expenseForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const submitBtn = document.getElementById('submitBtn');
    const originalContent = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';
    
    const formData = new FormData(this);
    
    // Debug: Log form data
    console.log('Form Data:');
    for (let pair of formData.entries()) {
        console.log(pair[0] + ': ' + pair[1]);
    }

    fetch(window.location.href, { 
        method: 'POST', 
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => {
        console.log('Response status:', res.status);
        console.log('Response headers:', res.headers);
        
        if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
        }
        
        const contentType = res.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            return res.text().then(text => {
                console.error('Response is not JSON:', text);
                throw new Error('Server returned non-JSON response');
            });
        }
        
        return res.json();
    })
    .then(data => {
        console.log('Response data:', data);
        
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message || 'Failed to save expense', 'error');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalContent;
        }
    })
    .catch(err => {
        console.error('Error details:', err);
        showToast('Connection error: ' + err.message, 'error');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalContent;
    });
});

function showToast(message, type) {
    const colors = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        info: 'bg-blue-500'
    };
    
    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 ${colors[type]} text-white px-6 py-3 rounded-lg shadow-lg z-50`;
    toast.style.animation = 'slideIn 0.3s ease-out';
    toast.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'} mr-2"></i>${message}`;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease-out';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeExpenseModal();
    }
});

// Close modal when clicking outside
document.getElementById('expenseModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeExpenseModal();
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
</style>

<?php include 'footer.php'; ?>
