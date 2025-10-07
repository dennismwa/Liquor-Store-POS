<?php
require_once 'config.php';
requireOwner();

$page_title = 'Dashboard';

// Get settings
$settings = getSettings();

// Get today's stats
$today = date('Y-m-d');
$today_sales_query = $conn->query("SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total FROM sales WHERE DATE(sale_date) = '$today'");
$today_sales = $today_sales_query ? $today_sales_query->fetch_assoc() : ['count' => 0, 'total' => 0];

// Get yesterday's sales for comparison
$yesterday = date('Y-m-d', strtotime('-1 day'));
$yesterday_sales = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM sales WHERE DATE(sale_date) = '$yesterday'")->fetch_assoc()['total'];
$sales_change = $yesterday_sales > 0 ? (($today_sales['total'] - $yesterday_sales) / $yesterday_sales) * 100 : 0;

// Get this month's stats
$this_month = date('Y-m');
$month_sales_query = $conn->query("SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total FROM sales WHERE DATE_FORMAT(sale_date, '%Y-%m') = '$this_month'");
$month_sales = $month_sales_query ? $month_sales_query->fetch_assoc() : ['count' => 0, 'total' => 0];

// Get last month for comparison
$last_month = date('Y-m', strtotime('-1 month'));
$last_month_sales = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM sales WHERE DATE_FORMAT(sale_date, '%Y-%m') = '$last_month'")->fetch_assoc()['total'];
$month_change = $last_month_sales > 0 ? (($month_sales['total'] - $last_month_sales) / $last_month_sales) * 100 : 0;

// Get low stock products
$low_stock_query = $conn->query("SELECT COUNT(*) as count FROM products WHERE stock_quantity <= reorder_level AND status = 'active'");
$low_stock = $low_stock_query ? $low_stock_query->fetch_assoc() : ['count' => 0];

// Get out of stock
$out_of_stock = $conn->query("SELECT COUNT(*) as count FROM products WHERE stock_quantity = 0 AND status = 'active'")->fetch_assoc()['count'];

// Get total products
$total_products_query = $conn->query("SELECT COUNT(*) as count FROM products WHERE status = 'active'");
$total_products = $total_products_query ? $total_products_query->fetch_assoc() : ['count' => 0];

// Get total inventory value
$inventory_value = $conn->query("SELECT COALESCE(SUM(stock_quantity * selling_price), 0) as value FROM products WHERE status = 'active'")->fetch_assoc()['value'];

// Get active users count
$active_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE status = 'active'")->fetch_assoc()['count'];

// Get recent sales (last 5)
$recent_sales = $conn->query("SELECT s.*, u.name as seller_name FROM sales s LEFT JOIN users u ON s.user_id = u.id ORDER BY s.sale_date DESC LIMIT 5");

// Get top products (this month)
$top_products = $conn->query("SELECT p.name, p.selling_price, SUM(si.quantity) as total_sold, SUM(si.subtotal) as total_revenue 
                              FROM sale_items si 
                              JOIN products p ON si.product_id = p.id 
                              JOIN sales s ON si.sale_id = s.id 
                              WHERE DATE_FORMAT(s.sale_date, '%Y-%m') = '$this_month' 
                              GROUP BY p.id 
                              ORDER BY total_sold DESC 
                              LIMIT 5");

// Get daily sales for chart (last 7 days)
$daily_sales = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $result = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM sales WHERE DATE(sale_date) = '$date'");
    $row = $result ? $result->fetch_assoc() : ['total' => 0];
    $daily_sales[] = [
        'date' => date('D', strtotime($date)),
        'full_date' => date('M d', strtotime($date)),
        'total' => $row['total']
    ];
}

// Get hourly sales today
$hourly_sales = [];
for ($hour = 0; $hour < 24; $hour++) {
    $result = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as total 
                           FROM sales 
                           WHERE DATE(sale_date) = '$today' 
                           AND HOUR(sale_date) = $hour");
    $row = $result ? $result->fetch_assoc() : ['total' => 0];
    $hourly_sales[] = [
        'hour' => str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00',
        'total' => $row['total']
    ];
}

