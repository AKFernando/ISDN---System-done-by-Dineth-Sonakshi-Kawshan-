<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header('Location: login.php');
    exit();
}

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if ($order_id == 0) {
    header('Location: orders.php');
    exit();
}

$order_query = mysqli_query($conn, "SELECT * FROM orders WHERE id=$order_id AND customer_id=" . $_SESSION['user_id']);
$order = mysqli_fetch_assoc($order_query);

if (!$order) {
    header('Location: orders.php');
    exit();
}

$items_query = mysqli_query($conn, "SELECT oi.*, p.name, p.category, p.image FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id=$order_id");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - ISDN</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="ecommerce-styles.css">
    <script src="theme-currency.js"></script>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container">
        <div class="confirmation-container">
            <div class="confirmation-success">
                <div class="success-icon"><i class="fas fa-check"></i></div>
                <h1>Order Placed Successfully!</h1>
                <p class="confirmation-message">Thank you for your order. We'll deliver it within 24-48 hours.</p>
                <div class="order-number">
                    <span>Order Number:</span>
                    <strong>#<?php echo $order['id']; ?></strong>
                </div>
            </div>

            <div class="confirmation-details">
                <div class="section-card">
                    <h2><i class="fas fa-box"></i> Order Details</h2>
                    <div class="confirmation-info">
                        <div class="info-row">
                            <span>Order Date:</span>
                            <span><?php echo date('F j, Y, g:i a', strtotime($order['order_date'])); ?></span>
                        </div>
                        <div class="info-row">
                            <span>Status:</span>
                            <span><span class="badge badge-<?php echo $order['status']; ?>"><?php echo strtoupper($order['status']); ?></span></span>
                        </div>
                        <div class="info-row">
                            <span>Payment:</span>
                            <span><span class="badge badge-<?php echo $order['payment_status']; ?>"><?php echo strtoupper($order['payment_status']); ?></span></span>
                        </div>
                        <?php if (!empty($order['payment_reference'])): ?>
                        <div class="info-row">
                            <span>Payment Ref:</span>
                            <span><?php echo htmlspecialchars($order['payment_reference']); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="info-row">
                            <span>Total Amount:</span>
                            <span class="amount-highlight price-display" data-price="<?php echo $order['total']; ?>">$<?php echo number_format($order['total'], 2); ?></span>
                        </div>
                        <div class="info-row">
                            <span>Delivery:</span>
                            <span>Within 24-48 hours</span>
                        </div>
                    </div>
                </div>

                <div class="section-card">
                    <h2><i class="fas fa-shopping-bag"></i> Order Items</h2>
                    <div class="confirmation-items">
                        <?php while($item = mysqli_fetch_assoc($items_query)): ?>
                            <div class="confirmation-item">
                                <div class="confirmation-item-image">
                                    <?php if($item['image'] && file_exists($item['image'])): ?>
                                        <img src="<?php echo $item['image']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                    <?php else: ?>
                                        <div class="confirmation-placeholder"><i class="fas fa-box"></i></div>
                                    <?php endif; ?>
                                </div>
                                <div class="confirmation-item-details">
                                    <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                    <p><?php echo htmlspecialchars($item['category']); ?></p>
                                    <p class="item-qty-price">Qty: <?php echo $item['quantity']; ?> × $<?php echo number_format($item['price'], 2); ?></p>
                                </div>
                                <div class="confirmation-item-total">
                                    <span class="price-display" data-price="<?php echo ($item['price'] * $item['quantity']); ?>">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>

                <div class="confirmation-next-steps">
                    <h3><i class="fas fa-clipboard-list"></i> What's Next?</h3>
                    <ul class="next-steps-list">
                        <li><i class="fas fa-check"></i> Order confirmation email sent to your registered email</li>
                        <li><i class="fas fa-check"></i> Your order is being processed at the nearest RDC</li>
                        <li><i class="fas fa-check"></i> Track your order status in "My Orders" section</li>
                        <li><i class="fas fa-check"></i> Delivery within 24-48 hours to your location</li>
                    </ul>
                </div>

                <div class="confirmation-actions">
                    <a href="orders.php" class="btn-primary">View My Orders</a>
                    <a href="products.php" class="btn-secondary">Continue Shopping</a>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>
