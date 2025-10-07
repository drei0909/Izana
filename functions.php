<?php
session_start();
require_once('./classes/database.php');
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');


$db = new Database();

// Check if 'ref' is set in the POST request
if ($_POST['ref'] == 'add_to_cart') {
    $carts = $_POST['cart'];

  foreach($carts as $row){

        // --- Changed: use local variables and execute([...]) to avoid bindParam reference issues ---
        $productId = isset($row['id']) ? intval($row['id']) : 0;
        $quantity  = isset($row['quantity']) ? intval($row['quantity']) : 0;

        // skip invalid entries
        if ($productId <= 0 || $quantity <= 0) {
            continue;
            }

        //1st: check if product is added already in the cart
        $check_item_query = $db->conn->prepare("SELECT * FROM cart 
            WHERE product_id = :product_id
            AND customer_id = :customer_id");
        // use execute with values (avoids bindParam-by-reference issues)
        $check_item_query->execute([
            ':product_id' => $productId,
            ':customer_id' => intval($_SESSION['customer_ID'])
        ]);

  //2nd: if product exists
    if ($check_item_query->rowCount() > 0) {
        $cart = $check_item_query->fetch(PDO::FETCH_ASSOC);
        $currentQty = intval($cart['qty']);
        $newQty = $currentQty + $quantity;


     //3rd: if exists, update the qty
     $update_query = $db->conn->prepare("UPDATE cart 
     SET qty = :qty 
     WHERE product_id = :product_id
     AND customer_id = :customer_id");
     $update_query->execute([
        ':qty' => $newQty,
        ':product_id' => $productId,
        ':customer_id' => intval($_SESSION['customer_ID'])
      ]);

        } else {

                    // // 4th: If the product does not exist in the cart, insert it
                    $insert_query = $db->conn->prepare("INSERT INTO cart (product_id, customer_id, qty) 
                        VALUES (:product_id, :customer_id, :qty)");
                    $insert_query->execute([
                        ':product_id' => $productId,
                        ':customer_id' => intval($_SESSION['customer_ID']),
                        ':qty' => $quantity
                    ]);
                        
        }

  } 

    // Return a simple success response so frontend can refresh the cart
    echo json_encode(['status' => 'success']);
    exit;
}


// Get the updated cart content
if ($_POST['ref'] == 'show_cart') {
 

    $cart = [];
    $html_cart_content = '';
    $cart_grand_total = 0;  
    $cart_total =    0;
    

    $select_cart_query = $db->conn->prepare("SELECT 
        cart.*, product.product_name, product.product_price
        FROM cart
        INNER JOIN product ON cart.product_id = product.product_id
        WHERE customer_id = :customer_id");
    $select_cart_query->bindParam(':customer_id', $_SESSION['customer_ID'], PDO::PARAM_INT);
    if ($select_cart_query->execute()) {
        $carts = $select_cart_query->fetchAll(PDO::FETCH_ASSOC);

        foreach ($carts as $cart) {


            $cart_subtotal = $cart['qty'] * $cart['product_price'];
            
            $cart_grand_total +=   $cart_subtotal; 

           $html_cart_content .= '
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <strong>' . $cart['qty'] . ' x ' . $cart['product_name'] . '</strong><br>
                    <small>₱' . $cart['product_price'] . ' each</small><br>
                    <small>₱' . $cart_subtotal . '</small>
                </div>
                <button class="btn btn-sm btn-outline-danger delete-cart-item ms-2" data-id="' . $cart['cart_id'] . '">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </div>
            <hr>';

        }

        echo json_encode([
            'status' => 'success',
            'html_cart_content' => $html_cart_content,
            'cart_grand_total' => number_format($cart_grand_total, 2)
        ]);

    } else {
        echo json_encode([
            'status' => 'error',
        ]);
    }
}  


// Delete item from cart
if (isset($_POST['ref']) && $_POST['ref'] == 'delete_cart_item') {
    $cart_id = $_POST['cart_id'];

    $delete_query = $db->conn->prepare("DELETE FROM cart 
        WHERE cart_id = :cart_id AND customer_id = :customer_id");
    $delete_query->bindParam(':cart_id', $cart_id, PDO::PARAM_INT);
    $delete_query->bindParam(':customer_id', $_SESSION['customer_ID'], PDO::PARAM_INT);

    if ($delete_query->execute()) {
        echo json_encode(['status' => 'success']);
    } 
}


/**
 * Generate a secure verification code.
 *
 * @param int $length Length of the code to generate (default 4).
 * @return string Uppercase alphanumeric verification code.
 */
function generate_verification_code($length = 4) {
    // Characters to use (uppercase letters + digits)
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $maxIndex = strlen($chars) - 1;
    $code = '';

    for ($i = 0; $i < $length; $i++) {
        // Use random_int for cryptographically secure random numbers
        $index = random_int(0, $maxIndex);
        $code .= $chars[$index];
    }

    return $code;
}

// Handle Registration
if (isset($_POST['ref']) && $_POST['ref'] === "register_customer") {
    $fname    = trim($_POST['first_name'] ?? '');
    $lname    = trim($_POST['last_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $contact  = trim($_POST['contact'] ?? '');
    $password = $_POST['password'] ?? '';
  
    // Code Generator
    $verification_code	 = generate_verification_code();

    if ($fname === '' || $lname === '' || $username === '' || $email === '' || $email === '' ||$password === '') {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid email address.']);
        exit;
    }

    if (!preg_match('/^(?=.*[A-Z])(?=.*\W)(?=.*\d).{6,}$/', $password)) {
        echo json_encode(['status' => 'error', 'message' => 'Password must contain at least 6 characters, 1 uppercase, 1 number, and 1 special character.']);
        exit;
    }

    try {
        $success = $db->registerCustomer($fname, $lname, $username, $email, $contact, $password, $verification_code);
        if ($success) {



            $mail = new PHPMailer(true);

            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.hostinger.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'info@izanacafe.shop'; // change this
                $mail->Password = 'Izana@Cafe9';     // use app password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom('info@izanacafe.shop', 'Izana Coffee Shop');
                $mail->addAddress($email, "$fname $lname");

                $mail->isHTML(true);
                $mail->Subject = 'Welcome to Izana Coffee Shop!';
                $mail->Body = "
                    <h3>Hello $fname!</h3>
                    <p>Thank you for registering at <strong>Izana Coffee Shop</strong> ☕</p>
                    <p>Use this code <b>$verification_code</b> to verify your account</p>
                ";

                $mail->send();
            } catch (Exception $e) {
                error_log('Mailer Error: ' . $mail->ErrorInfo);
            }

            echo json_encode(['status' => 'success', 'message' => 'Your account has been created successfully. Check your email!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Username or Email already taken.']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// Handle Email Verification
if (isset($_POST['ref']) && $_POST['ref'] === "email_verification") {
    $verification_code = $_POST['verification_code'] ?? '';
    $email = $_POST['email'] ?? '';

   
    if ($email === '' || $verification_code === '') {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid email address.']);
        exit;
    }

    try {
        $success = $db->emailVerify($email, $verification_code);
        if ($success) {
            echo json_encode(['status' => 'success', 'message' => 'Your account has been verified successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Wrong verification code']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

//handle the fetching of updates on admin side(Menu preview)
if (isset($_POST['ref']) && $_POST['ref'] === "menu_preview") {
    try {
        $stmt = $db->conn->query("
            SELECT 
                p.product_id, 
                p.product_name, 
                p.product_price, 
                p.image_path, 
                c.category
            FROM product p
            LEFT JOIN product_categories c 
                ON p.category_id = c.category_id
            ORDER BY c.category_id
        ");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $grouped = [];
        foreach ($products as $p) {
            if (strtolower($p['category']) === 'Frappe') continue;
            $grouped[$p['category']][] = $p;
        }

        ob_start();
        foreach ($grouped as $category => $items): ?>
          <div class="category-title"><?= htmlspecialchars($category) ?></div>
          <div class="row">
            <?php foreach ($items as $item): 
                $img = !empty($item['image_path']) 
                      ?  htmlspecialchars($item['image_path']) 
                      : "uploads/default.jpg";
            ?>
              <div class="col-md-4 mb-4">
                <div class="menu-card">
                  <img src="<?= $img ?>" alt="<?= htmlspecialchars($item['product_name']) ?>">
                  <div class="menu-name"><?= htmlspecialchars($item['product_name']) ?></div>
                  <div class="menu-price">₱<?= number_format($item['product_price'], 2) ?></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endforeach;
        $html = ob_get_clean();

        echo json_encode(['status' => 'success', 'html' => $html]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}


// Fetch notifications
if (isset($_POST['ref']) && $_POST['ref'] === 'fetch_notifications') {
    try {
        $customer_id = $_SESSION['customer_id'] ?? null;
        if (!$customer_id) {
            echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
            exit;
        }

        // Fetch last 10 notifications for this customer
        $stmt = $db->conn->prepare("
            SELECT id, message, is_read, created_at
            FROM notifications
            WHERE customer_id = ?
            ORDER BY created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$customer_id]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Unread count
        $stmtUnread = $db->conn->prepare("
            SELECT COUNT(*) FROM notifications WHERE customer_id = ? AND is_read = 0
        ");
        $stmtUnread->execute([$customer_id]);
        $unreadCount = $stmtUnread->fetchColumn();

        echo json_encode([
            'status' => 'success',
            'unread_count' => $unreadCount,
            'notifications' => $notifications
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// Mark all as read
if (isset($_POST['ref']) && $_POST['ref'] === 'mark_notifications_read') {
    try {
        $customer_id = $_SESSION['customer_id'] ?? null;
        if ($customer_id) {
            $stmt = $db->conn->prepare("
                UPDATE notifications SET is_read = 1 WHERE customer_id = ? AND is_read = 0
            ");
            $stmt->execute([$customer_id]);
        }
        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// Fetch cart item count
if (isset($_POST['ref']) && $_POST['ref'] === 'get_cart_count') {
    $customer_id = $_SESSION['customer_ID'] ?? null;
    if (!$customer_id) {
        echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
        exit;
    }

    $stmt = $db->conn->prepare("SELECT COUNT(*) FROM cart WHERE customer_id = ?");
    $stmt->execute([$customer_id]);
    $count = $stmt->fetchColumn();

    echo json_encode(['status' => 'success', 'count' => intval($count)]);
    exit;
}


// Place the order(online cashier)
if (isset($_POST['ref']) && $_POST['ref'] === "place_order") {
    $customerID = intval($_SESSION['customer_ID']);
    $refNo      = trim($_POST['ref_no'] ?? '');
    $receiptPath = null;

    // --- Validate receipt upload ---
    if (isset($_FILES['pop']) && $_FILES['pop']['error'] === 0) {
        $ext = strtolower(pathinfo($_FILES['pop']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png'];

        if (!in_array($ext, $allowed)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid file type. Only JPG, JPEG, or PNG allowed.']);
            exit();
        }

        if (!is_dir('uploads/receipts')) mkdir('uploads/receipts', 0777, true);

        $filename = uniqid('gcash_', true) . '.' . $ext;
        $target   = 'uploads/receipts/' . $filename;

        if (!move_uploaded_file($_FILES['pop']['tmp_name'], $target)) {
            echo json_encode(['status' => 'error', 'message' => 'Failed to upload receipt.']);
            exit();
        }

        $receiptPath = $filename;
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Proof of payment is required.']);
        exit();
    }

    // --- Calculate total from cart ---
    $stmt = $db->conn->prepare("
        SELECT SUM(c.qty * p.product_price) AS total
        FROM cart c
        INNER JOIN product p ON c.product_id = p.product_id
        WHERE c.customer_id = :customer_id
    ");
    $stmt->execute([':customer_id' => $customerID]);
    $total = $stmt->fetchColumn() ?? 0;

    if ($total <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Cart is empty.']);
        exit();
    }

    try {
        $db->conn->beginTransaction();

        $insertOrder = $db->conn->prepare("
            INSERT INTO order_online (customer_id, total_amount, receipt, ref_no, created_at, status)
            VALUES (:customer_id, :total_amount, :receipt, :ref_no, NOW(), :status)
        ");
        $insertOrder->execute([
            ':customer_id' => $customerID,
            ':total_amount' => $total,
            ':receipt'      => $receiptPath,
            ':ref_no'       => $refNo,
            ':status'       => 0
        ]);

        $orderID = $db->conn->lastInsertId();

        // --- Insert each cart item into order_item ---
        $cartItems = $db->conn->prepare("
            SELECT c.product_id, c.qty, p.product_price
            FROM cart c
            INNER JOIN product p ON c.product_id = p.product_id
            WHERE c.customer_id = :customer_id
        ");
        $cartItems->execute([':customer_id' => $customerID]);
        $items = $cartItems->fetchAll(PDO::FETCH_ASSOC);

        $insertItem = $db->conn->prepare("
            INSERT INTO order_item (order_id, pos_id, product_id, quantity, price)
            VALUES (:order_id, :pos_id, :product_id, :quantity, :price)
        ");

        foreach ($items as $item) {
            $insertItem->execute([
                ':order_id'   => $orderID,
                ':pos_id'     => null, 
                ':product_id' => $item['product_id'],
                ':quantity'   => $item['qty'],
                ':price'      => $item['product_price']
            ]);
        }

        // --- Insert payment record ---
        $insertPayment = $db->conn->prepare("
            INSERT INTO payment (order_id, payment_date, payment_method, payment_amount, payment_status)
            VALUES (:order_id, NOW(), :method, :amount, :status)
        ");
        $insertPayment->execute([
            ':order_id' => $orderID,
            ':method'   => 'GCash',
            ':amount'   => $total,
            ':status'   => 'Pending'  // admin will confirm later
        ]);

        $clear = $db->conn->prepare("DELETE FROM cart WHERE customer_id = :customer_id");
        $clear->execute([':customer_id' => $customerID]);

        $db->conn->commit();

        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        $db->conn->rollBack();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }

    exit();
}




?>