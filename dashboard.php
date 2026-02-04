<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$role = $_SESSION['role'];
$user_name = $_SESSION['name'];

if ($role == 'admin') {
    $products_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM products"))['count'];
    $users_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users"))['count'];
    $orders_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM orders"))['count'];
    $low_stock = mysqli_query($conn, "SELECT * FROM products WHERE stock < 50 ORDER BY stock ASC LIMIT 5");
}

if ($role == 'rdc') {
    $rdc_location = $_SESSION['rdc_location'];
    $assigned_deliveries = mysqli_query($conn, "SELECT d.*, o.id as order_id, o.total, u.name as customer_name 
        FROM deliveries d 
        JOIN orders o ON d.order_id = o.id 
        JOIN users u ON o.customer_id = u.id 
        WHERE d.rdc_staff_id = " . $_SESSION['user_id'] . " 
        ORDER BY d.assigned_date DESC LIMIT 10");
    $pending_deliveries = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM deliveries WHERE rdc_staff_id = " . $_SESSION['user_id'] . " AND status = 'pending'"))['count'];
}

if ($role == 'customer') {
    $my_orders = mysqli_query($conn, "SELECT * FROM orders WHERE customer_id = " . $_SESSION['user_id'] . " ORDER BY order_date DESC LIMIT 5");
    $total_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM orders WHERE customer_id = " . $_SESSION['user_id']))['count'];
    $pending_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM orders WHERE customer_id = " . $_SESSION['user_id'] . " AND status = 'pending'"))['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ISDN</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <script src="theme-currency.js"></script>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1>Welcome, <?php echo htmlspecialchars($user_name); ?></h1>
            <p>Role: <span class="badge"><?php echo strtoupper($role); ?></span></p>
        </div>

        <?php if($role == 'admin'): ?>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-box"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $products_count; ?></h3>
                        <p>Total Products</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $users_count; ?></h3>
                        <p>Total Users</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-file-invoice"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $orders_count; ?></h3>
                        <p>Total Orders</p>
                    </div>
                </div>
            </div>

            <div class="section-card">
                <h2><i class="fas fa-exclamation-triangle"></i> Low Stock Alert</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Stock</th>
                            <th>RDC Location</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($item = mysqli_fetch_assoc($low_stock)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td><?php echo htmlspecialchars($item['category']); ?></td>
                                <td><span class="badge badge-warning"><?php echo $item['stock']; ?></span></td>
                                <td><?php echo htmlspecialchars($item['rdc_location']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif($role == 'rdc'): ?>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-map-marker-alt"></i></div>
                    <div class="stat-info">
                        <h3><?php echo htmlspecialchars($rdc_location); ?></h3>
                        <p>Your RDC Location</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $pending_deliveries; ?></h3>
                        <p>Pending Deliveries</p>
                    </div>
                </div>
            </div>

            <div class="section-card">
                <h2><i class="fas fa-box"></i> Recent Assigned Deliveries</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Assigned Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($delivery = mysqli_fetch_assoc($assigned_deliveries)): ?>
                            <tr>
                                <td>#<?php echo $delivery['order_id']; ?></td>
                                <td><?php echo htmlspecialchars($delivery['customer_name']); ?></td>
                                <td><span class="price-display" data-price="<?php echo $delivery['total']; ?>">$<?php echo number_format($delivery['total'], 2); ?></span></td>
                                <td><span class="badge badge-<?php echo $delivery['status']; ?>"><?php echo strtoupper($delivery['status']); ?></span></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($delivery['assigned_date'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

        <?php else: ?>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-file-invoice"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $total_orders; ?></h3>
                        <p>Total Orders</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $pending_orders; ?></h3>
                        <p>Pending Orders</p>
                    </div>
                </div>
            </div>

            <div class="section-card">
                <h2><i class="fas fa-clipboard-list"></i> Recent Orders</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Order Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($order = mysqli_fetch_assoc($my_orders)): ?>
                            <tr>
                                <td>#<?php echo $order['id']; ?></td>
                                <td><span class="price-display" data-price="<?php echo $order['total']; ?>">$<?php echo number_format($order['total'], 2); ?></span></td>
                                <td><span class="badge badge-<?php echo $order['status']; ?>"><?php echo strtoupper($order['status']); ?></span></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($order['order_date'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <div style="margin-top: 20px;">
                    <a href="products.php" class="btn-primary">Browse Products</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>
