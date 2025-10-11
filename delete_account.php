<?php
session_start();
require_once('./classes/database.php');
$db = new Database();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['ref'] ?? '') === 'delete_account') {
    $customerID = $_SESSION['customer_ID'] ?? null;
    if (!$customerID) {
        echo json_encode(['status' => 'error', 'message' => 'Not logged in.']);
        exit;
    }

    $stmt = $db->conn->prepare("DELETE FROM customer WHERE customer_id = ?");
    if ($stmt->execute([$customerID])) {
        session_destroy();
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error.']);
    }
}
?>
