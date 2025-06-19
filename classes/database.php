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
    $sql = "INSERT INTO Customer (customer_FN, customer_LN, customer_username, customer_email, customer_password)
            VALUES (:fn, :ln, :username, :email, :password)";
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
        return 'no_user'; // 👈 This must match login.php logic
    }

    if (!password_verify($password, $user['customer_password'])) {
        return 'wrong_password'; // 👈 Match login.php
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
public function insertOrderItem($orderID, $productName, $quantity, $price) {
    $stmt = $this->conn->prepare("INSERT INTO order_item (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    $stmt->execute([$orderID, $productID, $quantity, $price]);
}

//placeOrder method
public function placeOrder($customerID, $orderType, $cart, $paymentMethod, $receiptPath = null) {
    $totalAmount = 0;
    foreach ($cart as $item) {
        $totalAmount += $item['price'] * $item['quantity'];
    }

    // Insert into `Order`
    $stmt = $this->conn->prepare("INSERT INTO `Order` (customer_id, order_type, total_amount, receipt) VALUES (?, ?, ?, ?)");
    $stmt->execute([$customerID, $orderType, $totalAmount, $receiptPath]);

    $orderID = $this->conn->lastInsertId();

    // Insert each item into `Order_Item`
    foreach ($cart as $item) {
        $productID = $this->getProductIdByName($item['name']);
        if ($productID) {
            $this->insertOrderItem($orderID, $productID, $item['quantity'], $item['price']);
        }
    }

    // Insert into `Payment`
    $stmt = $this->conn->prepare("INSERT INTO Payment (order_id, payment_method, payment_amount) VALUES (?, ?, ?)");
    $stmt->execute([$orderID, $paymentMethod, $totalAmount]);

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
