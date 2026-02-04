<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: dashboard.php');
    exit();
}

$total_revenue_result = mysqli_query($conn, "SELECT SUM(total) as revenue FROM orders WHERE status='delivered'");
$total_revenue = mysqli_fetch_assoc($total_revenue_result)['revenue'] ?? 0;

$monthly_revenue_result = mysqli_query($conn, "SELECT SUM(total) as revenue FROM orders WHERE status='delivered' AND MONTH(order_date) = MONTH(CURRENT_DATE())");
$monthly_revenue = mysqli_fetch_assoc($monthly_revenue_result)['revenue'] ?? 0;

$total_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM orders"))['count'];
$pending_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM orders WHERE status='pending'"))['count'];
$delivered_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM orders WHERE status='delivered'"))['count'];

$total_customers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role='customer'"))['count'];
$total_products = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM products"))['count'];
$low_stock_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM products WHERE stock < 50"))['count'];

$top_products = mysqli_query($conn, "SELECT p.name, p.category, SUM(oi.quantity) as total_sold, SUM(oi.quantity * oi.price) as revenue 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    GROUP BY p.id 
    ORDER BY total_sold DESC LIMIT 5");

$rdc_performance = mysqli_query($conn, "SELECT 
    p.rdc_location,
    COUNT(DISTINCT o.id) as total_orders,
    SUM(o.total) as revenue,
    AVG(TIMESTAMPDIFF(HOUR, o.order_date, d.delivered_date)) as avg_delivery_hours
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    LEFT JOIN deliveries d ON o.id = d.order_id
    WHERE o.status = 'delivered'
    GROUP BY p.rdc_location
    ORDER BY revenue DESC");

$recent_activities = mysqli_query($conn, "SELECT o.id, o.order_date, o.total, o.status, u.name as customer_name 
    FROM orders o 
    JOIN users u ON o.customer_id = u.id 
    ORDER BY o.order_date DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard - ISDN</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <script src="theme-currency.js"></script>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-chart-bar"></i> Analytics & Reporting</h1>
            <p>Real-time insights and performance metrics across all RDCs</p>
            <div style="margin-top: 1rem;">
                <a href="advanced-analytics.php" class="btn-primary">
                    <i class="fas fa-chart-pie"></i> Advanced Analytics with Charts
                </a>
                <a href="export-excel.php?type=orders" class="btn-secondary">
                    <i class="fas fa-file-excel"></i> Export Data
                </a>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
                <div class="stat-info">
                    <h3 class="price-display" data-price="<?php echo $total_revenue; ?>">$<?php echo number_format($total_revenue, 2); ?></h3>
                    <p>Total Revenue</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
                <div class="stat-info">
                    <h3 class="price-display" data-price="<?php echo $monthly_revenue; ?>">$<?php echo number_format($monthly_revenue, 2); ?></h3>
                    <p>This Month</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-shopping-bag"></i></div>
                <div class="stat-info">
                    <h3><?php echo $total_orders; ?></h3>
                    <p>Total Orders</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-info">
                    <h3><?php echo $total_customers; ?></h3>
                    <p>Active Customers</p>
                </div>
            </div>
        </div>

        <div class="analytics-grid">
            <div class="section-card">
                <h2><i class="fas fa-trophy"></i> Top Selling Products</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Units Sold</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($product = mysqli_fetch_assoc($top_products)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><?php echo htmlspecialchars($product['category']); ?></td>
                                <td><?php echo $product['total_sold']; ?> units</td>
                                <td><span class="price-display" data-price="<?php echo $product['revenue']; ?>">$<?php echo number_format($product['revenue'], 2); ?></span></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <div class="section-card">
                <h2><i class="fas fa-warehouse"></i> RDC Performance</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>RDC Location</th>
                            <th>Orders</th>
                            <th>Revenue</th>
                            <th>Avg Delivery</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($rdc = mysqli_fetch_assoc($rdc_performance)): ?>
                            <tr>
                                <td><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($rdc['rdc_location']); ?></td>
                                <td><?php echo $rdc['total_orders']; ?></td>
                                <td><span class="price-display" data-price="<?php echo $rdc['revenue']; ?>">$<?php echo number_format($rdc['revenue'], 2); ?></span></td>
                                <td><?php echo $rdc['avg_delivery_hours'] ? round($rdc['avg_delivery_hours']) . ' hrs' : 'N/A'; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="section-card">
            <h2><i class="fas fa-clock"></i> Recent Activity</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($activity = mysqli_fetch_assoc($recent_activities)): ?>
                        <tr>
                            <td>#<?php echo $activity['id']; ?></td>
                            <td><?php echo htmlspecialchars($activity['customer_name']); ?></td>
                            <td><span class="price-display" data-price="<?php echo $activity['total']; ?>">$<?php echo number_format($activity['total'], 2); ?></span></td>
                            <td><span class="badge badge-<?php echo $activity['status']; ?>"><?php echo strtoupper($activity['status']); ?></span></td>
                            <td><?php echo date('M j, Y H:i', strtotime($activity['order_date'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-header">
                    <i class="fas fa-percentage"></i>
                    <h3>Order Fulfillment Rate</h3>
                </div>
                <div class="kpi-value"><?php echo $total_orders > 0 ? round(($delivered_orders / $total_orders) * 100, 1) : 0; ?>%</div>
                <div class="kpi-footer">
                    <span><?php echo $delivered_orders; ?> / <?php echo $total_orders; ?> orders delivered</span>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-header">
                    <i class="fas fa-box-open"></i>
                    <h3>Inventory Health</h3>
                </div>
                <div class="kpi-value"><?php echo $total_products - $low_stock_count; ?> / <?php echo $total_products; ?></div>
                <div class="kpi-footer">
                    <span><?php echo $low_stock_count; ?> products need restocking</span>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-header">
                    <i class="fas fa-hourglass-half"></i>
                    <h3>Pending Orders</h3>
                </div>
                <div class="kpi-value"><?php echo $pending_orders; ?></div>
                <div class="kpi-footer">
                    <span>Awaiting processing</span>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>
