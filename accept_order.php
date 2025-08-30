<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once('./classes/database.php');
$db = new Database();

if (!isset($_SESSION['admin_ID'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$pendingID = (int)($_POST['pending_ID'] ?? 0); // ✅ use pending_ID, not order_ID

if ($pendingID > 0) {
    try {
        // Accept the order and get the order ID
        $orderID = $db->acceptPendingOrder($pendingID);

        // Update the session with the accepted order ID
        $_SESSION['last_order_id'] = $orderID;

        // Return clean JSON to confirm order acceptance
        echo json_encode([
            'success' => true,
            'status' => 'Accepted',
            'order_id' => (int)$orderID
        ]);
        exit();

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error accepting order: ' . $e->getMessage()
        ]);
        exit();
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid pending_ID']);
    exit();
}
