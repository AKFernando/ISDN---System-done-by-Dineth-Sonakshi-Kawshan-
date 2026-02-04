<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'rdc' && $_SESSION['role'] != 'admin')) {
    header('Location: dashboard.php');
    exit();
}

$role = $_SESSION['role'];

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_rdc'])) {
    $delivery_id = intval($_POST['delivery_id']);
    $rdc_staff_id = intval($_POST['rdc_staff_id']);
    
    if ($rdc_staff_id > 0) {
        mysqli_query($conn, "UPDATE deliveries SET rdc_staff_id=$rdc_staff_id, assigned_date=NOW() WHERE id=$delivery_id");
        $message = 'RDC staff assigned successfully';
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_delivery'])) {
    $delivery_id = intval($_POST['delivery_id']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    $delivery_query = mysqli_query($conn, "SELECT d.*, o.id as order_id, u.email, u.name as customer_name FROM deliveries d JOIN orders o ON d.order_id = o.id JOIN users u ON o.customer_id = u.id WHERE d.id = $delivery_id");
    $delivery_info = mysqli_fetch_assoc($delivery_query);
    
    $update_query = "UPDATE deliveries SET status='$status'";
    if ($status == 'delivered') {
        $update_query .= ", delivered_date=NOW()";
    }
    if ($role == 'rdc') {
        $update_query .= " WHERE id=$delivery_id AND rdc_staff_id=" . $_SESSION['user_id'];
    } else {
        $update_query .= " WHERE id=$delivery_id";
    }
    
    if (mysqli_query($conn, $update_query)) {
        mysqli_query($conn, "UPDATE orders SET status='$status' WHERE id=" . $delivery_info['order_id']);
        
        if (in_array($status, ['dispatched', 'delivered']) && $delivery_info['email']) {
            include_once 'send-email.php';
            sendDeliveryNotification($delivery_info['email'], $delivery_info['customer_name'], $delivery_info['order_id'], $status);
        }
        
        $message = 'Delivery status updated';
    }
}

if ($role == 'admin') {
    $rdc_staff = mysqli_query($conn, "SELECT id, name, rdc_location FROM users WHERE role='rdc'");
    $deliveries = mysqli_query($conn, "SELECT d.*, o.id as order_id, o.total, o.order_date, u.name as customer_name, u.phone, u.email, staff.name as rdc_staff_name, staff.rdc_location 
        FROM deliveries d 
        JOIN orders o ON d.order_id = o.id 
        JOIN users u ON o.customer_id = u.id 
        LEFT JOIN users staff ON d.rdc_staff_id = staff.id 
        ORDER BY d.assigned_date DESC");
} else {
    $deliveries = mysqli_query($conn, "SELECT d.*, o.id as order_id, o.total, o.order_date, u.name as customer_name, u.phone, u.email 
        FROM deliveries d 
        JOIN orders o ON d.order_id = o.id 
        JOIN users u ON o.customer_id = u.id 
        WHERE d.rdc_staff_id = " . $_SESSION['user_id'] . " 
        ORDER BY d.assigned_date DESC");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deliveries - ISDN</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <script src="theme-currency.js"></script>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-truck"></i> <?php echo $role == 'admin' ? 'All Deliveries' : 'My Assigned Deliveries'; ?></h1>
            <?php if($role == 'rdc'): ?>
                <p>RDC Location: <span class="badge"><?php echo htmlspecialchars($_SESSION['rdc_location']); ?></span></p>
            <?php else: ?>
                <p>Manage and monitor deliveries across all RDCs</p>
            <?php endif; ?>
        </div>

        <?php if($message): ?>
            <div class="alert-success"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="section-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Contact</th>
                        <?php if($role == 'admin'): ?>
                            <th>RDC / Staff</th>
                        <?php endif; ?>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Order Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($delivery = mysqli_fetch_assoc($deliveries)): ?>
                        <tr>
                            <td>#<?php echo $delivery['order_id']; ?></td>
                            <td><?php echo htmlspecialchars($delivery['customer_name']); ?></td>
                            <td><?php echo htmlspecialchars($delivery['phone']) . '<br>' . htmlspecialchars($delivery['email']); ?></td>
                            <?php if($role == 'admin'): ?>
                                <td>
                                    <?php if($delivery['rdc_staff_name']): ?>
                                        <i class="fas fa-user-tie"></i> <?php echo htmlspecialchars($delivery['rdc_staff_name']); ?><br>
                                        <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($delivery['rdc_location']); ?>
                                    <?php else: ?>
                                        <span class="badge">Unassigned</span>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                            <td><span class="price-display" data-price="<?php echo $delivery['total']; ?>">$<?php echo number_format($delivery['total'], 2); ?></span></td>
                            <td><span class="badge badge-<?php echo $delivery['status']; ?>"><?php echo strtoupper($delivery['status']); ?></span></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($delivery['order_date'])); ?></td>
                            <td>
                                <?php if($delivery['status'] != 'delivered' && ($role == 'rdc' || $role == 'admin')): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="delivery_id" value="<?php echo $delivery['id']; ?>">
                                        <select name="status" class="inline-select">
                                            <option value="pending" <?php echo $delivery['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="dispatched" <?php echo $delivery['status'] == 'dispatched' ? 'selected' : ''; ?>>Dispatched</option>
                                            <option value="delivered" <?php echo $delivery['status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                        </select>
                                        <button type="submit" name="update_delivery" class="btn-small btn-primary">Update</button>
                                    </form>
                                    <?php if($role == 'admin' && !$delivery['rdc_staff_id']): ?>
                                        <form method="POST" style="display:inline; margin-top:5px;">
                                            <input type="hidden" name="delivery_id" value="<?php echo $delivery['id']; ?>">
                                            <select name="rdc_staff_id" class="inline-select">
                                                <option value="">Assign RDC Staff</option>
                                                <?php mysqli_data_seek($rdc_staff, 0); ?>
                                                <?php while($staff = mysqli_fetch_assoc($rdc_staff)): ?>
                                                    <option value="<?php echo $staff['id']; ?>"><?php echo htmlspecialchars($staff['name']) . ' (' . htmlspecialchars($staff['rdc_location']) . ')'; ?></option>
                                                <?php endwhile; ?>
                                            </select>
                                            <button type="submit" name="assign_rdc" class="btn-small btn-secondary">Assign</button>
                                        </form>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge badge-delivered"><i class="fas fa-check-circle"></i> Completed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>
