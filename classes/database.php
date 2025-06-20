<?php
class Database {
    private $host = "localhost";
    private $db_name = "Izana";
    private $username = "root";
    private $password = "";
    public $conn;

    // Connect to the database
    public function __construct() {
        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->db_name}",
                $this->username, $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }

 public function registerCustomer($fn, $ln, $username, $email, $password) {
    // Check for duplicate username or email
    $checkSql = "SELECT COUNT(*) FROM Customer WHERE customer_username = :username OR customer_email = :email";
    $checkStmt = $this->conn->prepare($checkSql);
    $checkStmt->execute([
        ':username' => $username,
        ':email' => $email
    ]);

    if ($checkStmt->fetchColumn() > 0) {
        return false; // Already exists
    }

    // Insert user with hashed password
    $sql = "INSERT INTO Customer (customer_FN, customer_LN, customer_username, customer_email, customer_password, is_new)
            VALUES (:fn, :ln, :username, :email, :password, 1)";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute([
        ':fn' => $fn,
        ':ln' => $ln,
        ':username' => $username,
        ':email' => $email,
        ':password' => password_hash($password, PASSWORD_BCRYPT)
    ]);
    return $this->conn->lastInsertId();
}


//loginCustomer method
 public function loginCustomer($username, $password) {
    $sql = "SELECT * FROM Customer WHERE customer_username = :username";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        return 'no_user'; 
    }

    if (!password_verify($password, $user['customer_password'])) {
        return 'wrong_password'; 
    }

    return $user;
}

    // Get all products
    public function getAllProducts() {
        $sql = "SELECT * FROM Product";
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function insertOrder($customerID, $paymentMethod, $receiptPath, $status) {
    $cart = $_SESSION['cart'] ?? [];
    $total = 0;
    foreach ($cart as $item) {
        $total += $item['price'] * $item['quantity'];
    }

    $stmt = $this->conn->prepare("INSERT INTO order (customer_id, total, payment_method, receipt, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$customerID, $total, $paymentMethod, $receiptPath, $status]);
    return $this->conn->lastInsertId();
}
//insertitam
public function insertOrderItem($orderID, $productID, $quantity, $price) {
    $stmt = $this->conn->prepare("INSERT INTO order_item (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    $stmt->execute([$orderID, $productID, $quantity, $price]);
}


//placeOrder method
public function placeOrder($customerID, $orderType, $cart, $paymentMethod, $receiptPath = null, $promoCode = null) {
    $totalAmount = 0;

    // Calculate total amount
    foreach ($cart as $item) {
        $totalAmount += $item['price'] * $item['quantity'];
    }

    // Normalize promo code
    $promoCode = strtoupper(trim($promoCode));

    // Promo Logic: Apply 10% discount for WELCOME10 if customer is new and hasn't used it yet
    if ($promoCode === 'WELCOME10' && isset($_SESSION['is_new']) && $_SESSION['is_new']) {
        // Check if promo has already been used by this customer
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM customer_promotion WHERE customer_id = ? AND promo_code = ?");
        $stmt->execute([$customerID, $promoCode]);

        if ($stmt->fetchColumn() == 0) {
            $discount = $totalAmount * 0.10;
            $totalAmount -= $discount;

            // Insert into customer_promotion table
            $stmt = $this->conn->prepare("INSERT INTO customer_promotion (customer_id, promo_code) VALUES (?, ?)");
            $stmt->execute([$customerID, $promoCode]);
        }
    }

    // Insert into Order table
    $stmt = $this->conn->prepare("INSERT INTO `order` (customer_id, order_type, total_amount, receipt) VALUES (?, ?, ?, ?)");
    $stmt->execute([$customerID, $orderType, $totalAmount, $receiptPath]);
    $orderID = $this->conn->lastInsertId();

    // Insert each item into Order_Item table
    foreach ($cart as $item) {
        if (!isset($item['id'])) continue;
        $this->insertOrderItem($orderID, $item['id'], $item['quantity'], $item['price']);
    }

    // Insert into Payment table
    $stmt = $this->conn->prepare("INSERT INTO payment (order_id, payment_method, payment_amount) VALUES (?, ?, ?)");
    $stmt->execute([$orderID, $paymentMethod, $totalAmount]);

    // Reward Points Logic: Earn 1 point per ₱100 spent
    $pointsEarned = floor($totalAmount / 100);
    if ($pointsEarned > 0) {
        $stmt = $this->conn->prepare("INSERT INTO reward_transaction (customer_id, points_earned, reward_type, points_redeemed) VALUES (?, ?, 'Earned', 0)");
        $stmt->execute([$customerID, $pointsEarned]);
    }

    // Mark customer as no longer new after their first order
    $stmt = $this->conn->prepare("UPDATE Customer SET is_new = 0 WHERE customer_id = ?");
    $stmt->execute([$customerID]);
    $_SESSION['is_new'] = 0;

    return $orderID;
}



public function getProductIdByName($productName) {
    $stmt = $this->conn->prepare("SELECT product_id FROM Product WHERE product_name = ?");
    $stmt->execute([$productName]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['product_id'] : null;
}




}
?>
