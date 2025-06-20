<?php
session_start();
require_once('./classes/database.php');
$db = new Database();

// Check if user is logged in
if (!isset($_SESSION['customer_ID'])) {
    header("Location: login.php");
    exit();
}

$customerID = $_SESSION['customer_ID'];
$customerName = $_SESSION['customer_FN'] ?? 'Guest';
$cart = $_SESSION['cart'] ?? [];

$promoCode = isset($_POST['promo_code']) ? strtoupper(trim($_POST['promo_code'])) : null;
$orderType = $_POST['order_type'] ?? null;
$paymentMethod = $_POST['payment_method'] ?? null;

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
        $ext = pathinfo($_FILES['gcash_receipt']['name'], PATHINFO_EXTENSION);
        $allowed = ['jpg', 'jpeg', 'png'];
        if (!in_array(strtolower($ext), $allowed)) {
            $_SESSION['error'] = "Invalid file type. Only JPG, JPEG, or PNG allowed.";
            header("Location: checkout.php");
            exit();
        }

        if (!is_dir('uploads/receipts')) {
            mkdir('uploads/receipts', 0777, true);
        }

        $target = 'uploads/receipts/' . uniqid('gcash_', true) . '.' . $ext;
        if (!move_uploaded_file($_FILES['gcash_receipt']['tmp_name'], $target)) {
            $_SESSION['error'] = "Failed to upload receipt.";
            header("Location: checkout.php");
            exit();
        }

        $receiptPath = $target;
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

// Redeem points
$redeem = isset($_POST['redeem_points']);
$pointsRedeemed = 0;
$pointDiscount = 0;

if ($redeem) {
    $currentPoints = $db->getCustomerPoints($customerID);
    $pointsRedeemed = min($currentPoints, 5);
    $pointDiscount = $pointsRedeemed * 10;
    $totalAmount = max(0, $totalAmount - $pointDiscount);

    if ($pointsRedeemed > 0) {
        $stmt = $db->conn->prepare("INSERT INTO Reward_Transaction (customer_id, points_earned, reward_type, points_redeemed) VALUES (?, 0, 'Redeemed', ?)");
        $stmt->execute([$customerID, $pointsRedeemed]);
    }
}

// Promo code logic
$promoDiscount = 0;
$promoID = null;

if ($promoCode) {
    $stmt = $db->conn->prepare("SELECT * FROM Promotion WHERE promo_code = ?");
    $stmt->execute([$promoCode]);
    $promo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$promo) {
        $_SESSION['error'] = "Invalid promo code.";
        header("Location: checkout.php");
        exit();
    }

    $promoID = $promo['promo_id'];

    // Check if customer already used the promo
    $stmt = $db->conn->prepare("SELECT COUNT(*) FROM Customer_Promotion WHERE customer_id = ? AND promo_id = ?");
    $stmt->execute([$customerID, $promoID]);

    if ($stmt->fetchColumn() == 0) {
        if ($promoCode === 'FIRSTCUP') {
            $promoDiscount = $totalAmount * 0.10;
            $totalAmount -= $promoDiscount;

            // Save usage
            $stmt = $db->conn->prepare("INSERT INTO Customer_Promotion (customer_id, promo_id) VALUES (?, ?)");
            $stmt->execute([$customerID, $promoID]);

            $_SESSION['promo_applied_successfully'] = true;
        }
    }
}

// Place order (assumes your method accepts promo code for display/logic)
$orderID = $db->placeOrder($customerID, $orderType, $cart, $paymentMethod, $receiptPath, $promoCode);

// Cleanup
unset($_SESSION['cart']);
$_SESSION['order_success'] = true;

header("Location: checkout.php");
exit();