// Get payment methods breakdown
$payment_breakdown = $conn->query("SELECT 
    payment_method,
    COUNT(*) as count,
    COALESCE(SUM(total_amount), 0) as total
    FROM sales 
    WHERE DATE_FORMAT(sale_date, '%Y-%m') = '$this_month'
    GROUP BY payment_method");

// Get low stock products list
$low_stock_products = $conn->query("SELECT p.*, c.name as category_name 
                                    FROM products p 
                                    LEFT JOIN categories c ON p.category_id = c.id 
                                    WHERE p.stock_quantity <= p.reorder_level 
                                    AND p.status = 'active' 
                                    ORDER BY p.stock_quantity ASC 
                                    LIMIT 5");

// Get recent expenses (this month)
$recent_expenses = $conn->query("SELECT * FROM expenses WHERE DATE_FORMAT(expense_date, '%Y-%m') = '$this_month' ORDER BY expense_date DESC LIMIT 5");
$total_expenses = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE DATE_FORMAT(expense_date, '%Y-%m') = '$this_month'")->fetch_assoc()['total'];

// Calculate profit
$profit = $month_sales['total'] - $total_expenses;
$profit_margin = $month_sales['total'] > 0 ? ($profit / $month_sales['total']) * 100 : 0;

include 'header.php';
?>

<style>
.stat-card {
    background: white;
    border-radius: 1rem;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: 2px solid transparent;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(0,0,0,0.15);
    border-color: <?php echo $settings['primary_color']; ?>20;
}

.stat-icon {
    width: 3.5rem;
    height: 3.5rem;
    border-radius: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.quick-action-btn {
    background: white;
    border: 2px solid #e2e8f0;
    border-radius: 1rem;
    padding: 1.25rem;
    cursor: pointer;
    transition: all 0.3s;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.75rem;
}

.quick-action-btn:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.1);
    border-color: <?php echo $settings['primary_color']; ?>;
}

.quick-action-icon {
    width: 3.5rem;
    height: 3.5rem;
    border-radius: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.trend-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.625rem;
    border-radius: 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
}

.trend-up {
    background: #dcfce7;
    color: #16a34a;
}

.trend-down {
    background: #fee2e2;
    color: #dc2626;
}

.activity-item {
    padding: 1rem;
    border-radius: 0.75rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    transition: all 0.2s;
}

.activity-item:hover {
    background: #f1f5f9;
    border-color: <?php echo $settings['primary_color']; ?>40;
}

.chart-container {
    position: relative;
    height: 300px;
}

@media (max-width: 768px) {
    .stat-card {
        padding: 1rem;
    }
    
    .stat-icon {
        width: 3rem;
        height: 3rem;
        font-size: 1.25rem;
    }
    
    .quick-action-btn {
        padding: 1rem;
    }
    
    .quick-action-icon {
        width: 3rem;
        height: 3rem;
        font-size: 1.25rem;
    }
    
    .chart-container {
        height: 250px;
    }
}

.pulse {
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

.gradient-bg {
    background: linear-gradient(135deg, <?php echo $settings['primary_color']; ?> 0%, <?php echo $settings['primary_color']; ?>dd 100%);
}
</style>

<!-- Welcome Section -->
<div class="bg-white rounded-2xl shadow-sm p-6 mb-6 gradient-bg text-white">
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold mb-2">
                Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?> 👋
            </h1>
            <p class="text-white/90">Here's what's happening with your business today</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="text-right">
                <p class="text-sm text-white/80">Current Time</p>
                <p class="text-xl font-bold" id="currentDateTime"></p>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="bg-white rounded-2xl shadow-sm p-6 mb-6">
    <h2 class="text-xl font-bold text-gray-900 mb-4">Quick Actions</h2>
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
        <a href="/pos.php" class="quick-action-btn">
            <div class="quick-action-icon bg-green-100">
                <i class="fas fa-cash-register text-green-600"></i>
            </div>
            <span class="font-semibold text-sm text-gray-900">New Sale</span>
        </a>
        
        <button onclick="openAddProductModal()" class="quick-action-btn">
            <div class="quick-action-icon bg-blue-100">
                <i class="fas fa-plus-circle text-blue-600"></i>
            </div>
            <span class="font-semibold text-sm text-gray-900">Add Product</span>
        </button>
        
        <a href="/inventory.php" class="quick-action-btn">
            <div class="quick-action-icon bg-purple-100">
                <i class="fas fa-boxes text-purple-600"></i>
            </div>
            <span class="font-semibold text-sm text-gray-900">Manage Stock</span>
        </a>
        
        <a href="/sales.php" class="quick-action-btn">
            <div class="quick-action-icon bg-orange-100">
                <i class="fas fa-receipt text-orange-600"></i>
            </div>
            <span class="font-semibold text-sm text-gray-900">View Sales</span>
        </a>
        
        <a href="/reports.php" class="quick-action-btn">
            <div class="quick-action-icon bg-pink-100">
                <i class="fas fa-chart-bar text-pink-600"></i>
            </div>
            <span class="font-semibold text-sm text-gray-900">Reports</span>
        </a>
        
        <button onclick="openAddExpenseModal()" class="quick-action-btn">
            <div class="quick-action-icon bg-red-100">
                <i class="fas fa-money-bill-wave text-red-600"></i>
            </div>
            <span class="font-semibold text-sm text-gray-900">Add Expense</span>
        </button>
    </div>
</div>

<!-- Main Stats -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-6">
    <!-- Today's Sales -->
    <div class="stat-card">
        <div class="flex items-center justify-between mb-3">
            <div class="stat-icon bg-blue-100">
                <i class="fas fa-calendar-day text-blue-600"></i>
            </div>
            <div class="trend-badge <?php echo $sales_change >= 0 ? 'trend-up' : 'trend-down'; ?>">
                <i class="fas fa-arrow-<?php echo $sales_change >= 0 ? 'up' : 'down'; ?>"></i>
                <?php echo abs(round($sales_change, 1)); ?>%
            </div>
        </div>
        <p class="text-sm text-gray-600 mb-1">Today's Sales</p>
        <h3 class="text-2xl md:text-3xl font-bold text-gray-900 mb-1">
            <?php echo formatCurrency($today_sales['total']); ?>
        </h3>
        <p class="text-xs text-gray-500"><?php echo $today_sales['count']; ?> transactions</p>
    </div>

    <!-- This Month -->
    <div class="stat-card">
        <div class="flex items-center justify-between mb-3">
            <div class="stat-icon bg-green-100">
                <i class="fas fa-calendar-alt text-green-600"></i>
            </div>
            <div class="trend-badge <?php echo $month_change >= 0 ? 'trend-up' : 'trend-down'; ?>">
                <i class="fas fa-arrow-<?php echo $month_change >= 0 ? 'up' : 'down'; ?>"></i>
                <?php echo abs(round($month_change, 1)); ?>%
            </div>
        </div>
        <p class="text-sm text-gray-600 mb-1">This Month</p>
        <h3 class="text-2xl md:text-3xl font-bold text-gray-900 mb-1">
            <?php echo formatCurrency($month_sales['total']); ?>
        </h3>
        <p class="text-xs text-gray-500"><?php echo $month_sales['count']; ?> transactions</p>
    </div>

    <!-- Profit -->
    <div class="stat-card">
        <div class="flex items-center justify-between mb-3">
            <div class="stat-icon <?php echo $profit >= 0 ? 'bg-emerald-100' : 'bg-red-100'; ?>">
                <i class="fas fa-chart-line <?php echo $profit >= 0 ? 'text-emerald-600' : 'text-red-600'; ?>"></i>
            </div>
            <div class="trend-badge <?php echo $profit >= 0 ? 'trend-up' : 'trend-down'; ?>">
                <?php echo round($profit_margin, 1); ?>%
            </div>
        </div>
        <p class="text-sm text-gray-600 mb-1">Net Profit</p>
        <h3 class="text-2xl md:text-3xl font-bold <?php echo $profit >= 0 ? 'text-emerald-600' : 'text-red-600'; ?> mb-1">
            <?php echo formatCurrency($profit); ?>
        </h3>
        <p class="text-xs text-gray-500">Revenue - Expenses</p>
    </div>

    <!-- Inventory Value -->
    <div class="stat-card">
        <div class="flex items-center justify-between mb-3">
            <div class="stat-icon bg-purple-100">
                <i class="fas fa-warehouse text-purple-600"></i>
            </div>
        </div>
        <p class="text-sm text-gray-600 mb-1">Inventory Value</p>
        <h3 class="text-2xl md:text-3xl font-bold text-gray-900 mb-1">
            <?php echo formatCurrency($inventory_value); ?>
        </h3>
        <p class="text-xs text-gray-500"><?php echo $total_products['count']; ?> products</p>
    </div>
</div>

<!-- Secondary Stats -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="stat-card text-center">
        <div class="stat-icon bg-orange-100 mx-auto mb-3">
            <i class="fas fa-exclamation-triangle text-orange-600"></i>
        </div>
        <p class="text-sm text-gray-600 mb-1">Low Stock</p>
        <h4 class="text-2xl font-bold text-orange-600"><?php echo $low_stock['count']; ?></h4>
    </div>
    
    <div class="stat-card text-center">
        <div class="stat-icon bg-red-100 mx-auto mb-3">
            <i class="fas fa-times-circle text-red-600 <?php echo $out_of_stock > 0 ? 'pulse' : ''; ?>"></i>
        </div>
        <p class="text-sm text-gray-600 mb-1">Out of Stock</p>
        <h4 class="text-2xl font-bold text-red-600"><?php echo $out_of_stock; ?></h4>
    </div>
    
    <div class="stat-card text-center">
        <div class="stat-icon bg-blue-100 mx-auto mb-3">
            <i class="fas fa-users text-blue-600"></i>
        </div>
        <p class="text-sm text-gray-600 mb-1">Active Users</p>
        <h4 class="text-2xl font-bold text-blue-600"><?php echo $active_users; ?></h4>
    </div>
    
    <div class="stat-card text-center">
        <div class="stat-icon bg-indigo-100 mx-auto mb-3">
            <i class="fas fa-money-bill-wave text-indigo-600"></i>
        </div>
        <p class="text-sm text-gray-600 mb-1">Expenses</p>
        <h4 class="text-2xl font-bold text-indigo-600"><?php echo formatCurrency($total_expenses); ?></h4>
    </div>
</div>

<!-- Charts Section -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Sales Trend -->
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-900">7-Day Sales Trend</h3>
            <div class="text-right">
                <p class="text-xs text-gray-500">Average</p>
                <p class="text-sm font-bold text-gray-900">
                    <?php 
                    $avg_sales = array_sum(array_column($daily_sales, 'total')) / count($daily_sales);
                    echo formatCurrency($avg_sales);
                    ?>
                </p>
            </div>
        </div>
        <div class="chart-container">
            <canvas id="salesChart"></canvas>
        </div>
    </div>

    <!-- Hourly Sales Today -->
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-900">Today's Hourly Sales</h3>
            <div class="text-right">
                <p class="text-xs text-gray-500">Peak Hour</p>
                <p class="text-sm font-bold text-gray-900">
                    <?php 
                    $peak_hour = array_reduce($hourly_sales, function($carry, $item) {
                        return $item['total'] > $carry['total'] ? $item : $carry;
                    }, ['hour' => '--:--', 'total' => 0]);
                    echo $peak_hour['hour'];
                    ?>
                </p>
            </div>
        </div>
        <div class="chart-container">
            <canvas id="hourlyChart"></canvas>
        </div>
    </div>
</div>

<!-- Activity Sections -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <!-- Top Products -->
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-900">Top Products</h3>
            <a href="/reports.php" class="text-sm font-semibold hover:underline" style="color: <?php echo $settings['primary_color']; ?>">View All</a>
        </div>
        <div class="space-y-3">
            <?php 
            $rank = 1;
            if ($top_products && $top_products->num_rows > 0):
                while ($product = $top_products->fetch_assoc()): 
            ?>
            <div class="activity-item">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center font-bold text-sm text-white" style="background: <?php echo $settings['primary_color']; ?>">
                        <?php echo $rank++; ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-sm text-gray-900 truncate"><?php echo htmlspecialchars($product['name']); ?></p>
                        <p class="text-xs text-gray-500"><?php echo $product['total_sold']; ?> sold</p>
                    </div>
                    <p class="text-sm font-bold" style="color: <?php echo $settings['primary_color']; ?>">
                        <?php echo formatCurrency($product['total_revenue']); ?>
                    </p>
                </div>
            </div>
            <?php endwhile; else: ?>
            <p class="text-center text-gray-400 py-8">No sales data yet</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Sales -->
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-900">Recent Sales</h3>
            <a href="/sales.php" class="text-sm font-semibold hover:underline" style="color: <?php echo $settings['primary_color']; ?>">View All</a>
        </div>
        <div class="space-y-3">
            <?php if ($recent_sales && $recent_sales->num_rows > 0):
                while ($sale = $recent_sales->fetch_assoc()): 
            ?>
            <div class="activity-item">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center">
                        <i class="fas fa-receipt text-white"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-sm text-gray-900"><?php echo htmlspecialchars($sale['sale_number']); ?></p>
                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars($sale['seller_name']); ?> • <?php echo date('h:i A', strtotime($sale['sale_date'])); ?></p>
                    </div>
                    <p class="text-sm font-bold text-green-600">
                        <?php echo formatCurrency($sale['total_amount']); ?>
                    </p>
                </div>
            </div>
            <?php endwhile; else: ?>
            <p class="text-center text-gray-400 py-8">No sales yet</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Low Stock Alerts -->
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-900">Stock Alerts</h3>
            <a href="/stock-alerts.php" class="text-sm font-semibold hover:underline" style="color: <?php echo $settings['primary_color']; ?>">View All</a>
        </div>
        <div class="space-y-3">
            <?php if ($low_stock_products && $low_stock_products->num_rows > 0):
                while ($product = $low_stock_products->fetch_assoc()): 
                    $urgency = $product['stock_quantity'] == 0 ? 'critical' : 'warning';
            ?>
            <div class="activity-item border-l-4 <?php echo $urgency === 'critical' ? 'border-red-500' : 'border-orange-500'; ?>">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg <?php echo $urgency === 'critical' ? 'bg-red-100' : 'bg-orange-100'; ?> flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle <?php echo $urgency === 'critical' ? 'text-red-600' : 'text-orange-600'; ?>"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-sm text-gray-900 truncate"><?php echo htmlspecialchars($product['name']); ?></p>
                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars($product['category_name']); ?></p>
                    </div>
                    <p class="text-lg font-bold <?php echo $urgency === 'critical' ? 'text-red-600' : 'text-orange-600'; ?>">
                        <?php echo $product['stock_quantity']; ?>
                    </p>
                </div>
            </div>
            <?php endwhile; else: ?>
            <div class="text-center py-8">
                <i class="fas fa-check-circle text-4xl text-green-500 mb-2"></i>
                <p class="text-sm text-gray-500">All stock levels good!</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Payment Methods & Recent Expenses -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Payment Methods Breakdown -->
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Payment Methods (This Month)</h3>
        <div class="chart-container">
            <canvas id="paymentChart"></canvas>
        </div>
        <div class="grid grid-cols-3 gap-4 mt-4">
            <?php 
            $payment_data = [];
            while ($payment = $payment_breakdown->fetch_assoc()) {
                $payment_data[] = $payment;
            }
            
            $payment_colors = [
                'cash' => ['bg' => 'bg-green-100', 'text' => 'text-green-600', 'icon' => 'money-bill-wave'],
                'mpesa' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-600', 'icon' => 'mobile-alt'],
                'mpesa_till' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-600', 'icon' => 'store'],
                'card' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-600', 'icon' => 'credit-card']
            ];
            
            foreach ($payment_data as $payment):
                $colors = $payment_colors[$payment['payment_method']] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-600', 'icon' => 'wallet'];
            ?>
            <div class="text-center p-3 <?php echo $colors['bg']; ?> rounded-lg">
                <i class="fas fa-<?php echo $colors['icon']; ?> <?php echo $colors['text']; ?> text-2xl mb-2"></i>
                <p class="text-xs font-medium text-gray-600 uppercase mb-1"><?php echo str_replace('_', ' ', $payment['payment_method']); ?></p>
                <p class="text-sm font-bold <?php echo $colors['text']; ?>"><?php echo formatCurrency($payment['total']); ?></p>
                <p class="text-xs text-gray-500"><?php echo $payment['count']; ?> trans.</p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Recent Expenses -->
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-900">Recent Expenses</h3>
            <a href="/expenses.php" class="text-sm font-semibold hover:underline" style="color: <?php echo $settings['primary_color']; ?>">View All</a>
        </div>
        <div class="space-y-3">
            <?php if ($recent_expenses && $recent_expenses->num_rows > 0):
                while ($expense = $recent_expenses->fetch_assoc()): 
            ?>
            <div class="activity-item">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-red-500 to-pink-600 flex items-center justify-center">
                        <i class="fas fa-money-bill-wave text-white"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-sm text-gray-900"><?php echo htmlspecialchars($expense['category']); ?></p>
                        <p class="text-xs text-gray-500 truncate"><?php echo htmlspecialchars($expense['description']); ?></p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-bold text-red-600">
                            <?php echo formatCurrency($expense['amount']); ?>
                        </p>
                        <p class="text-xs text-gray-500"><?php echo date('M d', strtotime($expense['expense_date'])); ?></p>
                    </div>
                </div>
            </div>
            <?php endwhile; else: ?>
            <p class="text-center text-gray-400 py-8">No expenses yet</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Quick Add Product Modal -->
<div id="quickAddProductModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4" style="backdrop-filter: blur(4px);">
    <div class="bg-white rounded-2xl w-full max-w-lg shadow-2xl">
        <div class="p-6 gradient-bg text-white rounded-t-2xl">
            <div class="flex justify-between items-center">
                <h3 class="text-xl font-bold">Quick Add Product</h3>
                <button onclick="closeQuickAddProduct()" class="text-white/80 hover:text-white">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>
        <div class="p-6">
            <p class="text-center text-gray-600">
                For full product management, please visit the 
                <a href="/products.php" class="font-semibold hover:underline" style="color: <?php echo $settings['primary_color']; ?>">Products Page</a>
            </p>
        </div>
    </div>
</div>

<!-- Quick Add Expense Modal -->
<div id="quickAddExpenseModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4" style="backdrop-filter: blur(4px);">
    <div class="bg-white rounded-2xl w-full max-w-lg shadow-2xl">
        <div class="p-6 gradient-bg text-white rounded-t-2xl">
            <div class="flex justify-between items-center">
                <h3 class="text-xl font-bold">Quick Add Expense</h3>
                <button onclick="closeQuickAddExpense()" class="text-white/80 hover:text-white">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>
        <div class="p-6">
            <p class="text-center text-gray-600">
                For expense management, please visit the 
                <a href="/expenses.php" class="font-semibold hover:underline" style="color: <?php echo $settings['primary_color']; ?>">Expenses Page</a>
            </p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const primaryColor = '<?php echo $settings['primary_color']; ?>';
const dailySalesData = <?php echo json_encode($daily_sales); ?>;
const hourlySalesData = <?php echo json_encode($hourly_sales); ?>;
const paymentData = <?php echo json_encode($payment_data); ?>;

// Update Date/Time
function updateDateTime() {
    const now = new Date();
    const options = { 
        hour: '2-digit', 
        minute: '2-digit',
        hour12: true 
    };
    document.getElementById('currentDateTime').textContent = now.toLocaleTimeString('en-US', options);
}
updateDateTime();
setInterval(updateDateTime, 1000);

// Sales Trend Chart
const salesCtx = document.getElementById('salesChart');
if (salesCtx) {
    new Chart(salesCtx, {
        type: 'line',
        data: {
            labels: dailySalesData.map(d => d.date),
            datasets: [{
                label: 'Sales',
                data: dailySalesData.map(d => d.total),
                borderColor: primaryColor,
                backgroundColor: primaryColor + '20',
                tension: 0.4,
                fill: true,
                pointRadius: 4,
                pointHoverRadius: 6,
                pointBackgroundColor: primaryColor,
                pointBorderColor: '#fff',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        title: function(context) {
                            return dailySalesData[context[0].dataIndex].full_date;
                        },
                        label: function(context) {
                            return '<?php echo $settings['currency']; ?> ' + context.parsed.y.toFixed(2);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '<?php echo $settings['currency']; ?> ' + value.toLocaleString();
                        }
                    },
                    grid: {
                        color: '#f1f5f9'
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
}

// Hourly Sales Chart
const hourlyCtx = document.getElementById('hourlyChart');
if (hourlyCtx) {
    new Chart(hourlyCtx, {
        type: 'bar',
        data: {
            labels: hourlySalesData.map(d => d.hour),
            datasets: [{
                label: 'Hourly Sales',
                data: hourlySalesData.map(d => d.total),
                backgroundColor: primaryColor + '40',
                borderColor: primaryColor,
                borderWidth: 2,
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return '<?php echo $settings['currency']; ?> ' + context.parsed.y.toFixed(2);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '<?php echo $settings['currency']; ?> ' + value.toLocaleString();
                        }
                    },
                    grid: {
                        color: '#f1f5f9'
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
}

// Payment Methods Chart
const paymentCtx = document.getElementById('paymentChart');
if (paymentCtx) {
    const paymentColors = {
        'cash': '#10b981',
        'mpesa': '#3b82f6',
        'mpesa_till': '#8b5cf6',
        'card': '#6b7280'
    };
    
    new Chart(paymentCtx, {
        type: 'doughnut',
        data: {
            labels: paymentData.map(p => p.payment_method.replace('_', ' ').toUpperCase()),
            datasets: [{
                data: paymentData.map(p => p.total),
                backgroundColor: paymentData.map(p => paymentColors[p.payment_method] || '#6b7280'),
                borderWidth: 3,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        font: { size: 12, weight: 'bold' }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            return context.label + ': <?php echo $settings['currency']; ?> ' + context.parsed.toFixed(2) + ' (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });
}

// Quick Action Functions
function openAddProductModal() {
    document.getElementById('quickAddProductModal').classList.remove('hidden');
    document.getElementById('quickAddProductModal').classList.add('flex');
}

function closeQuickAddProduct() {
    document.getElementById('quickAddProductModal').classList.add('hidden');
    document.getElementById('quickAddProductModal').classList.remove('flex');
}

function openAddExpenseModal() {
    document.getElementById('quickAddExpenseModal').classList.remove('hidden');
    document.getElementById('quickAddExpenseModal').classList.add('flex');
}

function closeQuickAddExpense() {
    document.getElementById('quickAddExpenseModal').classList.add('hidden');
    document.getElementById('quickAddExpenseModal').classList.remove('flex');
}

// ESC key to close modals
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeQuickAddProduct();
        closeQuickAddExpense();
    }
});
</script>

<?php include 'footer.php'; ?>
