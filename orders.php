<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$role = $_SESSION['role'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($role == 'customer' && isset($_POST['place_order'])) {
        header('Location: checkout.php');
        exit();
    }
    
    if ($role == 'customer' && isset($_POST['clear_cart'])) {
        $_SESSION['cart'] = array();
        $message = 'Cart cleared';
    }
    
    if ($role == 'admin' && isset($_POST['update_status'])) {
        $order_id = intval($_POST['order_id']);
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        
        $order_query = mysqli_query($conn, "SELECT o.*, u.email, u.name as customer_name FROM orders o JOIN users u ON o.customer_id = u.id WHERE o.id = $order_id");
        $order_info = mysqli_fetch_assoc($order_query);
        
        mysqli_query($conn, "UPDATE orders SET status='$status' WHERE id=$order_id");
        mysqli_query($conn, "UPDATE deliveries SET status='$status' WHERE order_id=$order_id");
        
        if (in_array($status, ['dispatched', 'delivered']) && $order_info['email']) {
            include_once 'send-email.php';
            sendDeliveryNotification($order_info['email'], $order_info['customer_name'], $order_id, $status);
        }
        
        $message = 'Order status updated';
    }
    
    if ($role == 'admin' && isset($_POST['assign_delivery'])) {
        $order_id = intval($_POST['order_id']);
        $rdc_staff_id = intval($_POST['rdc_staff_id']);
        mysqli_query($conn, "UPDATE deliveries SET rdc_staff_id=$rdc_staff_id WHERE order_id=$order_id");
        $message = 'Delivery assigned';
    }
}

if ($role == 'admin') {
    $orders = mysqli_query($conn, "SELECT o.*, u.name as customer_name, u.email, d.rdc_staff_id, d.status as delivery_status 
        FROM orders o 
        LEFT JOIN users u ON o.customer_id = u.id 
        LEFT JOIN deliveries d ON o.id = d.order_id 
        ORDER BY o.order_date DESC");
    $rdc_staff = mysqli_query($conn, "SELECT id, name, rdc_location FROM users WHERE role='rdc'");
} else {
    $customer_id = $_SESSION['user_id'];
    $orders = mysqli_query($conn, "SELECT * FROM orders WHERE customer_id=$customer_id ORDER BY order_date DESC");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - ISDN</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <script src="theme-currency.js"></script>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container">
        <?php if($message): ?>
            <div class="alert-success"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if($role == 'customer'): ?>
            <div class="page-header">
                <h1>Shopping Cart</h1>
            </div>
            
            <?php 
            $cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : array();
            if (!empty($cart)): 
                $cart_total = 0;
            ?>
                <div class="section-card">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($cart as $product_id => $quantity): 
                                $product = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM products WHERE id=$product_id"));
                                if ($product):
                                    $subtotal = $product['price'] * $quantity;
                                    $cart_total += $subtotal;
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td>$<?php echo number_format($product['price'], 2); ?></td>
                                    <td><?php echo $quantity; ?></td>
                                    <td><span class="price-display" data-price="<?php echo $subtotal; ?>">$<?php echo number_format($subtotal, 2); ?></span></td>
                                </tr>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                            <tr>
                                <td colspan="3" style="text-align:right;"><strong>Total:</strong></td>
                                <td><strong><span class="price-display" data-price="<?php echo $cart_total; ?>">$<?php echo number_format($cart_total, 2); ?></span></strong></td>
                            </tr>
                        </tbody>
                    </table>
                    <div style="margin-top: 20px;">
                        <a href="checkout.php" class="btn-primary" style="display:inline-block;">Checkout &amp; Pay</a>
                        <form method="POST" style="display:inline;">
                            <button type="submit" name="clear_cart" class="btn-danger">Clear Cart</button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div class="section-card">
                    <p>Your cart is empty. <a href="products.php">Browse products</a></p>
                </div>
            <?php endif; ?>

            <div class="page-header" style="margin-top: 40px;">
                <h1>My Order History</h1>
            </div>
        <?php else: ?>
            <div class="page-header">
                <h1>All Orders</h1>
            </div>
        <?php endif; ?>

        <div class="section-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <?php if($role == 'admin'): ?>
                            <th>Customer</th>
                        <?php endif; ?>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Order Date</th>
                        <th>Invoice</th>
                        <?php if($role == 'admin'): ?>
                            <th>Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php while($order = mysqli_fetch_assoc($orders)): ?>
                        <tr>
                            <td>#<?php echo $order['id']; ?></td>
                            <?php if($role == 'admin'): ?>
                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                            <?php endif; ?>
                            <td><span class="price-display" data-price="<?php echo $order['total']; ?>">$<?php echo number_format($order['total'], 2); ?></span></td>
                            <td><span class="badge badge-<?php echo $order['payment_status']; ?>"><?php echo strtoupper($order['payment_status']); ?></span></td>
                            <td><span class="badge badge-<?php echo $order['status']; ?>"><?php echo strtoupper($order['status']); ?></span></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($order['order_date'])); ?></td>
                            <td>
                                <a href="generate-invoice.php?order_id=<?php echo $order['id']; ?>" class="btn-small btn-secondary" target="_blank">
                                    <i class="fas fa-file-invoice"></i> View Invoice
                                </a>
                            </td>
                            <?php if($role == 'admin'): ?>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <select name="status" class="inline-select">
                                            <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="dispatched" <?php echo $order['status'] == 'dispatched' ? 'selected' : ''; ?>>Dispatched</option>
                                            <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                        </select>
                                        <button type="submit" name="update_status" class="btn-small btn-primary">Update</button>
                                    </form>
                                    <?php if(!$order['rdc_staff_id']): ?>
                                        <form method="POST" style="display:inline;margin-top:5px;">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <select name="rdc_staff_id" class="inline-select">
                                                <option value="">Assign RDC</option>
                                                <?php mysqli_data_seek($rdc_staff, 0); ?>
                                                <?php while($staff = mysqli_fetch_assoc($rdc_staff)): ?>
                                                    <option value="<?php echo $staff['id']; ?>"><?php echo htmlspecialchars($staff['name']) . ' (' . htmlspecialchars($staff['rdc_location']) . ')'; ?></option>
                                                <?php endwhile; ?>
                                            </select>
                                            <button type="submit" name="assign_delivery" class="btn-small btn-secondary">Assign</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>
