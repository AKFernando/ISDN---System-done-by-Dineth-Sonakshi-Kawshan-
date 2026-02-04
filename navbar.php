<?php
if (!isset($_SESSION)) {
    session_start();
}
$current_page = basename($_SERVER['PHP_SELF']);
$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : array();
?>
<nav class="navbar">
    <div class="nav-container">
        <a href="index.php" class="nav-logo">
            <i class="fas fa-shield-alt"></i>
            IslandLink ISDN
        </a>
        <div class="nav-links">
            <a href="index.php" class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> <span>Home</span>
            </a>
            
            <?php if($role == 'admin'): ?>
                <a href="dashboard.php" class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i> <span>Dashboard</span>
                </a>
                <a href="products.php" class="nav-link <?php echo $current_page == 'products.php' ? 'active' : ''; ?>">
                    <i class="fas fa-boxes"></i> <span>Products</span>
                </a>
                <a href="orders.php" class="nav-link <?php echo $current_page == 'orders.php' ? 'active' : ''; ?>">
                    <i class="fas fa-file-invoice"></i> <span>Orders</span>
                </a>
                <a href="delivery.php" class="nav-link <?php echo $current_page == 'delivery.php' ? 'active' : ''; ?>">
                    <i class="fas fa-truck"></i> <span>Deliveries</span>
                </a>
                <a href="users.php" class="nav-link <?php echo $current_page == 'users.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i> <span>Users</span>
                </a>
                <a href="analytics.php" class="nav-link <?php echo ($current_page == 'analytics.php' || $current_page == 'advanced-analytics.php') ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar"></i> <span>Analytics</span>
                </a>
                <a href="profile.php" class="nav-link <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-circle"></i> <span>Profile</span>
                </a>
                <a href="logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                </a>
            
            <?php elseif($role == 'rdc'): ?>
                <a href="dashboard.php" class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i> <span>Dashboard</span>
                </a>
                <a href="products.php" class="nav-link <?php echo $current_page == 'products.php' ? 'active' : ''; ?>">
                    <i class="fas fa-warehouse"></i> <span>Stock</span>
                </a>
                <a href="delivery.php" class="nav-link <?php echo $current_page == 'delivery.php' ? 'active' : ''; ?>">
                    <i class="fas fa-truck"></i> <span>My Deliveries</span>
                </a>
                <a href="orders.php" class="nav-link <?php echo $current_page == 'orders.php' ? 'active' : ''; ?>">
                    <i class="fas fa-clipboard-list"></i> <span>Orders</span>
                </a>
                <a href="profile.php" class="nav-link <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-circle"></i> <span>Profile</span>
                </a>
                <a href="logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                </a>
            
            <?php elseif($role == 'customer'): ?>
                <a href="products.php" class="nav-link <?php echo $current_page == 'products.php' ? 'active' : ''; ?>">
                    <i class="fas fa-store"></i> <span>Shop</span>
                </a>
                <a href="cart.php" class="nav-link <?php echo $current_page == 'cart.php' ? 'active' : ''; ?>">
                    <i class="fas fa-shopping-cart"></i> <span>Cart</span>
                    <?php if(!empty($cart)): ?>
                        <span class="cart-badge"><?php echo array_sum($cart); ?></span>
                    <?php endif; ?>
                </a>
                <a href="orders.php" class="nav-link <?php echo $current_page == 'orders.php' ? 'active' : ''; ?>">
                    <i class="fas fa-receipt"></i> <span>My Orders</span>
                </a>
                <a href="track-order.php" class="nav-link <?php echo $current_page == 'track-order.php' ? 'active' : ''; ?>">
                    <i class="fas fa-map-marked-alt"></i> <span>Track</span>
                </a>
                <a href="profile.php" class="nav-link <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-circle"></i> <span>Profile</span>
                </a>
                <a href="logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                </a>
            
            <?php else: ?>
                <a href="login.php" class="nav-link <?php echo $current_page == 'login.php' ? 'active' : ''; ?>">
                    <i class="fas fa-sign-in-alt"></i> <span>Login</span>
                </a>
                <a href="signup.php" class="nav-link <?php echo $current_page == 'signup.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-plus"></i> <span>Sign Up</span>
                </a>
            <?php endif; ?>
            
            <?php include 'navbar-controls.php'; ?>
        </div>
    </div>
</nav>

