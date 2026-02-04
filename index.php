<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISDN - IslandLink Sales Distribution Network</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <script src="theme-currency.js"></script>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="hero">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1 class="hero-title">IslandLink Sales Distribution Network</h1>
            <p class="hero-subtitle">Transforming Distribution Management Across the Islands</p>
            <p class="hero-description">Streamline your operations with our comprehensive web-based distribution management system</p>
            <div class="hero-buttons">
                <?php if(!isset($_SESSION['user_id'])): ?>
                    <a href="signup.php" class="btn-hero-primary">Get Started</a>
                    <a href="login.php" class="btn-hero-secondary">Sign In</a>
                <?php else: ?>
                    <a href="dashboard.php" class="btn-hero-primary">Go to Dashboard</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="hero-scroll">
            <span>Scroll to explore</span>
            <div class="scroll-arrow">↓</div>
        </div>
    </div>

    <div class="container">
        <div class="section-header">
            <h2>Our Solutions</h2>
            <p>Powerful features designed for modern distribution management</p>
        </div>

        <div class="features">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-box"></i>
                </div>
                <h3>Product Management</h3>
                <p>Comprehensive catalog management across multiple distribution centers with real-time inventory tracking</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-file-invoice"></i>
                </div>
                <h3>Order Processing</h3>
                <p>Streamlined order placement and tracking system with automated workflows</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-truck"></i>
                </div>
                <h3>Delivery Management</h3>
                <p>Real-time delivery status updates and route optimization</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3>Analytics Dashboard</h3>
                <p>Insightful statistics and inventory monitoring with actionable insights</p>
            </div>
        </div>

        <div class="stats-section">
            <div class="stats-container">
                <div class="stat-item">
                    <i class="fas fa-server stat-item-icon"></i>
                    <h3 class="stat-number">99.9%</h3>
                    <p class="stat-label">Uptime</p>
                </div>
                <div class="stat-item">
                    <i class="fas fa-headset stat-item-icon"></i>
                    <h3 class="stat-number">24/7</h3>
                    <p class="stat-label">Support</p>
                </div>
                <div class="stat-item">
                    <i class="fas fa-shopping-cart stat-item-icon"></i>
                    <h3 class="stat-number">1000+</h3>
                    <p class="stat-label">Orders Daily</p>
                </div>
                <div class="stat-item">
                    <i class="fas fa-warehouse stat-item-icon"></i>
                    <h3 class="stat-number">50+</h3>
                    <p class="stat-label">RDC Locations</p>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>
