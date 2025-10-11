<?php
session_start();
require_once('classes/database.php');

$db = new Database();

// Set a test customer ID (or use logged-in customer)
$customer_id = $_SESSION['customer_ID'] ?? 23; // replace 23 with a real customer_id if not logged in

// Fetch notifications
$stmt = $db->conn->prepare("
    SELECT *
    FROM notifications
    WHERE customer_id = ?
    ORDER BY created_at DESC
");
$stmt->execute([$customer_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Display results
if ($notifications) {
    echo "<h3>Notifications for customer ID: $customer_id</h3>";
    foreach ($notifications as $n) {
        echo "<div style='padding:5px; border-bottom:1px solid #ccc;'>";
        echo "<strong>" . htmlspecialchars($n['message']) . "</strong><br>";
        echo "<small>" . $n['created_at'] . "</small>";
        echo "</div>";
    }
} else {
    echo "<p>No notifications found for customer ID: $customer_id</p>";
}
?>
