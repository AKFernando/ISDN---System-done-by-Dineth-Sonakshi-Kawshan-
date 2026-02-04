<?php
session_start();
require_once 'db.php';
require_once 'payment-config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header('Location: login.php');
    exit();
}

$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : array();

if (empty($cart)) {
    header('Location: cart.php');
    exit();
}

$message = '';
$error = '';

if (isset($_GET['canceled'])) {
    $error = 'Payment canceled. No charges were made.';
}

if (isset($_GET['error'])) {
    $error_map = [
        'missing_session' => 'Payment session missing. Please start again.',
        'stripe_fetch' => 'Could not verify payment with Stripe. Please retry.',
        'unpaid' => 'Payment was not completed. Please try again.',
        'empty_cart_after_payment' => 'Cart was empty after payment. Nothing was charged.',
        'stock_issue' => 'Some items went out of stock. Please update your cart.',
        'amount_mismatch' => 'Charged amount did not match cart total. Please contact support.',
        'order_create_failed' => 'Payment captured, but we could not create the order. Contact support.'
    ];
    $error_code = $_GET['error'];
    $error = isset($error_map[$error_code]) ? $error_map[$error_code] : 'We could not verify your payment. Please try again.';
}

$demo_payment_mode = (STRIPE_SECRET_KEY === 'sk_test_replace_me' || STRIPE_PUBLISHABLE_KEY === 'pk_test_replace_me');

$cart_items = array();
$subtotal = 0;

if (!empty($cart)) {
    $product_ids = implode(',', array_keys($cart));
    $products_query = mysqli_query($conn, "SELECT * FROM products WHERE id IN ($product_ids)");
    
    while ($product = mysqli_fetch_assoc($products_query)) {
        $product['quantity'] = $cart[$product['id']];
        $product['line_total'] = $product['price'] * $product['quantity'];
        $subtotal += $product['line_total'];
        $cart_items[] = $product;
    }
}

$tax = $subtotal * 0.05;
$shipping = $subtotal > 100 ? 0 : 15;
$total = $subtotal + $tax + $shipping;

