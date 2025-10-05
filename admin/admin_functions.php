<?php
session_start();

require_once('../classes/database.php');
require_once (__DIR__. "/../classes/config.php");


header('Content-Type: application/json');


$db = new Database();



// Get the updated cart content
if ($_POST['ref'] == 'get_order_item') {

    $order_id = intval($_POST['order_id']);
    $html = '';

    $stmt = $db->conn->prepare("SELECT 
        o.*, p.product_name
        FROM order_item o
        INNER JOIN product p ON o.product_id = p.product_id
        WHERE o.order_id = :order_id");
    $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch receipt from orders table
        $stmtOrder = $db->conn->prepare("SELECT receipt FROM order_online WHERE order_id = :order_id");
        $stmtOrder->bindParam(':order_id', $order_id, PDO::PARAM_INT);
        $stmtOrder->execute();
        $order = $stmtOrder->fetch(PDO::FETCH_ASSOC);

        $html .= '<ul class="list-group">';
        foreach ($rows as $row) {
            $html .= '<li class="list-group-item">
                        <span class="text-primary fw-bold">' . htmlspecialchars($row['product_name']) . '</span><br>
                        <span>' . htmlspecialchars($row['quantity']) . 'x</span><br>
                        <span>₱' . number_format($row['price'], 2) . '</span><br>
                      </li>';
        }
        $html .= '</ul>';

                    if (!empty($order['receipt'])) {
                $rootPath = "../uploads/receipts/";
                $html .= '
                    <div class="text-center mt-3">
                        <p class="fw-bold mb-1">Receipt:</p>
                        <img src="' . htmlspecialchars($rootPath . $order['receipt']) . '"
                            alt="Receipt Image"
                            class="img-fluid rounded shadow-sm border"
                            style="max-width: 350px; cursor: zoom-in;"
                            onclick="window.open(this.src)">
                    </div>';
    } else {
        $html .= '<p class="text-muted text-center mt-3">No receipt uploaded.</p>';
    }


        echo json_encode([
            'status' => 'success',
            'html' => $html
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
        ]);
    }
}


// Get orders que
if ($_POST['ref'] == 'get_orders_que') {

    $html = '<div class="row">';

    $statuses = [
        1 => 'Pending',
        2 => 'Preparing',
        3 => 'Ready for Pickup',
        4 => 'Cancel'
    ];

    foreach ($statuses as $statusId => $statusName) {
        $html .= '
        <div class="col-md-3">
            <div class="mr-1">
                <ul class="list-group" id="'. strtolower(str_replace(' ', '_', $statusName)) .'">
                    <li class="list-group-item bg-success fw-bold text-white">'. $statusName .'</li>';

        $orders = $db->getCashierOrders($statusId);
        if (!empty($orders)) {
            foreach ($orders as $row) {
                $html .= '<li class="list-group-item order-item" data-id="'. htmlspecialchars($row['order_id']) .'">';
                $html .= '<strong>'. htmlspecialchars($row['customer_FN']) .'</strong><br>';
                $html .= '<small>₱'. htmlspecialchars($row['total_amount']) .'</small>';
                $html .= '</li>';
            }
        } else {
          
        }

        $html .= '
                </ul>
            </div>
        </div>';
    }

    $html .= '</div>';

    echo json_encode([
        'status' => 'success',
        'html' => $html
    ]);
}


// updates order statis
if ($_POST['ref'] == 'update_order_stats') {
    $order_id = intval($_POST['id']);
    $status = intval($_POST['status']);

    $stmt = $db->conn->prepare("
        UPDATE order_online 
        SET status = ?
        WHERE order_id = ?
    ");
    return $stmt->execute([$status, $order_id]);
}

// Place the POS order
if (isset($_POST['ref']) && $_POST['ref'] === "place_pos_order") {
    header('Content-Type: application/json');

    $cart = json_decode($_POST['cart'] ?? "[]", true);
    $paymentMethod  = $_POST['payment_method'] ?? 'Cash';
    $cashReceived   = floatval($_POST['cash_received'] ?? 0);
    $adminID = isset($_SESSION['admin_ID']) ? intval($_SESSION['admin_ID']) : null;

    if (empty($cart)) {
        echo json_encode(['status' => 'error', 'message' => 'Cart is empty']);
        exit;
    }

    // Compute total on server
    $total = 0;
    foreach ($cart as $item) {
        $total += floatval($item['price']) * intval($item['quantity']);
    }

    $change = ($paymentMethod === 'Cash') ? max($cashReceived - $total, 0) : 0;

    try {
        $db->conn->beginTransaction();

        // Check if order_pos has admin_id column
        $hasAdminColumnStmt = $db->conn->prepare("
            SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'order_pos' AND COLUMN_NAME = 'admin_id'
        ");
        $hasAdminColumnStmt->execute();
        $hasAdmin = (bool)$hasAdminColumnStmt->fetchColumn();

        if ($hasAdmin && $adminID !== null) {
            $stmtPos = $db->conn->prepare("
                INSERT INTO order_pos (total_amount, payment_method, created_at, admin_id)
                VALUES (:total_amount, :payment_method, NOW(), :admin_id)
            ");
            $stmtPos->execute([
                ':total_amount' => $total,
                ':payment_method' => $paymentMethod,
                ':admin_id' => $adminID
            ]);
        } else {
            $stmtPos = $db->conn->prepare("
                INSERT INTO order_pos (total_amount, payment_method, created_at)
                VALUES (:total_amount, :payment_method, NOW())
            ");
            $stmtPos->execute([
                ':total_amount' => $total,
                ':payment_method' => $paymentMethod
            ]);
        }

        $posID = $db->conn->lastInsertId();

        $stmtItem = $db->conn->prepare("
            INSERT INTO order_item (order_id, pos_id, product_id, quantity, price)
            VALUES (NULL, :pos_id, :product_id, :qty, :price)
        ");

        foreach ($cart as $item) {
            $stmtItem->execute([
                ':pos_id' => $posID,
                ':product_id' => intval($item['id']),
                ':qty' => intval($item['quantity']),
                ':price' => floatval($item['price'])
            ]);
        }

        // Insert payment record
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
            'change' => number_format($change, 2)
        ]);
    } catch (Exception $e) {
        $db->conn->rollBack();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
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



?>