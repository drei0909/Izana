<?php
session_start();
require_once('./classes/database.php');
$db = new Database();

// Make sure the user is logged in
if (!isset($_SESSION['customer_ID'])) {
    header("Location: login.php");
    exit();
}

$customerID = $_SESSION['customer_ID'];
$customerName = $_SESSION['customer_FN'];
$cart = $_SESSION['cart'] ?? [];
$orderType = 'Take-out'; // Or get from a dropdown
$paymentMethod = $_POST['payment_method'] ?? null;

if (!$paymentMethod) {
    $_SESSION['error'] = "Please select a payment method.";
    header("Location: checkout.php");
    exit();
}

$receiptPath = null;

// Handle GCash receipt upload if selected
if ($paymentMethod === 'GCash') {
    if (isset($_FILES['gcash_receipt']) && $_FILES['gcash_receipt']['error'] === 0) {
        $ext = pathinfo($_FILES['gcash_receipt']['name'], PATHINFO_EXTENSION);
        $target = 'uploads/receipts/' . uniqid('gcash_', true) . '.' . $ext;
        move_uploaded_file($_FILES['gcash_receipt']['tmp_name'], $target);
        $receiptPath = $target;
    } else {
        $_SESSION['error'] = "Please upload a valid GCash receipt.";
        header("Location: checkout.php");
        exit();
    }
}

// Place the order via database class
$orderID = $db->placeOrder($customerID, $orderType, $cart, $paymentMethod, $receiptPath);

unset($_SESSION['cart']);
$_SESSION['order_success'] = true;

header("Location: checkout.php");
exit();
