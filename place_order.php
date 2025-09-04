<?php
session_start();
require_once('./classes/database.php');
$db = new Database();

// Check if the user is logged in (for online orders)
if (!isset($_SESSION['customer_ID']) && !isset($_SESSION['admin_ID'])) {
    header("Location: login.php");
    exit();
}

// Handle cart for online orders
if (isset($_SESSION['customer_ID'])) {
    $customerID = $_SESSION['customer_ID'];
    $cart = $_SESSION['cart'] ?? [];
} else {
    // For walk-in orders, assume customer ID comes from the form (admin side)
    $customerID = $_POST['customer_id'];  // Walk-in customer ID passed by admin
    $cart = $_POST['cart'] ?? [];  // Cart for walk-in (admin will pass cart data)
}

// Check if the cart is empty
if (empty($cart)) {
    $_SESSION['error'] = "Your cart is empty.";
    header("Location: checkout.php");
    exit();
}

// Get payment method (only GCash allowed)
$paymentMethod = $_POST['payment_method'] ?? null;
if ($paymentMethod !== 'GCash') {
    $_SESSION['error'] = "Only GCash payment is allowed.";
    header("Location: checkout.php");
    exit();
}

// Handle receipt upload for GCash
$receiptPath = null;
if (isset($_FILES['gcash_receipt']) && $_FILES['gcash_receipt']['error'] === 0) {
    $ext = strtolower(pathinfo($_FILES['gcash_receipt']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png'];

    if (!in_array($ext, $allowed)) {
        $_SESSION['error'] = "Invalid file type. Only JPG, JPEG, or PNG allowed.";
        header("Location: checkout.php");
        exit();
    }

    // Create a directory for receipts if not already existing
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

// Determine order channel (online or walk-in)
$orderChannel = isset($_POST['order_channel']) ? $_POST['order_channel'] : 'online';

// Insert the order into the database
try {
    // Add order to the order table with status 'Pending' and the correct order channel
    $orderID = $db->placeOrder($customerID, $cart, $paymentMethod, $receiptPath, $orderChannel);

    // Store the order ID for further processing
    $_SESSION['last_order_id'] = $orderID;
    unset($_SESSION['cart']);
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Order submitted for review!'];

    // Redirect to the appropriate page based on order type
    if ($orderChannel === 'walk-in') {
        header("Location: manage_cashier.php");  // Redirect to cashier page for walk-in
    } else {
        header("Location: checkout.php");  // Redirect to checkout page for online orders
    }
    exit();

} catch (Exception $e) {
    // In case of failure, show error message
    $_SESSION['flash'] = ['type' => 'error', 'message' => "Failed to place order: " . $e->getMessage()];
    header("Location: checkout.php");
    exit();
}
?>
