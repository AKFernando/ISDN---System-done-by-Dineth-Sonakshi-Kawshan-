<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header('Location: login.php');
    exit();
}

$message = '';
$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : array();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_cart'])) {
        foreach ($_POST['quantity'] as $product_id => $quantity) {
            if ($quantity > 0) {
                $cart[$product_id] = intval($quantity);
            } else {
                unset($cart[$product_id]);
            }
        }
        $_SESSION['cart'] = $cart;
        $message = 'Cart updated successfully';
    }
    
    if (isset($_POST['remove_item'])) {
        $product_id = intval($_POST['product_id']);
        unset($cart[$product_id]);
        $_SESSION['cart'] = $cart;
        $message = 'Item removed from cart';
    }
    
    if (isset($_POST['clear_cart'])) {
        $_SESSION['cart'] = array();
        $cart = array();
        $message = 'Cart cleared';
    }
}

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

$tax_rate = 0.05;
$tax = $subtotal * $tax_rate;
$shipping = $subtotal > 100 ? 0 : 15;
$total = $subtotal + $tax + $shipping;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - ISDN</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="ecommerce-styles.css">
    <script src="theme-currency.js"></script>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-shopping-cart"></i> Shopping Cart</h1>
            <p>Review your items and proceed to checkout</p>
        </div>

        <?php if($message): ?>
            <div class="alert-success"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if(empty($cart_items)): ?>
        <div class="empty-cart">
            <div class="empty-cart-icon"><i class="fas fa-shopping-cart"></i></div>
            <h2>Your cart is empty</h2>
                <p>Browse our products and add items to get started</p>
                <a href="products.php" class="btn-primary">Start Shopping</a>
            </div>
        <?php else: ?>
            <div class="cart-layout">
                <div class="cart-items-section">
                    <div class="section-card">
                        <h2>Cart Items (<?php echo count($cart_items); ?>)</h2>
                        <form method="POST" id="cart-form">
                            <?php foreach($cart_items as $item): ?>
                                <div class="cart-item">
                                    <div class="cart-item-image">
                                        <?php if($item['image'] && file_exists($item['image'])): ?>
                                            <img src="<?php echo $item['image']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                        <?php else: ?>
                                            <div class="cart-placeholder"><i class="fas fa-box"></i></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="cart-item-details">
                                        <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                        <p class="cart-item-category"><?php echo htmlspecialchars($item['category']); ?></p>
                                        <p class="cart-item-rdc"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($item['rdc_location']); ?></p>
                                        <p class="cart-item-stock">Stock: <?php echo $item['stock']; ?> units available</p>
                                    </div>
                                    <div class="cart-item-quantity">
                                        <label>Quantity</label>
                                        <input type="number" name="quantity[<?php echo $item['id']; ?>]" 
                                               value="<?php echo $item['quantity']; ?>" 
                                               min="1" max="<?php echo $item['stock']; ?>" 
                                               class="quantity-input-large"
                                               onchange="document.getElementById('cart-form').submit()">
                                    </div>
                                    <div class="cart-item-price">
                                        <p class="cart-item-unit-price"><span class="price-display" data-price="<?php echo $item['price']; ?>">$<?php echo number_format($item['price'], 2); ?></span> each</p>
                                        <p class="cart-item-total-price"><span class="price-display" data-price="<?php echo $item['line_total']; ?>">$<?php echo number_format($item['line_total'], 2); ?></span></p>
                                    </div>
                                    <div class="cart-item-remove">
                                        <button type="submit" name="remove_item" value="1" 
                                                onclick="event.preventDefault(); removeItem(<?php echo $item['id']; ?>)"
                                                class="btn-remove" title="Remove item">×</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <input type="hidden" name="update_cart" value="1">
                        </form>
                        <div class="cart-actions">
                            <form method="POST" style="display:inline;">
                                <button type="submit" name="clear_cart" class="btn-secondary" 
                                        onclick="return confirm('Clear all items from cart?')">
                                    Clear Cart
                                </button>
                            </form>
                            <a href="products.php" class="btn-secondary">Continue Shopping</a>
                        </div>
                    </div>
                </div>

                <div class="cart-summary-section">
                    <div class="section-card cart-summary">
                        <h2>Order Summary</h2>
                        <div class="summary-line">
                            <span>Subtotal (<?php echo array_sum($cart); ?> items)</span>
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
                        <?php if($subtotal < 100 && $shipping > 0): ?>
                            <div class="shipping-notice">
                                <small><i class="fas fa-lightbulb"></i> Add <span class="price-display" data-price="<?php echo (100 - $subtotal); ?>">$<?php echo number_format(100 - $subtotal, 2); ?></span> more for FREE shipping!</small>
                            </div>
                        <?php endif; ?>
                        <div class="summary-divider"></div>
                        <div class="summary-total">
                            <span>Total</span>
                            <span class="price-display" data-price="<?php echo $total; ?>">$<?php echo number_format($total, 2); ?></span>
                        </div>
                        <form method="POST" action="checkout.php">
                            <button type="submit" class="btn-checkout">
                                <span>Proceed to Checkout</span>
                                <span class="btn-arrow">→</span>
                            </button>
                        </form>
                        <div class="trust-badges">
                            <div class="trust-badge"><i class="fas fa-lock"></i> Secure Checkout</div>
                            <div class="trust-badge"><i class="fas fa-shipping-fast"></i> Fast Delivery</div>
                            <div class="trust-badge"><i class="fas fa-check-circle"></i> Quality Assured</div>
                        </div>
                    </div>

                    <div class="section-card delivery-info">
                        <h3><i class="fas fa-box"></i> Delivery Information</h3>
                        <ul class="delivery-details">
                            <li><i class="fas fa-check"></i> Delivery within 24-48 hours</li>
                            <li><i class="fas fa-check"></i> Track your shipment in real-time</li>
                            <li><i class="fas fa-check"></i> 5 Regional Distribution Centres</li>
                            <li><i class="fas fa-check"></i> Serving 5,000+ retail outlets</li>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'footer.php'; ?>

    <script>
    function removeItem(productId) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="remove_item" value="1">' +
                        '<input type="hidden" name="product_id" value="' + productId + '">';
        document.body.appendChild(form);
        form.submit();
    }
    </script>
</body>
</html>
<?php
function removeItemFromCart($product_id) {
    if (isset($_POST['product_id']) && $_POST['product_id'] == $product_id) {
        return true;
    }
    return false;
}
?>
