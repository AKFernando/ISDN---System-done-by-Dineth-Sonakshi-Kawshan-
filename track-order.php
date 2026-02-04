<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header('Location: login.php');
    exit();
}

$customer_id = $_SESSION['user_id'];
$customer_query = mysqli_query($conn, "SELECT name FROM users WHERE id = $customer_id");
$customer_data = mysqli_fetch_assoc($customer_query);
$customer_name = $customer_data['name'] ?? 'Customer';

$orders = mysqli_query($conn, "SELECT o.*, d.status as delivery_status, d.assigned_date, d.delivered_date, d.rdc_staff_id, u.name as rdc_staff_name, u.rdc_location 
    FROM orders o 
    LEFT JOIN deliveries d ON o.id = d.order_id 
    LEFT JOIN users u ON d.rdc_staff_id = u.id 
    WHERE o.customer_id = $customer_id 
    ORDER BY o.order_date DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Order - ISDN</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <link rel="stylesheet" href="style.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="theme-currency.js"></script>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-map-marked-alt"></i> Track Your Orders</h1>
            <p>Real-time GPS tracking and delivery status updates</p>
        </div>

        <?php while($order = mysqli_fetch_assoc($orders)): ?>
            <div class="tracking-card">
                <div class="tracking-header">
                    <div class="tracking-order-info">
                        <h3>Order #<?php echo $order['id']; ?></h3>
                        <p>Placed on <?php echo date('F j, Y, g:i a', strtotime($order['order_date'])); ?></p>
                    </div>
                    <div class="tracking-amount">
                        <span class="price-display" data-price="<?php echo $order['total']; ?>">$<?php echo number_format($order['total'], 2); ?></span>
                    </div>
                </div>

                <div class="tracking-timeline">
                    <div class="timeline-step <?php echo $order['status'] ? 'completed' : ''; ?>">
                        <div class="timeline-icon"><i class="fas fa-check-circle"></i></div>
                        <div class="timeline-content">
                            <h4>Order Placed</h4>
                            <p><?php echo date('M j, Y H:i', strtotime($order['order_date'])); ?></p>
                        </div>
                    </div>
                    <div class="timeline-line <?php echo ($order['status'] == 'dispatched' || $order['status'] == 'delivered') ? 'completed' : ''; ?>"></div>
                    <div class="timeline-step <?php echo ($order['status'] == 'dispatched' || $order['status'] == 'delivered') ? 'completed' : ($order['status'] == 'pending' ? 'active' : ''); ?>">
                        <div class="timeline-icon"><i class="fas fa-box"></i></div>
                        <div class="timeline-content">
                            <h4>Processing at RDC</h4>
                            <p><?php echo $order['rdc_location'] ? htmlspecialchars($order['rdc_location']) : 'Being assigned'; ?></p>
                            <?php if($order['assigned_date']): ?>
                                <p class="timeline-time"><?php echo date('M j, Y H:i', strtotime($order['assigned_date'])); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="timeline-line <?php echo $order['status'] == 'delivered' ? 'completed' : ''; ?>"></div>
                    <div class="timeline-step <?php echo $order['status'] == 'dispatched' ? 'active' : ($order['status'] == 'delivered' ? 'completed' : ''); ?>">
                        <div class="timeline-icon"><i class="fas fa-truck"></i></div>
                        <div class="timeline-content">
                            <h4>Out for Delivery</h4>
                            <p><?php echo $order['rdc_staff_name'] ? 'Driver: ' . htmlspecialchars($order['rdc_staff_name']) : 'Awaiting dispatch'; ?></p>
                        </div>
                    </div>
                    <div class="timeline-line <?php echo $order['status'] == 'delivered' ? 'completed' : ''; ?>"></div>
                    <div class="timeline-step <?php echo $order['status'] == 'delivered' ? 'completed' : ''; ?>">
                        <div class="timeline-icon"><i class="fas fa-home"></i></div>
                        <div class="timeline-content">
                            <h4>Delivered</h4>
                            <?php if($order['delivered_date']): ?>
                                <p class="timeline-time"><?php echo date('M j, Y H:i', strtotime($order['delivered_date'])); ?></p>
                            <?php else: ?>
                                <p>Estimated: 24-48 hours</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="tracking-status">
                    <span class="status-badge status-<?php echo $order['status']; ?>">
                        <?php if($order['status'] == 'pending'): ?>
                            <i class="fas fa-clock"></i> Processing
                        <?php elseif($order['status'] == 'dispatched'): ?>
                            <i class="fas fa-shipping-fast"></i> In Transit
                        <?php else: ?>
                            <i class="fas fa-check-circle"></i> Delivered
                        <?php endif; ?>
                    </span>
                </div>

                <?php if($order['status'] == 'dispatched' || $order['status'] == 'delivered'): ?>
                    <div class="gps-map-section">
                        <h3><i class="fas fa-map-marked-alt"></i> Live GPS Tracking</h3>
                        <div id="map-<?php echo $order['id']; ?>" class="delivery-map"></div>
                        <p class="map-info">
                            <i class="fas fa-truck"></i> 
                            <?php if($order['status'] == 'delivered'): ?>
                                Delivery completed
                            <?php else: ?>
                                Delivery vehicle en route to your location
                            <?php endif; ?>
                        </p>
                    </div>
                    <script>
                        (function() {
                            const mapId = 'map-<?php echo $order['id']; ?>';
                            const mapElement = document.getElementById(mapId);
                            if (!mapElement) return;
                            
                            const map = L.map(mapId).setView([6.9271, 79.8612], 11);
                            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                attribution: '© OpenStreetMap contributors',
                                maxZoom: 19
                            }).addTo(map);
                            
                            const rdcLat = 6.9271;
                            const rdcLng = 79.8612;
                            const customerLat = 6.9500;
                            const customerLng = 79.9000;
                            
                            const rdc = L.marker([rdcLat, rdcLng], {
                                icon: L.icon({
                                    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-blue.png',
                                    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
                                    iconSize: [25, 41],
                                    iconAnchor: [12, 41],
                                    popupAnchor: [1, -34],
                                    shadowSize: [41, 41]
                                })
                            }).addTo(map).bindPopup('<b><i class="fas fa-warehouse"></i> <?php echo htmlspecialchars($order['rdc_location'] ?? 'RDC'); ?></b><br>Distribution Centre');
                            
                            const customer = L.marker([customerLat, customerLng], {
                                icon: L.icon({
                                    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-green.png',
                                    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
                                    iconSize: [25, 41],
                                    iconAnchor: [12, 41],
                                    popupAnchor: [1, -34],
                                    shadowSize: [41, 41]
                                })
                            }).addTo(map).bindPopup('<b><i class="fas fa-home"></i> Delivery Location</b><br><?php echo htmlspecialchars($customer_name); ?>');
                            
                            <?php if($order['status'] == 'dispatched'): ?>
                            const deliveryLat = 6.9350;
                            const deliveryLng = 79.8800;
                            const delivery = L.marker([deliveryLat, deliveryLng], {
                                icon: L.icon({
                                    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png',
                                    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
                                    iconSize: [25, 41],
                                    iconAnchor: [12, 41],
                                    popupAnchor: [1, -34],
                                    shadowSize: [41, 41]
                                })
                            }).addTo(map).bindPopup('<b><i class="fas fa-truck"></i> Delivery Vehicle</b><br>En Route');
                            
                            L.polyline([
                                [rdcLat, rdcLng],
                                [deliveryLat, deliveryLng],
                                [customerLat, customerLng]
                            ], {color: '#667eea', weight: 3, opacity: 0.7}).addTo(map);
                            <?php else: ?>
                            L.polyline([
                                [rdcLat, rdcLng],
                                [customerLat, customerLng]
                            ], {color: '#27ae60', weight: 3, opacity: 0.7, dashArray: '5, 10'}).addTo(map);
                            <?php endif; ?>
                            
                            map.fitBounds([
                                [rdcLat, rdcLng],
                                [customerLat, customerLng]
                            ], {padding: [50, 50]});
                        })();
                    </script>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>
