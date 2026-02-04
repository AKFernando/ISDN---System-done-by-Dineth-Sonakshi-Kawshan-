<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);
        
        $profile_image = null;
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            $target_dir = "uploads/profiles/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $image_extension = pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION);
            $new_filename = "profile_" . $user_id . "_" . time() . "." . $image_extension;
            $target_file = $target_dir . $new_filename;
            
            if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
                $profile_image = $target_file;
            }
        }
        
        if ($profile_image) {
            $query = "UPDATE users SET name='$name', email='$email', phone='$phone', profile_image='$profile_image' WHERE id=$user_id";
        } else {
            $query = "UPDATE users SET name='$name', email='$email', phone='$phone' WHERE id=$user_id";
        }
        
        if (mysqli_query($conn, $query)) {
            $_SESSION['name'] = $name;
            $message = 'Profile updated successfully';
        } else {
            $error = 'Failed to update profile';
        }
    }
    
    if (isset($_POST['change_password'])) {
        $current_password = md5($_POST['current_password']);
        $new_password = md5($_POST['new_password']);
        $confirm_password = md5($_POST['confirm_password']);
        
        $check_query = "SELECT password FROM users WHERE id=$user_id";
        $check_result = mysqli_query($conn, $check_query);
        $user_data = mysqli_fetch_assoc($check_result);
        
        if ($user_data['password'] != $current_password) {
            $error = 'Current password is incorrect';
        } elseif ($_POST['new_password'] != $_POST['confirm_password']) {
            $error = 'New passwords do not match';
        } else {
            $query = "UPDATE users SET password='$new_password' WHERE id=$user_id";
            if (mysqli_query($conn, $query)) {
                $message = 'Password changed successfully';
            } else {
                $error = 'Failed to change password';
            }
        }
    }
}

$user_query = "SELECT * FROM users WHERE id=$user_id";
$user_result = mysqli_query($conn, $user_query);
$user = mysqli_fetch_assoc($user_result);

if ($role == 'customer') {
    $stats_query = "SELECT COUNT(*) as total_orders, SUM(total) as total_spent FROM orders WHERE customer_id=$user_id";
    $stats_result = mysqli_query($conn, $stats_query);
    $stats = mysqli_fetch_assoc($stats_result);
}

if ($role == 'rdc') {
    $deliveries_query = "SELECT COUNT(*) as total_deliveries, 
        SUM(CASE WHEN status='delivered' THEN 1 ELSE 0 END) as completed_deliveries 
        FROM deliveries WHERE rdc_staff_id=$user_id";
    $deliveries_result = mysqli_query($conn, $deliveries_query);
    $delivery_stats = mysqli_fetch_assoc($deliveries_result);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - ISDN</title>
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
        <?php if($error): ?>
            <div class="alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="profile-header">
            <div class="profile-avatar">
                <?php if(isset($user['profile_image']) && $user['profile_image'] && file_exists($user['profile_image'])): ?>
                    <img src="<?php echo $user['profile_image']; ?>" alt="Profile Picture" class="avatar-large">
                <?php else: ?>
                    <div class="avatar-placeholder-large">👤</div>
                <?php endif; ?>
            </div>
            <div class="profile-info">
                <h1><?php echo htmlspecialchars($user['name']); ?></h1>
                <p><span class="badge"><?php echo strtoupper($user['role']); ?></span></p>
                <p>@<?php echo htmlspecialchars($user['username']); ?></p>
            </div>
        </div>

        <?php if($role == 'customer' && isset($stats)): ?>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-file-invoice"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total_orders'] ?? 0; ?></h3>
                        <p>Total Orders</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
                    <div class="stat-info">
                        <h3><span class="price-display" data-price="<?php echo $stats['total_spent'] ?? 0; ?>">$<?php echo number_format($stats['total_spent'] ?? 0, 2); ?></span></h3>
                        <p>Total Spent</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if($role == 'rdc' && isset($delivery_stats)): ?>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-box"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $delivery_stats['total_deliveries'] ?? 0; ?></h3>
                        <p>Total Deliveries</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $delivery_stats['completed_deliveries'] ?? 0; ?></h3>
                        <p>Completed</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="profile-grid">
            <div class="section-card">
                <h2><i class="fas fa-edit"></i> Edit Profile</h2>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Profile Picture</label>
                        <input type="file" name="profile_image" accept="image/*">
                        <small style="color: #666;">Upload a new profile picture (JPG, PNG)</small>
                    </div>
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                    </div>
                    <?php if($role == 'rdc'): ?>
                        <div class="form-group">
                            <label>RDC Location</label>
                            <input type="text" value="<?php echo htmlspecialchars($user['rdc_location']); ?>" disabled>
                            <small style="color: #666;">Contact admin to change RDC location</small>
                        </div>
                    <?php endif; ?>
                    <button type="submit" name="update_profile" class="btn-primary">Update Profile</button>
                </form>
            </div>

            <div class="section-card">
                <h2><i class="fas fa-lock"></i> Change Password</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Current Password</label>
                        <input type="password" name="current_password" required>
                    </div>
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="new_password" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" required minlength="6">
                    </div>
                    <button type="submit" name="change_password" class="btn-primary">Change Password</button>
                </form>
            </div>
        </div>

        <div class="section-card">
            <h2><i class="fas fa-info-circle"></i> Account Information</h2>
            <table class="info-table">
                <tr>
                    <td><strong>Username:</strong></td>
                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                </tr>
                <tr>
                    <td><strong>Role:</strong></td>
                    <td><span class="badge"><?php echo strtoupper($user['role']); ?></span></td>
                </tr>
                <tr>
                    <td><strong>Member Since:</strong></td>
                    <td><?php echo date('F j, Y', strtotime($user['created_at'])); ?></td>
                </tr>
                <?php if($role == 'rdc'): ?>
                <tr>
                    <td><strong>RDC Location:</strong></td>
                    <td><?php echo htmlspecialchars($user['rdc_location']); ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>