$user_query = mysqli_query($conn, "SELECT * FROM users WHERE id=" . $_SESSION['user_id']);
$user = mysqli_fetch_assoc($user_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - ISDN</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="ecommerce-styles.css">
    <script src="theme-currency.js"></script>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container">
        <div class="checkout-progress">
            <div class="progress-step completed">
                <div class="step-number">1</div>
                <div class="step-label">Cart</div>
            </div>
            <div class="progress-line completed"></div>
            <div class="progress-step active">
                <div class="step-number">2</div>
                <div class="step-label">Checkout</div>
            </div>
            <div class="progress-line"></div>
            <div class="progress-step">
                <div class="step-number">3</div>
                <div class="step-label">Confirmation</div>
            </div>
        </div>

        <?php if($error): ?>
            <div class="alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="checkout-layout">
            <div class="checkout-main">
                <div class="section-card">
                    <h2><i class="fas fa-clipboard-list"></i> Order Review</h2>
                    <div class="checkout-items">
                        <?php foreach($cart_items as $item): ?>
                            <div class="checkout-item">
                                <div class="checkout-item-image">
                                    <?php if($item['image'] && file_exists($item['image'])): ?>
                                        <img src="<?php echo $item['image']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                    <?php else: ?>
                                        <div class="checkout-placeholder"><i class="fas fa-box"></i></div>
                                    <?php endif; ?>
                                </div>
                                <div class="checkout-item-info">
                                    <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                    <p><?php echo htmlspecialchars($item['category']); ?></p>
                                    <p class="checkout-item-rdc">📍 <?php echo htmlspecialchars($item['rdc_location']); ?></p>
                                </div>
                                <div class="checkout-item-qty">
                                    <span>Qty: <?php echo $item['quantity']; ?></span>
                                </div>
                                <div class="checkout-item-price">
                                    <span>$<?php echo number_format($item['line_total'], 2); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="section-card">
                    <h2><i class="fas fa-user"></i> Delivery Information</h2>
                    <div class="delivery-info-display">
                        <div class="info-row">
                            <span class="info-label">Name:</span>
                            <span class="info-value"><?php echo htmlspecialchars($user['name']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Email:</span>
                            <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Phone:</span>
                            <span class="info-value"><?php echo htmlspecialchars($user['phone']); ?></span>
                        </div>
                        <div class="info-notice">
                            <p><i class="fas fa-box"></i> Estimated Delivery: 24-48 hours</p>
                            <p><i class="fas fa-truck"></i> Your order will be shipped from the nearest RDC</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="checkout-sidebar">
                <div class="section-card order-summary-checkout">
                    <h2>Order Summary</h2>
                    <div class="summary-line">
                        <span>Subtotal</span>
                        <span class="price-display" data-price="<?php echo $subtotal; ?>">$<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="summary-line">
                        <span>Tax (5%)</span>
                        <span class="price-display" data-price="<?php echo $tax; ?>">$<?php echo number_format($tax, 2); ?></span>
                    </div>
                    <div class="summary-line">
                        <span>Shipping</span>
                        <span><?php echo $shipping > 0 ? '<span class="price-display" data-price="' . $shipping . '">$' . number_format($shipping, 2) . '</span>' : 'FREE'; ?></span>
                    </div>
                    <div class="summary-divider"></div>
                    <div class="summary-total">
                        <span>Total</span>
                        <span class="price-display" data-price="<?php echo $total; ?>">$<?php echo number_format($total, 2); ?></span>
                    </div>
                    
                    <?php if(!$demo_payment_mode): ?>
                        <button type="button" id="pay-with-card" class="btn-place-order">
                            <span>Pay with Card</span>
                            <span class="btn-amount price-display" data-price="<?php echo $total; ?>">$<?php echo number_format($total, 2); ?></span>
                        </button>
                        <div id="payment-error" class="alert-error" style="display:none;"></div>
                    <?php else: ?>
                        <form method="POST" action="payment-demo.php" class="demo-payment-form">
                            <div class="form-group">
                                <label>Card Number</label>
                                <input type="text" name="card_number" placeholder="4242 4242 4242 4242" maxlength="19" required>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Expiry (MM/YY)</label>
                                    <input type="text" name="card_expiry" placeholder="12/30" maxlength="5" required>
                                </div>
                                <div class="form-group">
                                    <label>CVC</label>
                                    <input type="text" name="card_cvc" placeholder="123" maxlength="4" required>
                                </div>
                            </div>
                            <button type="submit" class="btn-place-order">
                                <span>Pay (Demo)</span>
                                <span class="btn-amount price-display" data-price="<?php echo $total; ?>">$<?php echo number_format($total, 2); ?></span>
                            </button>
                            <p class="small-note">Demo mode enabled: payment is simulated.</p>
                        </form>
                    <?php endif; ?>

                    <div class="checkout-security">
                        <p><i class="fas fa-lock"></i> Secure Checkout</p>
                        <p><i class="fas fa-check-circle"></i> 100% Money-back Guarantee</p>
                    </div>
                </div>

                <a href="cart.php" class="btn-back-cart">← Back to Cart</a>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://js.stripe.com/v3"></script>
    <script>
    (function() {
        const payButton = document.getElementById('pay-with-card');
        const errorBox = document.getElementById('payment-error');
        if (!payButton) return;

        const stripe = Stripe('<?php echo STRIPE_PUBLISHABLE_KEY; ?>');

        function showError(message) {
            if (!errorBox) return;
            errorBox.textContent = message || 'Something went wrong. Please try again.';
            errorBox.style.display = 'block';
            payButton.disabled = false;
            payButton.classList.remove('loading');
        }

        payButton.addEventListener('click', async function() {
            payButton.disabled = true;
            payButton.classList.add('loading');
            errorBox.style.display = 'none';

            try {
                const response = await fetch('create-checkout-session.php', {
                    method: 'POST'
                });

                const data = await response.json();

                if (!response.ok || data.error) {
                    showError(data.error || 'Unable to start payment.');
                    return;
                }

                if (data.url) {
                    window.location = data.url;
                    return;
                }

                showError('Payment link was not generated.');
            } catch (err) {
                showError('Network issue starting payment. Please retry.');
            }
        });
    })();
    </script>
</body>
</html>
