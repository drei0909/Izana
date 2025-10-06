<?php
class Database {
    private $host = "localhost";    
    private $db_name = "izana";
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
        return 'no_user'; 
    }

    if (!password_verify($password, $user['customer_password'])) {
        return 'wrong_password'; 
    }

    return $user;
}

  // Get all products
public function getAllProducts($category_id = null) {
    if ($category_id !== null) {
        
        $stmt = $this->conn->prepare("SELECT * FROM product WHERE category_id = :category_id");
        $stmt->bindParam(':category_id', $category_id, PDO::PARAM_INT);
    } else {
        // If no category_id, get all products
        $stmt = $this->conn->prepare("SELECT p.* FROM product p
        INNER JOIN product_categories pc ON p.category_id = pc.category_id
        WHERE pc.is_active = 1");
        
    }

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}




//    public function insertOrder($customerID, $paymentMethod, $receiptPath, $status) {
//     $cart = $_SESSION['cart'] ?? [];
//     $total = 0;
//     foreach ($cart as $item) {
//         $total += $item['price'] * $item['quantity'];
//     }

//     $stmt = $this->conn->prepare("INSERT INTO `order` (customer_id, total, payment_method, receipt, status, order_date) 
//                                  VALUES (?, ?, ?, ?, ?, NOW())");  // Make sure `NOW()` is used to insert the current date
//     $stmt->execute([$customerID, $total, $paymentMethod, $receiptPath, $status]);
//     return $this->conn->lastInsertId();
// }



public function insertOrderItem($orderID, $posID, $productID, $quantity, $price) {
    $stmt = $this->conn->prepare("
        INSERT INTO order_item (order_id, pos_id, product_id, quantity, price) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$orderID, $posID, $productID, $quantity, $price]);
}



// public function placeOrder($customerID, $cart, $paymentMethod, $receiptPath = null, $orderChannel = 'online') {
//     if (empty($cart)) {
//         throw new Exception("Cart is empty.");
//     }
//     if ($paymentMethod !== 'GCash') {
//         throw new Exception("Only GCash is allowed.");
//     }

//     // Calculate total amount
//     $totalAmount = 0;
//     foreach ($cart as $item) {
//         $totalAmount += floatval($item['price']) * intval($item['quantity']);
//     }

//     try {
//         $this->conn->beginTransaction();

//         // Insert into `order` table
//         $stmt = $this->conn->prepare("
//             INSERT INTO `order` (customer_id, order_channel, total_amount, receipt, order_status, order_date)
//             VALUES (?, ?, ?, ?, 'Pending', NOW())");
//         $stmt->execute([$customerID, $orderChannel, $totalAmount, $receiptPath]);  // Ensure orderChannel is passed correctly
//         $orderID = $this->conn->lastInsertId(); // Get the last inserted order ID

//         // Insert items into `order_item` table
//         $itemStmt = $this->conn->prepare("
//             INSERT INTO order_item (order_id, product_id, quantity, price)
//             VALUES (?, ?, ?, ?)
//         ");
//         foreach ($cart as $item) {
//             if (!isset($item['id'], $item['quantity'], $item['price'])) continue;
//             $itemStmt->execute([$orderID, $item['id'], intval($item['quantity']), floatval($item['price'])]);
//         }

//         // Insert payment details into `payment` table
//         $payStmt = $this->conn->prepare("
//             INSERT INTO payment (order_id, payment_method, payment_amount)
//             VALUES (?, ?, ?)
//         ");
//         $payStmt->execute([$orderID, $paymentMethod, $totalAmount]);

//         $this->conn->commit();
//         return $orderID;
//     } catch (Exception $e) {
//         $this->conn->rollBack();
//         throw $e;
//     }
// }





