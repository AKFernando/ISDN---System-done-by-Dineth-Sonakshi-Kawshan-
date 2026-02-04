<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: dashboard.php');
    exit();
}

$type = isset($_GET['type']) ? $_GET['type'] : 'orders';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=ISDN_' . $type . '_' . date('Y-m-d') . '.csv');

$output = fopen('php://output', 'w');

if ($type == 'orders') {
    fputcsv($output, ['Order ID', 'Customer', 'Total (USD)', 'Status', 'Order Date']);
    
    $orders = mysqli_query($conn, "SELECT o.*, u.name as customer_name FROM orders o JOIN users u ON o.customer_id = u.id ORDER BY o.order_date DESC");
    
    while ($row = mysqli_fetch_assoc($orders)) {
        fputcsv($output, [
            $row['id'],
            $row['customer_name'],
            number_format($row['total'], 2),
            strtoupper($row['status']),
            date('Y-m-d H:i:s', strtotime($row['order_date']))
        ]);
    }
} elseif ($type == 'products') {
    fputcsv($output, ['Product ID', 'Name', 'Category', 'Price (USD)', 'Stock', 'RDC Location']);
    
    $products = mysqli_query($conn, "SELECT * FROM products ORDER BY id DESC");
    
    while ($row = mysqli_fetch_assoc($products)) {
        fputcsv($output, [
            $row['id'],
            $row['name'],
            $row['category'],
            number_format($row['price'], 2),
            $row['stock'],
            $row['rdc_location']
        ]);
    }
} elseif ($type == 'users') {
    fputcsv($output, ['User ID', 'Username', 'Name', 'Role', 'Email', 'Phone', 'RDC Location']);
    
    $users = mysqli_query($conn, "SELECT * FROM users ORDER BY id DESC");
    
    while ($row = mysqli_fetch_assoc($users)) {
        fputcsv($output, [
            $row['id'],
            $row['username'],
            $row['name'],
            strtoupper($row['role']),
            $row['email'],
            $row['phone'],
            $row['rdc_location'] ?? 'N/A'
        ]);
    }
}

fclose($output);
exit();
?>
