<?php
session_start();
require_once('./classes/database.php');
$db = new Database();

if (!isset($_SESSION['admin_ID'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$orderID = (int)($_POST['order_ID'] ?? 0);

if ($orderID > 0) {
    try {
        $db->rejectPendingOrder($orderID);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error rejecting order']);
    }
}
?>
