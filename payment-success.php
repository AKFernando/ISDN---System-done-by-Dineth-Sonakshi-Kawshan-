<?php
session_start();
require_once 'db.php';
require_once 'payment-config.php';
require_once 'send-email.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: login.php');
    exit();
}

$session_id = isset($_GET['session_id']) ? trim($_GET['session_id']) : '';

if (empty($session_id)) {
    header('Location: checkout.php?error=missing_session');
    exit();
}

// Prevent duplicate order creation for the same Stripe session
$existing = mysqli_query($conn, "SELECT id FROM orders WHERE payment_reference='" . mysqli_real_escape_string($conn, $session_id) . "' LIMIT 1");
$existing_order = mysqli_fetch_assoc($existing);

if ($existing_order) {
    header('Location: order-confirmation.php?order_id=' . $existing_order['id']);
    exit();
}

// Fetch checkout session from Stripe to verify payment
$ch = curl_init("https://api.stripe.com/v1/checkout/sessions/" . urlencode($session_id));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . STRIPE_SECRET_KEY
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch) || $http_code >= 400) {
    header('Location: checkout.php?error=stripe_fetch');
    exit();
}

curl_close($ch);

$session_data = json_decode($response, true);

if (!$session_data || !isset($session_data['payment_status']) || $session_data['payment_status'] !== 'paid') {
    header('Location: checkout.php?error=unpaid');
    exit();
}

$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];

// If the cart was cleared locally, try to rebuild it from Stripe metadata
if (empty($cart) && isset($session_data['metadata']['cart'])) {
    $decoded_cart = json_decode($session_data['metadata']['cart'], true);
    if (is_array($decoded_cart)) {
        $cart = $decoded_cart;
    }
}

if (empty($cart)) {
    header('Location: checkout.php?error=empty_cart_after_payment');
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

$stripe_total = isset($session_data['amount_total']) ? ($session_data['amount_total'] / 100) : $total;

// Small tolerance for rounding differences
if (abs($total - $stripe_total) > 0.5) {
    header('Location: checkout.php?error=amount_mismatch');
    exit();
}

$customer_id = $_SESSION['user_id'];

$insert_order = "INSERT INTO orders (customer_id, total, payment_status, payment_reference, status) 
                 VALUES ($customer_id, $total, 'paid', '" . mysqli_real_escape_string($conn, $session_id) . "', 'pending')";

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
