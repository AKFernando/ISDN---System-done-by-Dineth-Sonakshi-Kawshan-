<?php
function configureMailSettings() {
    ini_set('SMTP', 'localhost');
    ini_set('smtp_port', '25');
    ini_set('sendmail_from', 'noreply@islandlink.com');
}

function sendOrderConfirmation($email, $name, $order_id, $total, $items) {
    configureMailSettings();
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    
    $subject = "Order Confirmation - IslandLink ISDN #" . $order_id;
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
            .order-details { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
            .footer { text-align: center; padding: 20px; color: #7f8c8d; font-size: 0.9rem; }
            .total { font-size: 1.5rem; color: #27ae60; font-weight: bold; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🛡️ Order Confirmation</h1>
                <p>Thank you for your order!</p>
            </div>
            <div class='content'>
                <h2>Hello " . htmlspecialchars($name) . ",</h2>
                <p>Your order has been successfully placed and is being processed.</p>
                
                <div class='order-details'>
                    <h3>Order #" . $order_id . "</h3>
                    <p><strong>Order Total:</strong> <span class='total'>$" . number_format($total, 2) . "</span></p>
                    <p><strong>Estimated Delivery:</strong> 24-48 hours</p>
                    <p><strong>Status:</strong> Processing</p>
                </div>
                
                <p>You can track your order anytime by logging into your account.</p>
                
                <p style='text-align: center; margin-top: 30px;'>
                    <a href='http://localhost/ISDN/track-order.php' style='background: #667eea; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;'>Track Order</a>
                </p>
            </div>
            <div class='footer'>
                <p>© 2026 IslandLink Sales Distribution Network</p>
                <p>📧 info@islandlink.com | 📞 +1 (555) 123-4567</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: IslandLink ISDN <noreply@islandlink.com>" . "\r\n";
    
    $result = @mail($email, $subject, $message, $headers);
    
    if (!$result) {
        $log_file = __DIR__ . '/email_log.txt';
        $log_entry = date('Y-m-d H:i:s') . " - Order Confirmation\n";
        $log_entry .= "To: $email\n";
        $log_entry .= "Subject: $subject\n";
        $log_entry .= "Order ID: $order_id\n";
        $log_entry .= "Total: $" . number_format($total, 2) . "\n";
        $log_entry .= "Status: Email server not configured (logged instead)\n";
        $log_entry .= "---\n\n";
        @file_put_contents($log_file, $log_entry, FILE_APPEND);
    }
    
    return true;
}

function sendDeliveryNotification($email, $name, $order_id, $status) {
    configureMailSettings();
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    
    $subject = "Delivery Update - Order #" . $order_id;
    
    $status_messages = [
        'dispatched' => 'Your order is on the way!',
        'delivered' => 'Your order has been delivered!'
    ];
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #4facfe, #00f2fe); color: white; padding: 30px; text-align: center; }
            .content { background: #f8f9fa; padding: 30px; }
            .status { font-size: 1.3rem; color: #27ae60; font-weight: bold; text-align: center; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🚚 Delivery Update</h1>
            </div>
            <div class='content'>
                <h2>Hello " . htmlspecialchars($name) . ",</h2>
                <div class='status'>" . $status_messages[$status] . "</div>
                <p>Order #" . $order_id . " status has been updated to: <strong>" . strtoupper($status) . "</strong></p>
                <p>Track your order: <a href='http://localhost/ISDN/track-order.php'>Click here</a></p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: IslandLink ISDN <noreply@islandlink.com>" . "\r\n";
    
    $result = @mail($email, $subject, $message, $headers);
    
    if (!$result) {
        $log_file = __DIR__ . '/email_log.txt';
        $log_entry = date('Y-m-d H:i:s') . " - Delivery Notification\n";
        $log_entry .= "To: $email\n";
        $log_entry .= "Subject: $subject\n";
        $log_entry .= "Order ID: $order_id\n";
        $log_entry .= "Status: " . strtoupper($status) . "\n";
        $log_entry .= "Status: Email server not configured (logged instead)\n";
        $log_entry .= "---\n\n";
        @file_put_contents($log_file, $log_entry, FILE_APPEND);
    }
    
    return true;
}
?>
