<?php
// Simulated card payment flow for demo when Stripe keys are not configured.
session_start();
require_once 'db.php';
require_once 'send-email.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: login.php');
    exit();
}

$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
if (empty($cart)) {
    header('Location: cart.php');
    exit();
}

// Basic client-side style validation to mimic a payment form.
$card_number = isset($_POST['card_number']) ? preg_replace('/\s+/', '', $_POST['card_number']) : '';
$card_expiry = isset($_POST['card_expiry']) ? trim($_POST['card_expiry']) : '';
$card_cvc = isset($_POST['card_cvc']) ? trim($_POST['card_cvc']) : '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($card_number) || empty($card_expiry) || empty($card_cvc)) {
    header('Location: checkout.php?error=missing_session');
    exit();
}

// Very light validation for demo purposes only.
if (!preg_match('/^\d{13,19}$/', $card_number)) {
    header('Location: checkout.php?error=unpaid');
    exit();
}
if (!preg_match('/^\d{2}\/\d{2}$/', $card_expiry)) {
    header('Location: checkout.php?error=unpaid');
    exit();
}
if (!preg_match('/^\d{3,4}$/', $card_cvc)) {
    header('Location: checkout.php?error=unpaid');
    exit();
}

$product_ids = implode(',', array_map('intval', array_keys($cart)));
$products_query = mysqli_query($conn, "SELECT id, name, price, stock FROM products WHERE id IN ($product_ids)");

$products = [];
while ($row = mysqli_fetch_assoc($products_query)) {
    $products[$row['id']] = $row;
}

$subtotal = 0;
$order_items = [];

foreach ($cart as $product_id => $quantity) {
    if (!isset($products[$product_id]) || $products[$product_id]['stock'] < $quantity) {
        header('Location: checkout.php?error=stock_issue');
        exit();
    }

    $product = $products[$product_id];
    $line_total = $product['price'] * $quantity;
    $subtotal += $line_total;

    $order_items[] = [
        'product_id' => $product_id,
        'quantity' => $quantity,
        'price' => $product['price'],
        'name' => $product['name']
    ];
}

$tax = $subtotal * 0.05;
$shipping = $subtotal > 100 ? 0 : 15;
$total = $subtotal + $tax + $shipping;

$payment_reference = 'DEMO-' . uniqid();
$customer_id = $_SESSION['user_id'];

$insert_order = "INSERT INTO orders (customer_id, total, payment_status, payment_reference, status) 
                 VALUES ($customer_id, $total, 'paid', '" . mysqli_real_escape_string($conn, $payment_reference) . "', 'pending')";

if (mysqli_query($conn, $insert_order)) {
    $order_id = mysqli_insert_id($conn);

    foreach ($order_items as $item) {
        mysqli_query($conn, "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES ($order_id, {$item['product_id']}, {$item['quantity']}, {$item['price']})");
        mysqli_query($conn, "UPDATE products SET stock = stock - {$item['quantity']} WHERE id={$item['product_id']}");
    }

    mysqli_query($conn, "INSERT INTO deliveries (order_id) VALUES ($order_id)");

    $user_query = mysqli_query($conn, "SELECT * FROM users WHERE id=" . $customer_id);
    $user = mysqli_fetch_assoc($user_query);

    if ($user && $user['email']) {
        sendOrderConfirmation($user['email'], $user['name'], $order_id, $total, $order_items);
    }

    $_SESSION['cart'] = [];
    header('Location: order-confirmation.php?order_id=' . $order_id);
    exit();
}

header('Location: checkout.php?error=order_create_failed');
exit();
?>
