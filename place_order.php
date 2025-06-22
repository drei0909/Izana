<?php
session_start();
require_once('./classes/database.php');
$db = new Database();

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
$redeemPoints = isset($_POST['redeem_points']);
$pointsRedeemed = 0;
$pointDiscount = 0;

if ($redeemPoints) {
    $stmt = $db->conn->prepare("SELECT SUM(points_earned - points_redeemed) AS total_points FROM reward_transaction WHERE customer_id = ?");
    $stmt->execute([$customerID]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $availablePoints = $result['total_points'] ?? 0;

    if ($availablePoints >= 5) {
        $pointsRedeemed = 5;
        $pointDiscount = 50;
        $totalAmount = max(0, $totalAmount - $pointDiscount);
    }
}

// Promo code logic
$promoDiscount = 0;
$promoID = null;

// Only run promo logic if a code is selected
if (!empty($promoCode)) {
    // Fetch promo details
    $stmt = $db->conn->prepare("SELECT * FROM promotion WHERE promo_code = ? AND is_active = 1 AND expiry_date >= CURDATE()");
    $stmt->execute([$promoCode]);
    $promo = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($promo) {
        $promoID = $promo['promo_id'];

        // Check if already used
        $stmt = $db->conn->prepare("SELECT COUNT(*) FROM Customer_Promotion WHERE customer_id = ? AND promo_id = ?");
        $stmt->execute([$customerID, $promoID]);
        $alreadyUsed = $stmt->fetchColumn();

        if ($alreadyUsed > 0) {
            $_SESSION['error'] = "Promo already used.";
            header("Location: checkout.php");
            exit();
        }

        // Handle FIRSTCUP separately
        if ($promoCode === 'FIRSTCUP') {
            $stmt = $db->conn->prepare("SELECT COUNT(*) FROM `order` WHERE customer_id = ?");
            $stmt->execute([$customerID]);
            $orderCount = $stmt->fetchColumn();

            if ($orderCount > 0) {
                $_SESSION['error'] = "Sorry! The FIRSTCUP promo is for new customers only.";
                header("Location: checkout.php");
                exit();
            }

            // Apply fixed discount
            $promoDiscount = min($promo['discount_value'], $totalAmount);
        } else {
            // Apply general promos
            if ($totalAmount >= $promo['minimum_order_amount']) {
                if ($promo['discount_type'] === 'percent') {
                    $promoDiscount = $totalAmount * ($promo['discount_value'] / 100);
                } else {
                    $promoDiscount = $promo['discount_value'];
                }
            } else {
                $_SESSION['error'] = "Minimum order not met for this promo.";
                header("Location: checkout.php");
                exit();
            }
        }

        // Deduct and save
        $totalAmount = max(0, $totalAmount - $promoDiscount);
        $stmt = $db->conn->prepare("INSERT INTO Customer_Promotion (customer_id, promo_id) VALUES (?, ?)");
        $stmt->execute([$customerID, $promoID]);

        $_SESSION['promo_applied_successfully'] = true;

    } else {
        $_SESSION['error'] = "Invalid or expired promo code.";
        header("Location: checkout.php");
        exit();
    }
}


// Finalize order
$orderID = $db->placeOrder($customerID, $orderType, $cart, $paymentMethod, $receiptPath, $promoCode, $pointsRedeemed);

// Cleanup
unset($_SESSION['cart']);
$_SESSION['order_success'] = true;

header("Location: checkout.php");
exit();
?>
