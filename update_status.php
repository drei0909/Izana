<?php
session_start();
require_once('./classes/database.php');
$db = new Database();

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0); // set to 1 while debugging

// ✅ Only allow cashier/admin to update status
if (!isset($_SESSION['admin_ID'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$pendingID = $_POST['pending_ID'] ?? null;   // match cashier.php
$newStatus = $_POST['order_status'] ?? null;

if (!$pendingID || !$newStatus) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit();
}

// ✅ Allowed statuses only
$allowedStatuses = ['accepted', 'rejected'];
if (!in_array($newStatus, $allowedStatuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

try {
   // Inside update_status.php
if ($newStatus === 'accepted') {
    // ✅ Move order into `order` + `order_item`
    $orderID = $db->acceptPendingOrder((int)$pendingID);

    // Return success with redirection to receipt.php
    echo json_encode([
        'success'   => true,
        'message'   => 'Order accepted successfully',
        'status'    => 'accepted',
        'order_id'  => $orderID,  // Pass the order ID to the response
        'redirect_url' => 'receipt.php?order_id=' . $orderID  // Ensure the customer is redirected to the receipt page
    ]);
    exit;
} elseif ($newStatus === 'rejected') {
    // ✅ Just update status in pending_order
    $stmt = $db->conn->prepare("UPDATE pending_order SET status = 'rejected' WHERE pending_id = ?");
    $success = $stmt->execute([$pendingID]);

    echo json_encode([
        'success'   => $success,
        'message'   => $success ? 'Order rejected successfully' : 'Failed to reject order',
        'status'    => 'rejected',
        'pending_id'=> $pendingID
    ]);
    exit;
}

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    exit;
}
