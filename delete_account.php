<?php
session_start();
require_once('./classes/database.php');
$db = new Database();

// Always return JSON
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['ref'] ?? '') === 'delete_account') {
    $customerID = $_SESSION['customer_ID'] ?? null;

    if (!$customerID) {
        echo json_encode(['status' => 'error', 'message' => 'Not logged in.']);
        exit;
    }

    try {
        $stmt = $db->conn->prepare("DELETE FROM customer WHERE customer_id = ?");
        if ($stmt->execute([$customerID])) {
            session_destroy();
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database error.']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// Always end with a default JSON response (so it never returns blank)
echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
exit;
?>
