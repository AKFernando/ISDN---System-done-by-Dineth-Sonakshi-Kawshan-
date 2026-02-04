<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: dashboard.php');
    exit();
}

$daily_sales = mysqli_query($conn, "SELECT DATE(order_date) as date, SUM(total) as revenue, COUNT(*) as orders 
    FROM orders 
    WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
    GROUP BY DATE(order_date) 
    ORDER BY date ASC");

$category_sales = mysqli_query($conn, "SELECT p.category, SUM(oi.quantity * oi.price) as revenue, SUM(oi.quantity) as units 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    GROUP BY p.category 
    ORDER BY revenue DESC");

$dates = [];
$revenues = [];
while($row = mysqli_fetch_assoc($daily_sales)) {
    $dates[] = date('M j', strtotime($row['date']));
    $revenues[] = $row['revenue'];
}
mysqli_data_seek($daily_sales, 0);

$categories = [];
$category_revenues = [];
while($row = mysqli_fetch_assoc($category_sales)) {
    $categories[] = $row['category'];
    $category_revenues[] = $row['revenue'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Analytics - ISDN</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="theme-currency.js"></script>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-chart-pie"></i> Advanced Analytics</h1>
            <p>Visual insights and interactive charts for data-driven decisions</p>
            <div style="margin-top: 1rem;">
                <a href="export-excel.php?type=orders" class="btn-secondary btn-small">
                    <i class="fas fa-file-excel"></i> Export Orders
                </a>
                <a href="export-excel.php?type=products" class="btn-secondary btn-small">
                    <i class="fas fa-file-excel"></i> Export Products
                </a>
                <a href="export-excel.php?type=users" class="btn-secondary btn-small">
                    <i class="fas fa-file-excel"></i> Export Users
                </a>
            </div>
        </div>

        <div class="charts-grid">
            <div class="section-card">
                <h2><i class="fas fa-chart-line"></i> Revenue Trend (Last 30 Days)</h2>
                <canvas id="revenueChart"></canvas>
            </div>

            <div class="section-card">
                <h2><i class="fas fa-chart-pie"></i> Sales by Category</h2>
                <canvas id="categoryChart"></canvas>
            </div>
        </div>

        <div class="section-card">
            <h2><i class="fas fa-download"></i> Export Options</h2>
            <div class="export-options">
                <div class="export-card">
                    <i class="fas fa-file-excel fa-3x"></i>
                    <h3>Export to Excel</h3>
                    <p>Download data as CSV file (Excel compatible)</p>
                    <a href="export-excel.php?type=orders" class="btn-primary btn-small">Orders CSV</a>
                    <a href="export-excel.php?type=products" class="btn-secondary btn-small">Products CSV</a>
                </div>
                <div class="export-card">
                    <i class="fas fa-file-pdf fa-3x"></i>
                    <h3>Generate Invoice</h3>
                    <p>Create printable PDF invoices for orders</p>
                    <a href="orders.php" class="btn-primary btn-small">View Orders</a>
                </div>
                <div class="export-card">
                    <i class="fas fa-envelope fa-3x"></i>
                    <h3>Email Notifications</h3>
                    <p>Automated emails for order confirmations</p>
                    <span class="badge badge-delivered">Active</span>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($dates); ?>,
                datasets: [{
                    label: 'Daily Revenue (USD)',
                    data: <?php echo json_encode($revenues); ?>,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: true },
                    title: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($categories); ?>,
                datasets: [{
                    data: <?php echo json_encode($category_revenues); ?>,
                    backgroundColor: [
                        '#667eea',
                        '#764ba2',
                        '#f093fb',
                        '#4facfe',
                        '#00f2fe',
                        '#43e97b'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    </script>
</body>
</html>