public function getProductIdByName($productName) {
    $stmt = $this->conn->prepare("SELECT product_id FROM Product WHERE product_name = ?");
    $stmt->execute([$productName]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['product_id'] : null;
}

public function getCustomerByID($customerID) {
    $stmt = $this->conn->prepare("SELECT * FROM customer WHERE customer_id = ?");
    $stmt->execute([$customerID]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

public function getCustomerOrders($customerID) {
    $stmt = $this->conn->prepare("SELECT * FROM `order_online` WHERE customer_id = ? ORDER BY order_date DESC");
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

    return $admin;
}

// public function getAllCustomers() {
//     $stmt = $this->conn->prepare("SELECT * FROM customer ORDER BY created_at DESC");
//     $stmt->execute();
//     return $stmt->fetchAll(PDO::FETCH_ASSOC);
// }


public function getAllOrder() {
    $sql = "
        SELECT 
            p.pos_id AS order_id,
            'walk-in' AS order_channel,   -- Since this is POS, we tag as walk-in
            p.total_amount,
            p.payment_method,
            p.created_at AS order_date,
            pm.payment_status
        FROM order_pos p
        LEFT JOIN payment pm ON p.pos_id = pm.pos_id
        ORDER BY p.created_at DESC
    ";

    $stmt = $this->conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


//ad categories
public function addCategory($category) {
    $stmt = $this->conn->prepare("INSERT INTO product_categories (category) VALUES (?)");
    return $stmt->execute([$category]);
}

// Add Product
public function addProduct($name, $price, $category_id, $imagePath) {
    $stmt = $this->conn->prepare("INSERT INTO product (product_name, product_price, category_id, image_path) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$name, $price, $category_id, $imagePath]);
}

public function updateProduct($id, $name, $price, $category, $imagePath) {
    if ($imagePath === null) {
        // Explicitly clear the image_path when null
        $stmt = $this->conn->prepare("
            UPDATE product 
            SET product_name = ?, product_price = ?, category_id = ?, image_path = NULL
            WHERE product_id = ?
        ");
        return $stmt->execute([$name, $price, $category, $id]);
    } else {
        // Update with new/old image
        $stmt = $this->conn->prepare("
            UPDATE product 
            SET product_name = ?, product_price = ?, category_id = ?, image_path = ?
            WHERE product_id = ?
        ");
        return $stmt->execute([$name, $price, $category, $imagePath, $id]);
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

// public function getSalesReport($start = null, $end = null) {
//     $sql = "SELECT o.order_id, o.order_channel, o.order_date,
//                    c.customer_FN, c.customer_LN,
//                    p.payment_method, p.payment_amount
//             FROM `order` o
//             LEFT JOIN customer c ON o.customer_id = c.customer_id
//             LEFT JOIN payment p ON o.order_id = p.order_id
//             WHERE 1";

//     $params = [];

//     if ($start && $end) {
//         $sql .= " AND DATE(o.order_date) BETWEEN ? AND ?";
//         $params[] = $start;
//         $params[] = $end;
//     }

//     $sql .= " ORDER BY o.order_date DESC";
//     $stmt = $this->conn->prepare($sql);
//     $stmt->execute($params);
//     return $stmt->fetchAll(PDO::FETCH_ASSOC);
// }



// public function getSalesChartData($start, $end, $period = 'daily') {
//   $dateExpr = $period=='weekly'
//     ? "YEAR(o.order_date), WEEK(o.order_date,1)"
//     : "DATE(o.order_date)";
//   $labelExpr = $period=='weekly'
//     ? "CONCAT(YEAR(o.order_date),'‑W',WEEK(o.order_date,1))"
//     : "DATE(o.order_date)";
//   $sql = "SELECT $labelExpr AS label, SUM(p.payment_amount) AS total
//           FROM `order` o
//           LEFT JOIN payment p ON p.order_id = o.order_id
//           WHERE 1";
//   $params = [];
//   if ($start&&$end){$sql.=" AND DATE(o.order_date) BETWEEN ? AND ?"; $params=[$start,$end];}
//   $sql.=" GROUP BY $dateExpr ORDER BY MIN(o.order_date)";
//   $stmt = $this->conn->prepare($sql);
//   $stmt->execute($params);
//   return $stmt->fetchAll(PDO::FETCH_ASSOC);
// }

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


// public function getTotalOrders() {
//     $stmt = $this->conn->query("SELECT COUNT(*) FROM `order`");
//     return $stmt->fetchColumn();
// }

// public function getTotalSales() {
//     $stmt = $this->conn->query("SELECT SUM(total_amount) FROM `order`");
//     $total = $stmt->fetchColumn();
//     return $total ?? 0;
// }



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

// function getOrderById($orderId) {
//     global $pdo;
//     $stmt = $pdo->prepare("SELECT o.*, c.customer_name 
//                            FROM `order` o 
//                            JOIN customers c ON o.customer_ID = c.customer_ID 
//                            WHERE o.order_ID = :orderId");
//     $stmt->execute(['orderId' => $orderId]);
//     return $stmt->fetch(PDO::FETCH_ASSOC);
// }

// // Update order status (returns true on success)
// public function updateOrderStatus($orderID, $newStatus) {
//     $stmt = $this->conn->prepare("UPDATE `order` SET order_status = ? WHERE order_id = ?");
//     return $stmt->execute([$newStatus, $orderID]);
// }

public function getAllCustomers() {
    $sql = "SELECT * FROM customer ORDER BY created_at DESC";
    $stmt = $this->conn->prepare($sql);
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


// public function searchOrder($keyword) {
//     if (empty($keyword)) {
//         $sql = "SELECT o.*, c.customer_FN, c.customer_LN, c.customer_email
//                 FROM `order` o
//                 JOIN customer c ON o.customer_id = c.customer_id
//                 ORDER BY o.order_date DESC";
//         $stmt = $this->conn->prepare($sql);
//         $stmt->execute();
//     } else {
//         $sql = "SELECT o.*, c.customer_FN, c.customer_LN, c.customer_email
//                 FROM `order` o
//                 JOIN customer c ON o.customer_id = c.customer_id
//                 WHERE o.order_id LIKE :keyword
//                    OR CONCAT(c.customer_FN, ' ', c.customer_LN) LIKE :keyword
//                    OR c.customer_FN LIKE :keyword
//                    OR c.customer_LN LIKE :keyword
//                    OR c.customer_email LIKE :keyword
//                 ORDER BY o.order_date DESC";
//         $stmt = $this->conn->prepare($sql);
//         $stmt->execute([':keyword' => "%$keyword%"]);
//     }
//     return $stmt->fetchAll(PDO::FETCH_ASSOC);
// }


 // Get total walk-in orders
    public function getTotalWalkInOrders() {
        $stmt = $this->conn->query("SELECT COUNT(*) FROM `order_online`");
        return $stmt->fetchColumn();
    }

    // Get total walk-in sales
    public function getTotalWalkInSales() {
        $stmt = $this->conn->query("SELECT SUM(total_amount) FROM `order_pos`");
        return $stmt->fetchColumn() ?? 0;
    
}


// public function getOrders($status = null) {
//     $sql = "SELECT 
//                 o.order_id, 
//                 o.customer_id, 
//                 o.created_at, 
//                 o.status, 
//                 o.total_amount, 
//                 o.receipt, 
//                 o.ref_no,
//                 c.customer_FN, 
//                 c.customer_LN, 
//                 c.customer_email,
//                 p.payment_method, 
//                 p.payment_status
//             FROM order_online o
//             INNER JOIN customer c 
//                 ON o.customer_id = c.customer_id
//             LEFT JOIN payment p 
//                 ON o.order_id = p.order_id";

//             if($status != ''){
//                 $sql .= " WHERE o.status = $status";
//             }

//             $sql .= " ORDER BY o.created_at DESC"; 

//     $stmt = $this->conn->prepare($sql);
//     $stmt->execute();

//     $sql = "SELECT * FROM order_pos";

//     return $stmt->fetchAll(PDO::FETCH_ASSOC);
// }


public function getOrders($status = null) {
    $sql = "
        SELECT 
            o.order_id, 
            o.customer_id, 
            o.created_at, 
            o.status, 
            o.total_amount, 
            o.receipt, 
            c.customer_FN, 
            c.customer_LN, 
            c.customer_email,
            p.payment_method, 
            p.payment_status,
            'online' AS order_type
        FROM order_online o
        INNER JOIN customer c 
            ON o.customer_id = c.customer_id
        LEFT JOIN payment p 
            ON o.order_id = p.order_id
    ";

    if ($status != '') {
        $sql .= " WHERE o.status = $status";
    }

    $sql .= "
        UNION ALL
        SELECT 
            op.pos_id as order_id, 
            NULL AS customer_id, 
            op.created_at, 
            op.status, 
            op.total_amount, 
            NULL AS receipt, 
            'WALK-IN' AS customer_FN, 
            '' AS customer_LN, 
            '' AS customer_email,
            '' AS payment_method, 
            '' AS payment_status,
            'pos' AS order_type
        FROM order_pos op
    ";
    if ($status != '') {
        $sql .= " WHERE op.status = $status";
    }

    $sql .= " ORDER BY created_at DESC"; 

    $stmt = $this->conn->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


public function deletePaymentsByOrder($orderID) {
    $stmt = $this->conn->prepare("DELETE FROM payment WHERE order_id = ?");
    return $stmt->execute([$orderID]);
}

public function saveSalesToHistory($walkinSales, $onlineSales, $totalSales) {
    $stmt = $this->conn->prepare("INSERT INTO sales_history (order_date, walk_in_sales, online_sales, total_sales) VALUES (?, ?, ?, ?)");
    $stmt->execute([date('Y-m-d'), $walkinSales, $onlineSales, $totalSales]);
}


// Method to fetch all sales history
public function getSalesHistory() {
    $sql = "SELECT * FROM sales_history ORDER BY order_date DESC";
    $stmt = $this->conn->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


public function deleteOrder($orderId) {
    // Delete related payment records
    $stmt = $this->conn->prepare("DELETE FROM payment WHERE order_id = ?");
    $stmt->execute([$orderId]);

    // Delete related records from order_item
    $stmt = $this->conn->prepare("DELETE FROM order_item WHERE order_id = ?");
    $stmt->execute([$orderId]);

    // // Delete the order
    // $stmt = $this->conn->prepare("DELETE FROM `order` WHERE order_id = ?");
    // return $stmt->execute([$orderId]);
}










// Fetch online orders
public function getOnlineOrders() {
    $sql = "SELECT o.*, c.customer_FN, c.customer_LN, c.customer_email
            FROM order_online o
            JOIN customer c ON o.customer_id = c.customer_id
            ORDER BY o.order_date DESC";
    $stmt = $this->conn->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch POS orders (walk-in)
public function getPOSOrders() {
    $sql = "SELECT * FROM order_pos ORDER BY created_at DESC";
    $stmt = $this->conn->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function addPayment($orderID, $posID, $method, $amount, $status = 'Pending') {
    $stmt = $this->conn->prepare("
        INSERT INTO payment (order_id, pos_id, payment_method, payment_amount, payment_status) 
        VALUES (?, ?, ?, ?, ?)
    ");
    return $stmt->execute([$orderID, $posID, $method, $amount, $status]);
}

public function getPaymentsByOrder($orderID) {
    $stmt = $this->conn->prepare("SELECT * FROM payment WHERE order_id = ?");
    $stmt->execute([$orderID]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function getPaymentsByPOS($posID) {
    $stmt = $this->conn->prepare("SELECT * FROM payment WHERE pos_id = ?");
    $stmt->execute([$posID]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function deleteOnlineOrder($orderId) {
    $this->conn->prepare("DELETE FROM payment WHERE order_id = ?")->execute([$orderId]);
    $this->conn->prepare("DELETE FROM order_item WHERE order_id = ?")->execute([$orderId]);
    $this->conn->prepare("DELETE FROM order_online WHERE order_id = ?")->execute([$orderId]);
}


public function deletePOSOrder($posId) {
    $this->conn->prepare("DELETE FROM payment WHERE pos_id = ?")->execute([$posId]);
    $this->conn->prepare("DELETE FROM order_item WHERE pos_id = ?")->execute([$posId]);
    $this->conn->prepare("DELETE FROM order_pos WHERE pos_id = ?")->execute([$posId]);
}



}
?>