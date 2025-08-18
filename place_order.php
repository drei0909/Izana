<?php
session_start();
require_once('./classes/database.php');
$db = new Database();

if (!isset($_SESSION['customer_ID'])) {
    header("Location: login.php");
    exit();
}

$customerID   = $_SESSION['customer_ID'];
$customerName = $_SESSION['customer_FN'] ?? 'Guest';
$cart         = $_SESSION['cart'] ?? [];

// Get POST data
$orderType     = $_POST['order_type'] ?? null; // Dine-in / Take-out
$paymentMethod = $_POST['payment_method'] ?? null; // Cash / GCash (used only for receipt handling)

// ✅ Set order_channel automatically as 'online'
$orderChannel = 'online';

// Validate order type and payment method
if (!$orderType || !in_array($orderType, ['Dine-in', 'Take-out'])) {
    $_SESSION['error'] = "Please select a valid order type.";
    header("Location: checkout.php");
    exit();
}
if (!$paymentMethod || !in_array($paymentMethod, ['Cash', 'GCash'])) {
    $_SESSION['error'] = "Please select a valid payment method.";
    header("Location: checkout.php");
    exit();
}

// Handle GCash receipt
$receiptPath = null;
if ($paymentMethod === 'GCash') {
    if (isset($_FILES['gcash_receipt']) && $_FILES['gcash_receipt']['error'] === 0) {
        $ext = strtolower(pathinfo($_FILES['gcash_receipt']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png'];
        if (!in_array($ext, $allowed)) {
            $_SESSION['error'] = "Invalid file type. Only JPG, JPEG, or PNG allowed.";
            header("Location: checkout.php");
            exit();
        }

        if (!is_dir('uploads/receipts')) {
            mkdir('uploads/receipts', 0777, true);
        }

        $filename = uniqid('gcash_', true) . '.' . $ext;
        $target = 'uploads/receipts/' . $filename;

        if (!move_uploaded_file($_FILES['gcash_receipt']['tmp_name'], $target)) {
            $_SESSION['error'] = "Failed to upload receipt.";
            header("Location: checkout.php");
            exit();
        }

        $receiptPath = $filename;
    } else {
        $_SESSION['error'] = "Please upload a valid GCash receipt.";
        header("Location: checkout.php");
        exit();
    }
}

// Calculate total
$totalAmount = 0;
foreach ($cart as $item) {
    $totalAmount += $item['price'] * $item['quantity'];
}

try {
    $db->conn->beginTransaction();

    // Insert into order table
    $stmt = $db->conn->prepare("
        INSERT INTO `order` 
        (customer_id, total_amount, order_type, order_channel, receipt, order_status)
        VALUES (?, ?, ?, ?, ?, 'Pending')
    ");
    $stmt->execute([
        $customerID,
        $totalAmount,
        $orderType,
        $orderChannel, // 'online'
        $receiptPath
    ]);

    $orderID = $db->conn->lastInsertId();

    // Insert into order_item table
    $stmtItem = $db->conn->prepare("
        INSERT INTO order_item (order_id, product_id, quantity, price)
        VALUES (?, ?, ?, ?)
    ");
    foreach ($cart as $item) {
        $stmtItem->execute([
            $orderID,
            $item['id'],        // assuming 'id' is product_id
            $item['quantity'],
            $item['price']
        ]);
    }

    // Insert into payment table
    $stmtPayment = $db->conn->prepare("
        INSERT INTO payment (order_id, payment_method, payment_amount)
        VALUES (?, ?, ?)
    ");
    $stmtPayment->execute([
        $orderID,
        $paymentMethod,
        $totalAmount
    ]);

    $db->conn->commit();

    // Save last order ID in session
    $_SESSION['last_order_id'] = $orderID;

    // Cleanup
    unset($_SESSION['cart']);
    $_SESSION['order_success'] = true;

    header("Location: checkout.php");
    exit();

} catch (Exception $e) {
    $db->conn->rollBack();
    $_SESSION['error'] = "Failed to place order: " . $e->getMessage();
    header("Location: checkout.php");
    exit();
}
?>
