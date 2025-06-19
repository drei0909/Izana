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



    // Add new product (admin only)
    public function addProduct($name, $price, $stock, $admin_id = null) {
        $sql = "INSERT INTO Product (product_name, product_price, stock_quantity, added_by_admin_id)
                VALUES (:name, :price, :stock, :admin_id)";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':name' => $name,
            ':price' => $price,
            ':stock' => $stock,
            ':admin_id' => $admin_id
        ]);
    }

    // Get all products
    public function getAllProducts() {
        $sql = "SELECT * FROM Product";
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
