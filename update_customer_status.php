<?php
session_start();
require_once('./classes/database.php');
$db = new Database();

// Only allow updates for customer orders
if (!isset($_SESSION['admin_ID'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$orderID = $_POST['order_id'] ?? null;
$status = $_POST['status'] ?? null;

if (!$orderID || !$status) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit();
}

try {
    $stmt = $db->conn->prepare("UPDATE `pending_order` SET status = :status WHERE order_id = :order_id");
    $stmt->execute([
        ':status' => $status,
        ':order_id' => $orderID
    ]);

    echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
