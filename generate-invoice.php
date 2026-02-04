<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if ($order_id == 0) {
    header('Location: orders.php');
    exit();
}

$role = $_SESSION['role'];

if ($role == 'customer') {
    $order = mysqli_fetch_assoc(mysqli_query($conn, "SELECT o.*, u.name as customer_name, u.email, u.phone 
        FROM orders o 
        JOIN users u ON o.customer_id = u.id 
        WHERE o.id = $order_id AND o.customer_id = " . $_SESSION['user_id']));
} else {
    $order = mysqli_fetch_assoc(mysqli_query($conn, "SELECT o.*, u.name as customer_name, u.email, u.phone 
        FROM orders o 
        JOIN users u ON o.customer_id = u.id 
        WHERE o.id = $order_id"));
}

if (!$order) {
    header('Location: orders.php');
    exit();
}

$items = mysqli_query($conn, "SELECT oi.*, p.name, p.category 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = $order_id");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo $order_id; ?> - ISDN</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="theme-currency.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; padding: 20px; background: #f8f9fa; }
        .invoice-container { max-width: 800px; margin: 0 auto; background: white; padding: 40px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); }
        .invoice-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #667eea; padding-bottom: 20px; margin-bottom: 30px; }
        .company-info h1 { color: #667eea; margin-bottom: 10px; }
        .invoice-details { text-align: right; }
        .invoice-details h2 { color: #f5576c; }
        .bill-to { margin: 30px 0; }
        .bill-to h3 { color: #2c3e50; margin-bottom: 10px; }
        .items-table { width: 100%; border-collapse: collapse; margin: 30px 0; }
        .items-table th { background: #667eea; color: white; padding: 12px; text-align: left; }
        .items-table td { padding: 12px; border-bottom: 1px solid #e8ecef; }
        .total-section { text-align: right; margin-top: 30px; }
        .total-row { padding: 8px 0; }
        .grand-total { font-size: 1.5rem; color: #27ae60; font-weight: bold; padding-top: 10px; border-top: 2px solid #e8ecef; }
        .invoice-footer { margin-top: 40px; padding-top: 20px; border-top: 2px solid #e8ecef; text-align: center; color: #7f8c8d; }
        .print-btn { background: #667eea; color: white; border: none; padding: 12px 30px; border-radius: 5px; cursor: pointer; margin: 20px; }
        .print-btn:hover { background: #764ba2; }
        @media print { .print-btn, .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: center; margin-bottom: 20px;">
        <button onclick="window.print()" class="print-btn"><i class="fas fa-print"></i> Print / Save as PDF</button>
        <button onclick="exportToPDF()" class="print-btn" style="background: #e74c3c;"><i class="fas fa-file-pdf"></i> Download PDF</button>
        <a href="orders.php" onclick="if(window.history.length > 1) { window.history.back(); return false; }" class="print-btn" style="background: #95a5a6; text-decoration: none; display: inline-block;"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
    <script>
        function exportToPDF() {
            applyCurrencyToInvoice();
            setTimeout(() => {
                window.print();
            }, 100);
        }
        
        function applyCurrencyToInvoice() {
            const savedCurrency = localStorage.getItem('currency') || 'USD';
            const USD_TO_LKR = 320;
            
            document.querySelectorAll('.invoice-price[data-price]').forEach(element => {
                const basePrice = parseFloat(element.getAttribute('data-price'));
                if (isNaN(basePrice)) return;
                
                let displayPrice;
                let symbol;
                
                if (savedCurrency === 'LKR') {
                    displayPrice = (basePrice * USD_TO_LKR).toFixed(2);
                    symbol = 'Rs. ';
                    element.textContent = symbol + formatInvoiceNumber(displayPrice);
                } else {
                    displayPrice = basePrice.toFixed(2);
                    symbol = '$';
                    element.textContent = symbol + formatInvoiceNumber(displayPrice);
                }
            });
        }
        
        function formatInvoiceNumber(num) {
            return parseFloat(num).toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            applyCurrencyToInvoice();
        });
    </script>

    <div class="invoice-container">
        <div class="invoice-header">
            <div class="company-info">
                <h1><i class="fas fa-shield-alt"></i> IslandLink ISDN</h1>
                <p>123 Distribution Ave, Island City, IC 12345</p>
                <p>Phone: +1 (555) 123-4567</p>
                <p>Email: info@islandlink.com</p>
            </div>
            <div class="invoice-details">
                <h2>INVOICE</h2>
                <p><strong>Invoice #:</strong> <?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?></p>
                <p><strong>Date:</strong> <?php echo date('F j, Y', strtotime($order['order_date'])); ?></p>
                <p><strong>Status:</strong> <?php echo strtoupper($order['status']); ?></p>
            </div>
        </div>

        <div class="bill-to">
            <h3>Bill To:</h3>
            <p><strong><?php echo htmlspecialchars($order['customer_name']); ?></strong></p>
            <p><?php echo htmlspecialchars($order['email']); ?></p>
            <p><?php echo htmlspecialchars($order['phone']); ?></p>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Category</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $subtotal = 0;
                while($item = mysqli_fetch_assoc($items)): 
                    $line_total = $item['quantity'] * $item['price'];
                    $subtotal += $line_total;
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                        <td><?php echo htmlspecialchars($item['category']); ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td class="invoice-price" data-price="<?php echo $item['price']; ?>">$<?php echo number_format($item['price'], 2); ?></td>
                        <td class="invoice-price" data-price="<?php echo $line_total; ?>">$<?php echo number_format($line_total, 2); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <div class="total-section">
            <div class="total-row">
                <strong>Subtotal:</strong> <span class="invoice-price" data-price="<?php echo $subtotal; ?>">$<?php echo number_format($subtotal, 2); ?></span>
            </div>
            <div class="total-row">
                <strong>Tax (5%):</strong> <span class="invoice-price" data-price="<?php echo $subtotal * 0.05; ?>">$<?php echo number_format($subtotal * 0.05, 2); ?></span>
            </div>
            <div class="total-row">
                <strong>Shipping:</strong> 
                <?php 
                $shipping = $subtotal > 100 ? 0 : 15;
                if ($shipping > 0): 
                ?>
                    <span class="invoice-price" data-price="<?php echo $shipping; ?>">$<?php echo number_format($shipping, 2); ?></span>
                <?php else: ?>
                    <span>FREE</span>
                <?php endif; ?>
            </div>
            <div class="total-row grand-total">
                <strong>TOTAL:</strong> <span class="invoice-price" data-price="<?php echo $order['total']; ?>">$<?php echo number_format($order['total'], 2); ?></span>
            </div>
        </div>

        <div class="invoice-footer">
            <p><strong>Thank you for your business!</strong></p>
            <p>For inquiries, contact us at info@islandlink.com</p>
            <p style="margin-top: 15px; font-size: 0.85rem;">This is a computer-generated invoice. No signature required.</p>
        </div>
    </div>
</body>
</html>
