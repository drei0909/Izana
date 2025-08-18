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
        $sql = "SELECT * FROM product";
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function insertOrder($customerID, $paymentMethod, $receiptPath, $status) {
    $cart = $_SESSION['cart'] ?? [];
    $total = 0;
    foreach ($cart as $item) {
        $total += $item['price'] * $item['quantity'];
    }

    if ($pointsRedeemed >= 5) {
    $pointDiscount = 50;
    $totalAmount = max(0, $totalAmount - $pointDiscount);
}

    $stmt = $this->conn->prepare("INSERT INTO order (customer_id, total, payment_method, receipt,  promo_discount, point_discount, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$customerID, $total, $paymentMethod, $receiptPath,  $promoDiscount, $pointDiscount, $status]);
    return $this->conn->lastInsertId();
}


//insertitam
public function insertOrderItem($orderID, $productID, $quantity, $price) {
    $stmt = $this->conn->prepare("INSERT INTO order_item (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    $stmt->execute([$orderID, $productID, $quantity, $price]);
}


//placeOrder method
public function placeOrder($customerID, $orderType, $cart, $paymentMethod, $receiptPath = null, $promoCode = null, $pointsRedeemed = 0) {
    $totalAmount = 0;
    $promoDiscount = 0;
$pointDiscount = 0;

    // Calculate total order total
    foreach ($cart as $item) {
        $itemPrice = floatval($item['price']);
        $itemQty   = intval($item['quantity']);
        $totalAmount += $itemPrice * $itemQty;
    }

    $appliedPromoID = null; // for storing promo_id
    $promoCode = $promoCode ? strtoupper(trim($promoCode)) : null;

    // Promo logic using the promotion table
    if ($promoCode) {
        // Fetch promo details from DB
        $stmt = $this->conn->prepare("SELECT * FROM promotion WHERE promo_code = ? AND is_active = 1 AND (expiry_date IS NULL OR expiry_date >= CURDATE())");
        $stmt->execute([$promoCode]);
        $promo = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($promo) {
            $promoID       = $promo['promo_id'];
            $discountType  = $promo['discount_type'];
            $discountValue = $promo['discount_value'];
            $minOrder      = $promo['minimum_order_amount'];
            $maxUses       = $promo['max_uses'];

            // Check if customer already used this promo
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM customer_promotion WHERE customer_id = ? AND promo_id = ?");
            $stmt->execute([$customerID, $promoID]);
            $alreadyUsed = $stmt->fetchColumn();

            if (!$alreadyUsed && $totalAmount >= $minOrder) {
                // Apply discount
                if ($discountType === 'percent') {
                    $discount = ($discountValue / 100) * $totalAmount;
                } elseif ($discountType === 'fixed') {
                    $discount = $discountValue;
                } else {
                    $discount = 0;
                }
$promoDiscount = $discount;
$totalAmount -= $promoDiscount;
                $appliedPromoID = $promoID;

                // Record promo usage
                $stmt = $this->conn->prepare("INSERT INTO customer_promotion (customer_id, promo_id) VALUES (?, ?)");
                $stmt->execute([$customerID, $promoID]);

                $_SESSION['promo_applied_successfully'] = true;
            }
        }
    }

    // Insert order
   if ($pointsRedeemed >= 5) {
    $pointDiscount = 50;
    $totalAmount = max(0, $totalAmount - $pointDiscount);
}

$stmt = $this->conn->prepare("INSERT INTO `order` 
    (customer_id, order_type, total_amount, receipt, promo_discount, point_discount) 
    VALUES (?, ?, ?, ?, ?, ?)");
$stmt->execute([$customerID, $orderType, $totalAmount, $receiptPath, $promoDiscount, $pointDiscount]);

$orderID = $this->conn->lastInsertId();
    // Insert order items
    foreach ($cart as $item) {
        if (!isset($item['id'], $item['quantity'], $item['price'])) continue;
        $this->insertOrderItem($orderID, $item['id'], $item['quantity'], $item['price']);
    }

    // Insert payment
    $stmt = $this->conn->prepare("INSERT INTO payment (order_id, payment_method, payment_amount) VALUES (?, ?, ?)");
    $stmt->execute([$orderID, $paymentMethod, $totalAmount]);

    
   // Calculate points earned AFTER all discounts (totalAmount should already be final)
$pointsEarned = max(0, floor($totalAmount / 100)); // 1 point per ₱100

// Insert redeemed points if any
if ($pointsRedeemed > 0) {
    try {
        $stmt = $this->conn->prepare("
            INSERT INTO reward_transaction 
                (customer_id, points_earned, reward_type, points_redeemed) 
            VALUES 
                (?, 0, 'Redeemed', ?)
        ");
        $stmt->execute([$customerID, $pointsRedeemed]);
    } catch (PDOException $e) {
        error_log("Failed to insert redeemed points: " . $e->getMessage());
    }
}

$pointsEarned = max(0, floor($totalAmount / 100));



// Only earn points if no points were redeemed
if ($pointsRedeemed == 0) {
    $pointsEarned = max(0, floor($totalAmount / 100));
    if ($pointsEarned > 0) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO reward_transaction 
                    (customer_id, points_earned, reward_type, points_redeemed) 
                VALUES 
                    (?, ?, 'Earned', 0)
            ");
            $stmt->execute([$customerID, $pointsEarned]);
        } catch (PDOException $e) {
            error_log("Failed to insert earned points: " . $e->getMessage());
        }
    }
}



    // Mark as not new (first order logic)
    $stmt = $this->conn->prepare("UPDATE customer SET is_new = 0 WHERE customer_id = ?");
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

public function getAvailablePromos($customerID) {
    $stmt = $this->conn->prepare("SELECT * FROM promotion");
    $stmt->execute();
    $allPromos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Filter promos the customer hasn't used
    $available = [];
    foreach ($allPromos as $promo) {
        $check = $this->conn->prepare("SELECT COUNT(*) FROM customer_promotion WHERE customer_id = ? AND promo_id = ?");
        $check->execute([$customerID, $promo['promo_code']]);
        if ($check->fetchColumn() == 0) {
            $available[] = $promo;
        }
    }
    return $available;
}


public function getCustomerByID($customerID) {
    $stmt = $this->conn->prepare("SELECT * FROM customer WHERE customer_id = ?");
    $stmt->execute([$customerID]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

public function getCustomerOrders($customerID) {
    $stmt = $this->conn->prepare("SELECT * FROM `order` WHERE customer_id = ? ORDER BY order_date DESC");
    $stmt->execute([$customerID]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function getOrderItem($orderID) {
    $stmt = $this->conn->prepare("
        SELECT oi.quantity, oi.price, p.product_name 
        FROM order_item oi 
        JOIN product p ON oi.product_id = p.product_id 
        WHERE oi.order_id = ?
    ");
   $stmt->execute([$orderID]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function getPaymentByOrderID($orderID) {
    $stmt = $this->conn->prepare("SELECT * FROM payment WHERE order_id = ?");
    $stmt->execute([$orderID]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

public function updateCustomerInfo($customerID, $newUsername, $newEmail, $newPassword = null) {
    try {
        // Check for duplicate username or email
        $check = $this->conn->prepare("SELECT customer_id FROM customer WHERE (customer_username = :username OR customer_email = :email) AND customer_id != :id");
        $check->execute([
            ':username' => $newUsername,
            ':email' => $newEmail,
            ':id' => $customerID
        ]);

        if ($check->fetch()) {
            return 'duplicate';
        }

        // Validate email format
        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            return 'invalid_email';
        }

        // Build SQL dynamically based on password presence
        $fields = "customer_username = :username, customer_email = :email";
        $params = [
            ':username' => $newUsername,
            ':email' => $newEmail,
            ':id' => $customerID
        ];

        if (!empty($newPassword)) {
            $fields .= ", customer_password = :password";
            $params[':password'] = password_hash($newPassword, PASSWORD_BCRYPT);
        }

        $sql = "UPDATE customer SET $fields WHERE customer_id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return 'success';
    } catch (PDOException $e) {
        error_log("Update failed: " . $e->getMessage());
        return 'error';
    }
}

public function getTotalCustomers() {
    $stmt = $this->conn->query("SELECT COUNT(*) FROM customer");
    return $stmt->fetchColumn();
}

public function loginAdmin_L($username, $password) {
    $conn = $this->conn;

    $stmt = $conn->prepare("SELECT * FROM admin WHERE admin_username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin) {
        return 'no_user';
    }

    if (!password_verify($password, $admin['admin_password'])) {
        return 'wrong_password';
    }

    return $admin; // ✅ Login successful
}

public function getAllCustomers() {
    $stmt = $this->conn->prepare("SELECT * FROM customer ORDER BY created_at DESC");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC); // ← Dynamically fetch all rows
}


public function getAllOrder() {
    $sql = "
        SELECT 
            o.order_id,
            o.total_amount,
            o.receipt,
            o.order_date,
            c.customer_FN,
            c.customer_LN
        FROM `order` o
        JOIN customer c ON o.customer_id = c.customer_id
        ORDER BY o.order_date DESC
    ";

    $stmt = $this->conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}



// Add Product
public function addProduct($name, $price, $category, $stock, $imagePath) {
    $stmt = $this->conn->prepare("INSERT INTO product (product_name, product_price, product_category, stock_quantity, image_path) VALUES (?, ?, ?, ?, ?)");
    return $stmt->execute([$name, $price, $category, $stock, $imagePath]);
}


// Update Product
public function updateProduct($productId, $productName, $productPrice, $productCategory, $stockQuantity) {
    if ($stockQuantity) {
        $sql = "UPDATE product SET product_name = ?, product_price = ?, product_category = ?, stock_quantity = ? WHERE product_id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$productName, $productPrice, $productCategory, $stockQuantity, $productId]);
    } else {
        $sql = "UPDATE product SET product_name = ?, product_price = ?, product_category = ? WHERE product_id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$productName, $productPrice, $productCategory, $productId]);
    }
}

// Delete Product
public function deleteProduct($product_id) {
    try {
        // First, delete related records from order_item (if no ON DELETE CASCADE)
        $stmt1 = $this->conn->prepare("DELETE FROM order_item WHERE product_id = ?");
        $stmt1->execute([$product_id]);

        // Then delete the product itself
        $stmt2 = $this->conn->prepare("DELETE FROM product WHERE product_id = ?");
        return $stmt2->execute([$product_id]);
    } catch (PDOException $e) {
        error_log("Delete Error: " . $e->getMessage());
        return false;
    }
}



public function getProductById($productId) {
    $stmt = $this->conn->prepare("SELECT * FROM product WHERE product_id = ?");
    $stmt->execute([$productId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

public function getSalesReport($start = null, $end = null) {
    $sql = "SELECT o.order_id, o.order_type, o.order_date,
                   c.customer_FN, c.customer_LN,
                   p.payment_method, p.payment_amount
            FROM `order` o
            LEFT JOIN customer c ON o.customer_id = c.customer_id
            LEFT JOIN payment p ON o.order_id = p.order_id
            WHERE 1";

    $params = [];

    if ($start && $end) {
        $sql .= " AND DATE(o.order_date) BETWEEN ? AND ?";
        $params[] = $start;
        $params[] = $end;
    }

    $sql .= " ORDER BY o.order_date DESC";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}



public function getSalesChartData($start, $end, $period = 'daily') {
  $dateExpr = $period=='weekly'
    ? "YEAR(o.order_date), WEEK(o.order_date,1)"
    : "DATE(o.order_date)";
  $labelExpr = $period=='weekly'
    ? "CONCAT(YEAR(o.order_date),'‑W',WEEK(o.order_date,1))"
    : "DATE(o.order_date)";
  $sql = "SELECT $labelExpr AS label, SUM(p.payment_amount) AS total
          FROM `order` o
          LEFT JOIN payment p ON p.order_id = o.order_id
          WHERE 1";
  $params = [];
  if ($start&&$end){$sql.=" AND DATE(o.order_date) BETWEEN ? AND ?"; $params=[$start,$end];}
  $sql.=" GROUP BY $dateExpr ORDER BY MIN(o.order_date)";
  $stmt = $this->conn->prepare($sql);
  $stmt->execute($params);
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function getAdminById($admin_ID) {
    $stmt = $this->conn->prepare("SELECT * FROM admin WHERE admin_ID = ?");
    $stmt->execute([$admin_ID]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

public function updateAdminProfile($admin_ID, $firstName, $lastName) {
    $stmt = $this->conn->prepare("UPDATE admin SET admin_FN = ?, admin_LN = ? WHERE admin_ID = ?");
    return $stmt->execute([$firstName, $lastName, $admin_ID]);
}

public function updateAdminPassword($admin_ID, $hashedPassword) {
    $stmt = $this->conn->prepare("UPDATE admin SET password = ? WHERE admin_ID = ?");
    return $stmt->execute([$hashedPassword, $admin_ID]);
}


public function getTotalOrders() {
    $stmt = $this->conn->query("SELECT COUNT(*) FROM `order`");
    return $stmt->fetchColumn();
}

public function getTotalSales() {
    $stmt = $this->conn->query("SELECT SUM(total_amount) FROM `order`");
    $total = $stmt->fetchColumn();
    return $total ?? 0;
}



// Check if username exists
public function isUsernameExists($username) {
    $stmt = $this->conn->prepare("SELECT COUNT(*) FROM customer WHERE customer_username = ?");
    $stmt->execute([$username]);
    return $stmt->fetchColumn() > 0;
}

// Check if email exists
public function isEmailExists($email) {
    $stmt = $this->conn->prepare("SELECT COUNT(*) FROM customer WHERE customer_email = ?");
    $stmt->execute([$email]);
    return $stmt->fetchColumn() > 0;
}

function getOrderById($orderId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT o.*, c.customer_name 
                           FROM `order` o 
                           JOIN customers c ON o.customer_ID = c.customer_ID 
                           WHERE o.order_ID = :orderId");
    $stmt->execute(['orderId' => $orderId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function updateOrderStatus($orderId, $newStatus) {
    global $pdo;
    $allowedStatuses = ['Pending', 'Preparing', 'Ready for Pickup', 'Completed'];
    if (!in_array($newStatus, $allowedStatuses)) {
        return false; // ❌ Invalid status
    }

    $stmt = $pdo->prepare("UPDATE `order` SET order_status = :status WHERE order_ID = :orderId");
    return $stmt->execute(['status' => $newStatus, 'orderId' => $orderId]);
}

public function searchCustomers($search, $limit, $offset) {
    $sql = "SELECT * FROM customer
            WHERE customer_FN LIKE :search 
               OR customer_LN LIKE :search 
               OR customer_email LIKE :search
            ORDER BY created_at DESC
            LIMIT :limit OFFSET :offset";
    
    $stmt = $this->conn->prepare($sql);
    $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function countCustomers($search) {
    $sql = "SELECT COUNT(*) FROM customer 
            WHERE customer_FN LIKE :search 
               OR customer_LN LIKE :search 
               OR customer_email LIKE :search";
    
    $stmt = $this->conn->prepare($sql);
    $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
    $stmt->execute();
    
    return (int)$stmt->fetchColumn();
}


public function searchOrder($keyword) {
    if (empty($keyword)) {
        $sql = "SELECT o.*, c.customer_FN, c.customer_LN, c.customer_email
                FROM `order` o
                JOIN customer c ON o.customer_id = c.customer_id
                ORDER BY o.order_date DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
    } else {
        $sql = "SELECT o.*, c.customer_FN, c.customer_LN, c.customer_email
                FROM `order` o
                JOIN customer c ON o.customer_id = c.customer_id
                WHERE o.order_id LIKE :keyword
                   OR CONCAT(c.customer_FN, ' ', c.customer_LN) LIKE :keyword
                   OR c.customer_FN LIKE :keyword
                   OR c.customer_LN LIKE :keyword
                   OR c.customer_email LIKE :keyword
                ORDER BY o.order_date DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':keyword' => "%$keyword%"]);
    }
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


    public function cashierLogin($username, $password) {
        $sql = "SELECT * FROM cashier WHERE username = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$username]);
        $cashier = $stmt->fetch();
        if ($cashier && password_verify($password, $cashier['password'])) {
            return $cashier;
        }
        return false;
    }



 // Get total walk-in orders
    public function getTotalWalkInOrders() {
        $stmt = $this->conn->query("SELECT COUNT(*) FROM `order` WHERE order_type = 'walk-in'");
        return $stmt->fetchColumn();
    }

    // Get total walk-in sales
    public function getTotalWalkInSales() {
        $stmt = $this->conn->query("SELECT SUM(total_amount) FROM `order` WHERE order_type = 'walk-in'");
        return $stmt->fetchColumn() ?? 0;
    
}

public function getOrders($search = '', $limit = 5, $offset = 0) {
    $sql = "SELECT o.*, c.customer_FN, c.customer_LN
            FROM `order` o
            JOIN customer c ON o.customer_ID = c.customer_ID
            WHERE o.order_id LIKE :search
               OR c.customer_FN LIKE :search
               OR c.customer_LN LIKE :search
            ORDER BY o.order_date DESC
            LIMIT :limit OFFSET :offset";

    $stmt = $this->conn->prepare($sql);
    $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


public function countOrders($search = '') {
    $sql = "SELECT COUNT(*) as total
            FROM `order` o
            JOIN customer c ON o.customer_ID = c.customer_ID
            WHERE o.order_id LIKE :search
               OR c.customer_FN LIKE :search
               OR c.customer_LN LIKE :search";

    $stmt = $this->conn->prepare($sql);
    $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ? (int)$row['total'] : 0;
}

// ✅ Fetch paginated orders (only non-completed)
public function getCashierOrders($search = '', $limit = 5, $offset = 0) {
    $sql = "SELECT o.order_id, o.customer_ID, o.order_date, o.order_status, 
                   o.total_amount, o.order_type, o.receipt, 
                   c.customer_FN, c.customer_LN, c.customer_email,
                   p.payment_method
            FROM `order` o
            JOIN customer c ON o.customer_ID = c.customer_ID
            LEFT JOIN payment p ON o.order_id = p.order_id
            WHERE o.order_status != 'completed'
              AND (
                  o.order_id LIKE :search
                  OR CONCAT(c.customer_FN, ' ', c.customer_LN) LIKE :search
                  OR c.customer_FN LIKE :search
                  OR c.customer_LN LIKE :search
                  OR c.customer_email LIKE :search
              )
            ORDER BY o.order_date DESC
            LIMIT :limit OFFSET :offset";

    $stmt = $this->conn->prepare($sql);
    $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ✅ Count total orders (for pagination)
public function countCashierOrders($search = '') {
    $sql = "SELECT COUNT(*) as total
            FROM `order` o
            JOIN customer c ON o.customer_ID = c.customer_ID
            WHERE o.order_status != 'completed'
              AND (
                  o.order_id LIKE :search
                  OR CONCAT(c.customer_FN, ' ', c.customer_LN) LIKE :search
                  OR c.customer_FN LIKE :search
                  OR c.customer_LN LIKE :search
                  OR c.customer_email LIKE :search
              )";

    $stmt = $this->conn->prepare($sql);
    $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ? (int)$row['total'] : 0;
}




}
?>