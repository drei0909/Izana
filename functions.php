<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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

            echo json_encode(['status' => 'success', 'message' => 'Your account has been created successfully.']);
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
if ($_POST['ref'] === 'fetch_notifications') {

    $customer_id = $_SESSION['customer_ID'] ?? null;
    if (!$customer_id) {
        echo json_encode(["status" => "error", "message" => "No customer logged in."]);
        exit;
    }

 // Fetch notifications with related order info
        $stmt = $db->conn->prepare("
            SELECT 
                n.notification_id, 
                n.message, 
                n.order_id, 
                n.is_read, 
                n.created_at, 
                o.status AS order_status, 
                o.reject_reason
            FROM notifications n
            LEFT JOIN order_online o ON n.order_id = o.order_id
            WHERE n.customer_id = ?
            ORDER BY n.created_at DESC
        ");
        $stmt->execute([$customer_id]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Add flag to show repay button for rejected orders
      foreach ($notifications as &$n) {
        
        $n['show_repay'] = (!empty($n['order_id']) && $n['order_status'] == 0);
    }


        // Count unread notifications
        $unread_stmt = $db->conn->prepare("
            SELECT COUNT(*) AS unread_count 
            FROM notifications 
            WHERE customer_id = ? AND is_read = 0
        ");
        $unread_stmt->execute([$customer_id]);
        $unread_count = (int)($unread_stmt->fetch(PDO::FETCH_ASSOC)['unread_count'] ?? 0);

        echo json_encode([
            "status" => "success",
            "notifications" => $notifications,
            "unread_count" => $unread_count
        ]);
        exit;
}



// Check Account Status
if (isset($_POST['ref']) && $_POST['ref'] === 'check_acc_status') {
    require_once('./classes/database.php');
    $db = new Database();

    if (!isset($_SESSION['customer_ID'])) {
        echo json_encode(["status" => "error", "msg" => "No session found"]);
        exit;
    }

    $stmt = $db->conn->prepare("SELECT status, block_reason FROM customer WHERE customer_id = ?");
    $stmt->execute([$_SESSION['customer_ID']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        // 🔥 If account is blocked, destroy session immediately
        if (strtolower($row['status']) === 'blocked') {
            session_unset();
            session_destroy();
            echo json_encode([
                "status" => "blocked",
                "reason" => $row['block_reason'] ?? null
            ]);
            exit;
        }

        echo json_encode([
            "status" => "success",
            "account_status" => strtolower($row['status']),
            "reason" => $row['block_reason'] ?? null
        ]);
    } else {
        echo json_encode(["status" => "error", "msg" => "Customer not found"]);
    }
    exit;
}



//Mark all notifications as read
if ($_POST['ref'] === 'mark_notifications_read') {

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $customer_id = $_SESSION['customer_ID'] ?? null;

    if (!$customer_id) {
        echo json_encode(["status" => "error", "message" => "No customer logged in."]);
        exit;
    }

    $update = $db->conn->prepare("UPDATE notifications SET is_read = 1 WHERE customer_id = ? AND is_read = 0");
    $update->execute([$customer_id]);

    echo json_encode(["status" => "success", "message" => "All notifications marked as read."]);
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

// Place Order online
if (isset($_POST['ref']) && $_POST['ref'] === "place_order") {
  
        $customerID = intval($_SESSION['customer_ID'] ?? 0);
        $refNo      = trim($_POST['ref_no'] ?? '');
        $receiptPath = null;

        if (!$customerID) {
            echo json_encode(['status' => 'error', 'message' => 'You must be logged in to place an order.']);
            exit();
        }

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
            $target = 'uploads/receipts/' . $filename;

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

        $pickupTime = $_POST['pickup_time'] ?? null; // ✅ capture time

        $insertOrder = $db->conn->prepare("
            INSERT INTO order_online (customer_id, total_amount, receipt, ref_no, created_at, pickup_time, status)
            VALUES (:customer_id, :total_amount, :receipt, :ref_no, NOW(), :pickup_time, :status)
        ");
        $insertOrder->execute([
            ':customer_id' => $customerID,
            ':total_amount' => $total,
            ':receipt' => $receiptPath,
            ':ref_no' => $refNo,
            ':pickup_time' => $pickupTime ?: null, // ✅ store null if empty
            ':status' => 1
        ]);

            $orderID = $db->conn->lastInsertId();

            // --- Insert items into order_item ---
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
                    ':order_id' => $orderID,
                    ':pos_id' => null,
                    ':product_id' => $item['product_id'],
                    ':quantity' => $item['qty'],
                    ':price' => $item['product_price']
                ]);
            }

            // --- Insert payment record ---
            $insertPayment = $db->conn->prepare("
                INSERT INTO payment (order_id, pos_id, payment_method, payment_amount, payment_status)
                VALUES (:order_id, :pos_id, :method, :amount, :status)
            ");
            $insertPayment->execute([
                ':order_id' => $orderID,
                ':pos_id' => null,
                ':method' => 'GCash',
                ':amount' => $total,
                ':status' => 'Pending'
            ]);

            // --- Notification ---
            $message = "Your order #$orderID has been placed and is pending confirmation.";
            $notif = $db->conn->prepare("
                INSERT INTO notifications (customer_id, order_id, message, is_read, created_at)
                VALUES (?, ?, ?, 0, NOW())
            ");
            $notif->execute([$customerID, $orderID, $message]);

            // --- Clear cart ---
            $clear = $db->conn->prepare("DELETE FROM cart WHERE customer_id = :customer_id");
            $clear->execute([':customer_id' => $customerID]);

            $db->conn->commit();

            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            $db->conn->rollBack();
            echo json_encode(['status' => 'error', 'message' => 'Order failed: ' . $e->getMessage()]);
        }

        exit();
}


// Handle Customer Login
if(isset($_POST['ref']) && $_POST['ref'] === "login_customer") {
    header('Content-Type: application/json');

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if($username === '' || $password === '') {
        echo json_encode(['status'=>'error','message'=>'All fields are required.']);
        exit;
    }

    $result = $db->loginCustomer($username, $password);

    if($result['status'] === 'error') {
        // Blocked account
        if($result['message'] === 'account_blocked') {
            echo json_encode([
                'status'=>'error',
                'message'=>'blocked',
                'reason'=>$result['reason'] ?? 'No reason provided'
            ]);
            exit;
        }

        // Other errors
        $messages = [
            'user_not_found'=>'No user found with this username.',
            'account_is_not_verified'=>'Account not verified yet.',
            'wrong_password'=>'Incorrect password.',
            'db_error'=>'Database error occurred. Please try again.'
        ];

        $msgKey = $result['message'] ?? 'unexpected_error';
        $msg = $messages[$msgKey] ?? 'Unexpected error.';
        echo json_encode(['status'=>'error','message'=>$msg]);
        exit;
    }

    // Success login
    $user = $result['user'];
    $_SESSION['customer_ID'] = $user['customer_id'];
    $_SESSION['customer_FN'] = $user['customer_FN'];

    $stmt = $db->conn->prepare("UPDATE customer SET last_login = NOW() WHERE customer_id = ?");
    $stmt->execute([$user['customer_id']]);

    echo json_encode([
        'status'=>'success',
        'redirect'=>'menu.php'
    ]);
    exit;
}



// --- Handle Rebuy Order ---
if (isset($_POST['ref']) && $_POST['ref'] === 'rebuy_order') {
    if (session_status() === PHP_SESSION_NONE) session_start();

    $customer_id = $_SESSION['customer_ID'] ?? null;
    $order_id = intval($_POST['order_id'] ?? 0);

    if (!$customer_id || !$order_id) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
        exit;
    }

    try {
        // Get previous order items
        $stmt = $db->conn->prepare("
            SELECT product_id, quantity
            FROM order_item
            WHERE order_id = ?
        ");
        $stmt->execute([$order_id]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$items) {
            echo json_encode(['status' => 'error', 'message' => 'No items found in this order.']);
            exit;
        }

        // Clear current cart
        $clear = $db->conn->prepare("DELETE FROM cart WHERE customer_id = ?");
        $clear->execute([$customer_id]);

        // Insert all items again
        $insert = $db->conn->prepare("
            INSERT INTO cart (customer_id, product_id, qty)
            VALUES (?, ?, ?)
        ");
        foreach ($items as $item) {
            $insert->execute([$customer_id, $item['product_id'], $item['quantity']]);
        }

        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}


// Handle Forgot Password Request
if (isset($_POST['ref']) && $_POST['ref'] === "forgot_password_request") {
    $email = trim($_POST['email'] ?? '');

    if ($email === '') {
        echo json_encode(['status' => 'error', 'message' => 'Email is required.']);
        exit;
    }

    try {
        //FIX: Use correct column name "customer_email"
        $stmt = $db->conn->prepare("SELECT customer_id, customer_FN FROM customer WHERE customer_email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            echo json_encode(['status' => 'error', 'message' => 'No account found with this email.']);
            exit;
        }

        // Generate secure reset token
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        // Create password_resets table if not exists
        $db->conn->prepare("
            CREATE TABLE IF NOT EXISTS password_resets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                customer_id INT NOT NULL,
                token VARCHAR(255) NOT NULL,
                expires_at DATETIME NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ")->execute();

        // Remove old tokens for the same user
        $db->conn->prepare("DELETE FROM password_resets WHERE customer_id = ?")->execute([$user['customer_id']]);

        // Insert new reset token
        $db->conn->prepare("
            INSERT INTO password_resets (customer_id, token, expires_at) VALUES (?, ?, ?)
        ")->execute([$user['customer_id'], $token, $expires]);

       $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443)
    ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'];
        $path = rtrim(dirname($_SERVER['PHP_SELF']), '/\\'); // detects your folder name
        $resetLink = $protocol . $host . $path . "/reset_password.php?token=$token";

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.hostinger.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'info@izanacafe.shop';
        $mail->Password = 'Izana@Cafe9';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('info@izanacafe.shop', 'Izana Coffee Shop');
        $mail->addAddress($email, $user['customer_FN']);
        $mail->isHTML(true);
        $mail->Subject = 'Reset Your Izana Coffee Account Password';
        $mail->Body = "
            <h3>Hello {$user['customer_FN']},</h3>
            <p>Click the button below to reset your password:</p>
            <p><a href='$resetLink' style='background:#f2c9a0;padding:10px 20px;color:#000;border-radius:5px;text-decoration:none;'>Reset Password</a></p>
            <p>This link will expire in 15 minutes.</p>
        ";

        $mail->send();
        echo json_encode(['status' => 'success', 'message' => 'Password reset link has been sent to your email.']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to send reset email.']);
    }
    exit;
}


// Handle reset password (AJAX)
if (isset($_POST['ref']) && $_POST['ref'] === 'reset_password') {
    $token = trim($_POST['token'] ?? '');
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($token === '' || $new === '' || $confirm === '') {
        echo json_encode(['status'=>'error','message'=>'All fields are required.']); exit;
    }
    if ($new !== $confirm) {
        echo json_encode(['status'=>'error','message'=>'Passwords do not match.']); exit;
    }
    if (strlen($new) < 6) {
        echo json_encode(['status'=>'error','message'=>'Password must be at least 6 characters.']); exit;
    }

    try {
        // fetch token
        $stmt = $db->conn->prepare("SELECT customer_id, expires_at FROM password_resets WHERE token = ? LIMIT 1");
        $stmt->execute([$token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) { echo json_encode(['status'=>'error','message'=>'Invalid token.']); exit; }
        if (strtotime($row['expires_at']) < time()) {
            $db->conn->prepare("DELETE FROM password_resets WHERE token = ?")->execute([$token]);
            echo json_encode(['status'=>'error','message'=>'Token expired. Please request a new link.']); exit;
        }

        $hashed = password_hash($new, PASSWORD_BCRYPT);

        // update user's password — use your actual column name
        $db->conn->prepare("UPDATE customer SET customer_password = ? WHERE customer_id = ?")
                 ->execute([$hashed, $row['customer_id']]);

        // remove the token
        $db->conn->prepare("DELETE FROM password_resets WHERE token = ?")->execute([$token]);

        echo json_encode(['status'=>'success','message'=>'Password updated. You can now login.']);
        exit;
    } catch (Exception $e) {
        echo json_encode(['status'=>'error','message'=>'Server error.']); exit;
    }
}

// UPDATE CUSTOMER INFO
if (isset($_POST['ref']) && $_POST['ref'] === 'update_customer_info') {
    

    if (!isset($_SESSION['customer_ID'])) {
        echo json_encode(['status' => 'error', 'message' => 'Session expired. Please log in again.']);
        exit;
    }

    $customer_ID = $_SESSION['customer_ID'];
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($email)) {
        echo json_encode(['status' => 'error', 'message' => 'Username and Email cannot be empty.']);
        exit;
    }

    try {
        // Check for duplicate username or email
        $check = $db->conn->prepare("
            SELECT customer_id FROM customer 
            WHERE (customer_username = ? OR customer_email = ?) AND customer_id != ?
        ");
        $check->execute([$username, $email, $customer_ID]);
        if ($check->rowCount() > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Username or email already taken.']);
            exit;
        }

        if (!empty($password)) {
            // Hash new password if provided
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $query = "
                UPDATE customer 
                SET customer_username = ?, customer_email = ?, customer_contact = ?, customer_password = ?
                WHERE customer_id = ?
            ";
            $params = [$username, $email, $contact, $hashedPassword, $customer_ID];
        } else {
            // Update without changing password
            $query = "
                UPDATE customer 
                SET customer_username = ?, customer_email = ?, customer_contact = ?
                WHERE customer_id = ?
            ";
            $params = [$username, $email, $contact, $customer_ID];
        }

        $stmt = $db->conn->prepare($query);
        $stmt->execute($params);

        echo json_encode([
            'status' => 'success',
            'message' => 'Your account information has been updated successfully.'
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
    exit;
}

// DELETE CUSTOMER ACCOUNT
if (isset($_POST['ref']) && $_POST['ref'] === 'delete_account') {
    

    try {
        if (!isset($_SESSION['customer_ID'])) {
            echo json_encode(['status' => 'error', 'message' => 'Not logged in.']);
            exit;
        }

        $customer_ID = $_SESSION['customer_ID'];

        // Delete customer record
        $stmt = $db->conn->prepare("DELETE FROM customer WHERE customer_ID = ?");
        $result = $stmt->execute([$customer_ID]);

        if ($result) {
            session_destroy();
            echo json_encode(['status' => 'success', 'message' => 'Your account has been deleted.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete your account.']);
        }

    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
    }
    exit;
}

// repay order
if ($_POST['ref'] == 'repay_order') {
    try {
        $orderId = $_POST['order_id'] ?? null;

        if (!$orderId) {
            echo json_encode(['status' => 'error', 'msg' => 'Missing order ID']);
            exit;
        }

        // Check file upload
        if (!isset($_FILES['receipt']) || $_FILES['receipt']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['status' => 'error', 'msg' => 'Receipt file upload failed']);
            exit;
        }

        $file = $_FILES['receipt'];
        $fileName = time() . '_' . basename($file['name']);
        $targetDir = 'uploads/';
        $targetPath = $targetDir . $fileName;

        // Create folder if missing
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        // Validate image
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            echo json_encode(['status' => 'error', 'msg' => 'Invalid file type']);
            exit;
        }

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            echo json_encode(['status' => 'error', 'msg' => 'Failed to move uploaded file']);
            exit;
        }

        // Update DB (use repay_receipt field)
        $stmt = $db->conn->prepare("UPDATE order_online SET repay_receipt = :receipt, status = 1 WHERE order_id = :order_id");
        $stmt->execute([
            ':receipt' => $fileName,
            ':order_id' => $orderId
        ]);

        echo json_encode(['status' => 'success', 'msg' => 'Receipt uploaded successfully. Please wait for admin review.']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'msg' => 'Server error: ' . $e->getMessage()]);
    }
}



?>