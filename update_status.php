<?php
require_once('./classes/database.php');
$db = new Database();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderID = $_POST['order_ID'] ?? null;
    $newStatus = $_POST['order_status'] ?? null;

    if ($orderID && $newStatus) {
        try {
            $stmt = $db->conn->prepare("UPDATE `order` SET order_status = ? WHERE order_id = ?");
            $stmt->execute([$newStatus, $orderID]);
            header("Location: cashier.php");
            exit();
        } catch (PDOException $e) {
            echo "Database error: " . $e->getMessage();
        }
    } else {
        echo "Invalid data submitted.";
    }
} else {
    echo "Invalid request (not POST).";
}
?>
