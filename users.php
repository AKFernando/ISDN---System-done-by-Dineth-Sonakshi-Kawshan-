<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: dashboard.php');
    exit();
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_user'])) {
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $password = md5($_POST['password']);
        $role = mysqli_real_escape_string($conn, $_POST['role']);
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);
        $rdc_location = ($role == 'rdc') ? mysqli_real_escape_string($conn, $_POST['rdc_location']) : 'NULL';
        
        $query = "INSERT INTO users (username, password, role, name, email, phone, rdc_location) VALUES ('$username', '$password', '$role', '$name', '$email', '$phone', " . ($rdc_location != 'NULL' ? "'$rdc_location'" : "NULL") . ")";
        if (mysqli_query($conn, $query)) {
            $message = 'User added successfully';
        } else {
            $message = 'Error: Username already exists';
        }
    }
    
    if (isset($_POST['edit_user'])) {
        $id = intval($_POST['user_id']);
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $role = mysqli_real_escape_string($conn, $_POST['role']);
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);
        $rdc_location = ($role == 'rdc') ? mysqli_real_escape_string($conn, $_POST['rdc_location']) : 'NULL';
        
        $query = "UPDATE users SET username='$username', role='$role', name='$name', email='$email', phone='$phone', rdc_location=" . ($rdc_location != 'NULL' ? "'$rdc_location'" : "NULL") . " WHERE id=$id";
        if (mysqli_query($conn, $query)) {
            $message = 'User updated successfully';
        }
    }
    
    if (isset($_POST['delete_user'])) {
        $id = intval($_POST['user_id']);
        if ($id != $_SESSION['user_id']) {
            $query = "DELETE FROM users WHERE id=$id";
            if (mysqli_query($conn, $query)) {
                $message = 'User deleted successfully';
            }
        }
    }
}

$users = mysqli_query($conn, "SELECT * FROM users ORDER BY id DESC");
$edit_user = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id=$edit_id"));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - ISDN</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <script src="theme-currency.js"></script>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1><?php echo $edit_user ? 'Edit User' : 'Add New User'; ?></h1>
        </div>

        <?php if($message): ?>
            <div class="alert-success"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="section-card">
            <form method="POST">
                <?php if($edit_user): ?>
                    <input type="hidden" name="user_id" value="<?php echo $edit_user['id']; ?>">
                <?php endif; ?>
                <div class="form-row">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" value="<?php echo $edit_user ? htmlspecialchars($edit_user['username']) : ''; ?>" required>
                    </div>
                    <?php if(!$edit_user): ?>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" required>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="name" value="<?php echo $edit_user ? htmlspecialchars($edit_user['name']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <select name="role" id="role" required onchange="toggleRDC()">
                            <option value="">Select Role</option>
                            <option value="admin" <?php echo ($edit_user && $edit_user['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                            <option value="rdc" <?php echo ($edit_user && $edit_user['role'] == 'rdc') ? 'selected' : ''; ?>>RDC Staff</option>
                            <option value="customer" <?php echo ($edit_user && $edit_user['role'] == 'customer') ? 'selected' : ''; ?>>Customer</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?php echo $edit_user ? htmlspecialchars($edit_user['email']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" value="<?php echo $edit_user ? htmlspecialchars($edit_user['phone']) : ''; ?>">
                    </div>
                </div>
                <div class="form-group" id="rdc_field" style="<?php echo ($edit_user && $edit_user['role'] == 'rdc') ? '' : 'display:none;'; ?>">
                    <label>RDC Location</label>
                    <select name="rdc_location">
                        <option value="">Select RDC</option>
                        <option value="North RDC" <?php echo ($edit_user && $edit_user['rdc_location'] == 'North RDC') ? 'selected' : ''; ?>>North RDC</option>
                        <option value="South RDC" <?php echo ($edit_user && $edit_user['rdc_location'] == 'South RDC') ? 'selected' : ''; ?>>South RDC</option>
                        <option value="East RDC" <?php echo ($edit_user && $edit_user['rdc_location'] == 'East RDC') ? 'selected' : ''; ?>>East RDC</option>
                        <option value="West RDC" <?php echo ($edit_user && $edit_user['rdc_location'] == 'West RDC') ? 'selected' : ''; ?>>West RDC</option>
                    </select>
                </div>
                <?php if($edit_user): ?>
                    <button type="submit" name="edit_user" class="btn-primary">Update User</button>
                    <a href="users.php" class="btn-secondary">Cancel</a>
                <?php else: ?>
                    <button type="submit" name="add_user" class="btn-primary">Add User</button>
                <?php endif; ?>
            </form>
        </div>

        <div class="page-header" style="margin-top: 40px;">
            <h1>All Users</h1>
        </div>

        <div class="section-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>RDC Location</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php mysqli_data_seek($users, 0); ?>
                    <?php while($user = mysqli_fetch_assoc($users)): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                            <td><span class="badge"><?php echo strtoupper($user['role']); ?></span></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['phone']); ?></td>
                            <td><?php echo $user['rdc_location'] ? htmlspecialchars($user['rdc_location']) : '-'; ?></td>
                            <td>
                                <a href="users.php?edit=<?php echo $user['id']; ?>" class="btn-small btn-secondary">Edit</a>
                                <?php if($user['id'] != $_SESSION['user_id']): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" name="delete_user" class="btn-small btn-danger" onclick="return confirm('Delete this user?')">Delete</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    function toggleRDC() {
        var role = document.getElementById('role').value;
        var rdcField = document.getElementById('rdc_field');
        if (role === 'rdc') {
            rdcField.style.display = 'block';
        } else {
            rdcField.style.display = 'none';
        }
    }
    </script>

    <?php include 'footer.php'; ?>
</body>
</html>
