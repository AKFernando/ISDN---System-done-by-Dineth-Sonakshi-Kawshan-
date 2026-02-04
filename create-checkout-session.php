<?php
session_start();
require_once 'db.php';
require_once 'payment-config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if (STRIPE_SECRET_KEY === 'sk_test_replace_me' || STRIPE_PUBLISHABLE_KEY === 'pk_test_replace_me') {
    // Demo fallback: send user to a mock payment page
    $demoSessionId = 'demo_' . time();
    echo json_encode([
        'sessionId' => $demoSessionId,
        'url' => APP_BASE_URL . '/demo-payment.php?sid=' . urlencode($demoSessionId)
    ]);
    exit();
}

$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];

if (empty($cart)) {
    http_response_code(400);
    echo json_encode(['error' => 'Your cart is empty.']);
    exit();
}

$product_ids = implode(',', array_map('intval', array_keys($cart)));
$products_query = mysqli_query($conn, "SELECT id, name, price, stock, category FROM products WHERE id IN ($product_ids)");

$products = [];
while ($row = mysqli_fetch_assoc($products_query)) {
    $products[$row['id']] = $row;
}

$line_items = [];
$subtotal = 0;

foreach ($cart as $product_id => $quantity) {
    if (!isset($products[$product_id])) {
        http_response_code(400);
        echo json_encode(['error' => 'One or more items are unavailable.']);
        exit();
    }

    $product = $products[$product_id];

    if ($product['stock'] < $quantity) {
        http_response_code(400);
        echo json_encode(['error' => "Insufficient stock for {$product['name']}"]);
        exit();
    }

    $line_items[] = [
        'name' => $product['name'],
        'category' => $product['category'],
        'amount' => $product['price'],
        'quantity' => $quantity
    ];

    $subtotal += $product['price'] * $quantity;
}

$tax = $subtotal * 0.05;
$shipping = $subtotal > 100 ? 0 : 15;

$payload = [
    'mode' => 'payment',
    'payment_method_types[]' => 'card',
    'success_url' => APP_BASE_URL . '/payment-success.php?session_id={CHECKOUT_SESSION_ID}',
    'cancel_url' => APP_BASE_URL . '/checkout.php?canceled=1',
    'metadata[customer_id]' => $_SESSION['user_id'],
    'metadata[cart]' => json_encode($cart),
    'metadata[subtotal]' => number_format($subtotal, 2, '.', ''),
    'metadata[tax]' => number_format($tax, 2, '.', ''),
    'metadata[shipping]' => number_format($shipping, 2, '.', '')
];

foreach ($line_items as $index => $item) {
    $payload["line_items[$index][price_data][currency]"] = 'usd';
    $payload["line_items[$index][price_data][product_data][name]"] = $item['name'];
    $payload["line_items[$index][price_data][unit_amount]"] = intval(round($item['amount'] * 100));
    $payload["line_items[$index][quantity]"] = $item['quantity'];
}

$ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . STRIPE_SECRET_KEY
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    http_response_code(500);
    echo json_encode(['error' => 'Unable to reach Stripe.']);
    exit();
}

curl_close($ch);

$decoded = json_decode($response, true);

if ($http_code >= 400 || !$decoded || !isset($decoded['id'])) {
    http_response_code(500);
    $error_message = isset($decoded['error']['message']) ? $decoded['error']['message'] : 'Failed to create payment session.';
    echo json_encode(['error' => $error_message]);
    exit();
}

echo json_encode([
    'sessionId' => $decoded['id'],
    'url' => $decoded['url']
]);
?>
