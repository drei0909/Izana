<?php
session_start();
require_once('./classes/database.php');
$db = new Database();

header('Content-Type: application/json');

// ✅ Make sure customer is logged in and has a last order
if (empty($_SESSION['customer_ID']) || empty($_SESSION['last_order_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'No active order found.'
    ]);
    exit();
}

$customerID = $_SESSION['customer_ID'];
$pendingID  = $_SESSION['last_order_id'];

try {
    // ✅ Fetch the order status from either pending_order or order (depending on the status)
    $stmt = $db->conn->prepare("
        SELECT status 
        FROM pending_order 
        WHERE pending_id = ? AND customer_ID = ?
        LIMIT 1
    ");
    $stmt->execute([$pendingID, $customerID]);
    $orderData = $stmt->fetch(PDO::FETCH_ASSOC);

    // If the order is not found in pending_order, check the order table
    if (!$orderData) {
        $stmt = $db->conn->prepare("
            SELECT order_status AS status
            FROM `order`
            WHERE order_id = ? AND customer_id = ?
            LIMIT 1
        ");
        $stmt->execute([$pendingID, $customerID]);
        $orderData = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if ($orderData) {
        echo json_encode([
            'success' => true,
            'status'  => $orderData['status']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Order not found or does not belong to you.'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error checking order status.',
        'error'   => $e->getMessage() // ⚠️ For debugging, remove in production
    ]);
}
