<?php
session_start();
require_once('./classes/database.php');
$db = new Database();

if (!isset($_SESSION['customer_ID'])) {
    header("Location: login.php");
    exit();
}

$customerID = $_SESSION['customer_ID'];
$cart = $_SESSION['cart'] ?? [];

if (empty($cart)) {
    $_SESSION['error'] = "Your cart is empty.";
    header("Location: checkout.php");
    exit();
}

$paymentMethod = $_POST['payment_method'] ?? null;

// Only GCash allowed
if ($paymentMethod !== 'GCash') {
    $_SESSION['error'] = "Only GCash payment is allowed.";
    header("Location: checkout.php");
    exit();
}

// Handle receipt upload
$receiptPath = null;
if (isset($_FILES['gcash_receipt']) && $_FILES['gcash_receipt']['error'] === 0) {
    $ext = strtolower(pathinfo($_FILES['gcash_receipt']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png'];

    if (!in_array($ext, $allowed)) {
        $_SESSION['error'] = "Invalid file type. Only JPG, JPEG, or PNG allowed.";
        header("Location: checkout.php");
        exit();
    }

    if (!is_dir('uploads/receipts')) mkdir('uploads/receipts', 0777, true);

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

// Insert pending order
try {
    $pendingID = $db->addPendingOrder($customerID, $cart, $paymentMethod, $receiptPath);

    $_SESSION['last_order_id'] = $pendingID;
    unset($_SESSION['cart']);
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Order submitted for review!'];

    header("Location: checkout.php");
    exit();
} catch (Exception $e) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => "Failed to place order: " . $e->getMessage()];
    header("Location: checkout.php");
    exit();
}
