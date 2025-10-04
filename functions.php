<?php
session_start();
require_once('./classes/database.php');


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


// if(isset($_POST['ref']) && $_POST['ref'] === "place_order") {
    
//     $ref_no = $_POST['ref_no'];

//     $ext = strtolower(pathinfo($_FILES['pop']['name'], PATHINFO_EXTENSION));
//     $allowed = ['jpg', 'jpeg', 'png'];

//     if (!in_array($ext, $allowed)) {
//         $_SESSION['error'] = "Invalid file type. Only JPG, JPEG, or PNG allowed.";
//         // header("Location: checkout.php");
//         exit();
//     }

//     // Create a directory for receipts if not already existing
//     if (!is_dir('uploads/receipts')) mkdir('uploads/receipts', 0777, true);

//     $filename = uniqid('gcash_', true) . '.' . $ext;
//     $target = 'uploads/receipts/' . $filename;

//     if (!move_uploaded_file($_FILES['pop']['tmp_name'], $target)) {
//         $_SESSION['error'] = "Failed to upload receipt.";
//         // header("Location: checkout.php");
//         exit();
//     }

//     $receiptPath = $filename;
    
// }

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

        // --- Insert order into order_online ---
        $insertOrder = $db->conn->prepare("
            INSERT INTO order_online (customer_id, total_amount, receipt, ref_no, created_at, status)
            VALUES (:customer_id, :total_amount, :receipt, :ref_no, NOW(), :status)
        ");
        $insertOrder->execute([
            ':customer_id' => $customerID,
            ':total_amount' => $total,
            ':receipt'      => $receiptPath,
            ':ref_no'       => $refNo,
            ':status'       => 1
        ]);

        $orderID = $db->conn->lastInsertId(); // ✅ new order_id

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


// Fetch both online and walk-in orders for admin panel
if (isset($_POST['ref']) && $_POST['ref'] === "fetch_orders") {
    try {
        // --- Get Online Orders ---
        $stmtOnline = $db->conn->prepare("
            SELECT o.order_id AS id, 
                   CONCAT(c.customer_FN, ' ', c.customer_LN) AS customer_name,
                   o.total_amount, 
                   o.receipt, 
                   o.ref_no, 
                   o.order_date AS order_date,
                   'Online' AS order_channel
            FROM order_online o
            JOIN customers c ON o.customer_id = c.customer_ID
        ");
        $stmtOnline->execute();
        $onlineOrders = $stmtOnline->fetchAll(PDO::FETCH_ASSOC);

        // --- Get POS (Walk-in) Orders ---
        $stmtPos = $db->conn->prepare("
            SELECT p.pos_id AS id, 
                   'Walk-in Customer' AS customer_name, 
                   p.total_amount, 
                   NULL AS receipt, 
                   p.payment_method AS ref_no, 
                   p.created_at AS order_date,
                   'POS' AS order_channel
            FROM order_pos p
        ");
        $stmtPos->execute();
        $posOrders = $stmtPos->fetchAll(PDO::FETCH_ASSOC);

        // --- Merge and sort by date ---
        $orders = array_merge($onlineOrders, $posOrders);
        usort($orders, function($a, $b) {
            return strtotime($b['order_date']) - strtotime($a['order_date']);
        });

        echo json_encode(['status' => 'success', 'orders' => $orders]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}


// Place the POS order
if (isset($_POST['ref']) && $_POST['ref'] === "place_pos_order") {
    $cart = json_decode($_POST['cart'] ?? "[]", true);
    $paymentMethod  = $_POST['payment_method'] ?? 'Cash';
    $cashReceived   = floatval($_POST['cash_received'] ?? 0);

    if (empty($cart)) {
        echo json_encode(['status' => 'error', 'message' => 'Cart is empty']);
        exit();
    }

    $total = 0;
    foreach ($cart as $item) {
        $total += floatval($item['price']) * intval($item['quantity']);
    }

    $change = ($paymentMethod === 'Cash') ? max($cashReceived - $total, 0) : 0;

    try {
        $db->conn->beginTransaction();

        $stmtPos = $db->conn->prepare("
            INSERT INTO order_pos (total_amount, payment_method, created_at)
            VALUES (:total_amount, :payment_method, NOW())
        ");
        $stmtPos->execute([
            ':total_amount'   => $total,
            ':payment_method' => $paymentMethod
        ]);
        $posID = $db->conn->lastInsertId();

        $stmtItem = $db->conn->prepare("
            INSERT INTO order_item (order_id, pos_id, product_id, quantity, price)
            VALUES (NULL, :pos_id, :product_id, :qty, :price)
        ");
        foreach ($cart as $item) {
            $stmtItem->execute([
                ':pos_id'     => $posID,
                ':product_id' => intval($item['id']),
                ':qty'        => intval($item['quantity']),
                ':price'      => floatval($item['price'])
            ]);
        }

        $stmtPayment = $db->conn->prepare("
            INSERT INTO payment (order_id, pos_id, payment_date, payment_method, payment_amount, payment_status)
            VALUES (NULL, :pos_id, NOW(), :method, :amount, 'Completed')
        ");
        $stmtPayment->execute([
            ':pos_id' => $posID,
            ':method' => $paymentMethod,
            ':amount' => $total
        ]);

        $db->conn->commit();

        echo json_encode([
            'status' => 'success',
            'message' => 'POS order placed successfully',
            'change'  => number_format($change, 2)
        ]);
    } catch (Exception $e) {
        $db->conn->rollBack();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit();
}


// Handle Registration
if (isset($_POST['ref']) && $_POST['ref'] === "register_customer") {
    $fname    = trim($_POST['first_name'] ?? '');
    $lname    = trim($_POST['last_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // REQUIRED check
    if ($fname === '' || $lname === '' || $username === '' || $email === '' || $password === '') {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
        exit;
    }
    // EMAIL format check
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid email address.']);
        exit;
    }
    // PASSWORD strength check
    if (!preg_match('/^(?=.*[A-Z])(?=.*\W)(?=.*\d).{6,}$/', $password)) {
        echo json_encode(['status' => 'error', 'message' => 'Password must contain at least 6 characters, 1 uppercase, 1 number, and 1 special character.']);
        exit;
    }

    try {
        $success = $db->registerCustomer($fname, $lname, $username, $email, $password);
        if ($success) {
            echo json_encode(['status' => 'success', 'message' => 'Your account has been created successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Username or Email already taken.']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}



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







?>