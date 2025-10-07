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
    ];

    foreach ($statuses as $statusId => $statusName) {
        $html .= '
        <div class="col-md-4">
            <div class="mr-1">
                <ul class="list-group" id="'. strtolower(str_replace(' ', '_', $statusName)) .'">
                    <li class="list-group-item bg-success fw-bold text-white">'. $statusName .'</li>';

        $orders = $db->getOrders($statusId);
        if (!empty($orders)) {
            foreach ($orders as $row) {
                $html .= '<li class="list-group-item order-item" data-id="'. htmlspecialchars($row['order_id']) .'" data-order-type="'.$row['order_type'].'">';
                $html .= '<div><strong>'. htmlspecialchars($row['customer_FN']) .' </strong> <span class="float-end"> #00'.$row['order_id'].'</span> </div>';
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
    $orderType = $_POST['orderType'];

    

    if($orderType == 'online'){
        $stmt = $db->conn->prepare("
            UPDATE order_online 
            SET status = ?
            WHERE order_id = ?
        ");
        return $stmt->execute([$status, $order_id]);

    }else{
        $stmt = $db->conn->prepare("
            UPDATE order_pos 
            SET status = ?
            WHERE pos_id = ?
        ");
        return $stmt->execute([$status, $order_id]);
    }
    
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
                INSERT INTO order_pos (total_amount, payment_method, status, created_at)
                VALUES (:total_amount, :payment_method, 1, NOW())
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




// Fetch both online and walk-in orders for admin panel
if (isset($_POST['ref']) && $_POST['ref'] === "fetch_orders") {
    try {
        // --- Get Online Orders ---
        $stmtOnline = $db->conn->prepare("
    SELECT o.order_id AS id, 
           CONCAT(c.customer_FN, ' ', c.customer_LN) AS customer_name,
           o.status AS order_status,
           o.total_amount, 
           o.receipt, 
           o.ref_no, 
           o.created_at AS order_date,
           'Online' AS order_channel
    FROM order_online o
    JOIN customer c ON o.customer_id = c.customer_ID
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

// Cancel Order (Void)
if (isset($_POST['ref']) && $_POST['ref'] === 'cancel_order') {
    $order_id = intval($_POST['order_id']);

    try {
        // Update order status to 0 (Cancelled)
        $stmt = $db->conn->prepare("UPDATE order_online SET status = 4 WHERE order_id = ?");
        $stmt->execute([$order_id]);

        // Fetch order details to update Sales Report counters dynamically
        $stmt2 = $db->conn->prepare("SELECT total_amount  FROM order_online WHERE order_id = ?");
        $stmt2->execute([$order_id]);
        $order = $stmt2->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'success',
            'message' => 'Order has been cancelled.',
            'order_id' => $order_id,
            'total_amount' => $order['total_amount'],
            
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}


// Complete Order
if (isset($_POST['ref']) && $_POST['ref'] === 'complete_order') {
    $order_id = intval($_POST['order_id']);

    try {
        // --- Step 1: Mark order as completed ---
        $stmt = $db->conn->prepare("UPDATE order_online SET status = 5 WHERE order_id = ?");
        $stmt->execute([$order_id]);

        // --- Step 2: Fetch order details for insertion ---
        $stmtFetch = $db->conn->prepare("
            SELECT 
                o.order_id,
                o.total_amount,
                o.created_at,
                CONCAT(c.customer_FN, ' ', c.customer_LN) AS customer_name
            FROM order_online o
            JOIN customer c ON o.customer_id = c.customer_ID
            WHERE o.order_id = ?
        ");
        $stmtFetch->execute([$order_id]);
        $order = $stmtFetch->fetch(PDO::FETCH_ASSOC);

        if ($order) {
            // --- Step 3: Insert into sales_report (avoid duplicates) ---
            $stmtSales = $db->conn->prepare("
                INSERT INTO sales_report (order_id, customer_name, total_amount, order_channel, created_at)
                VALUES (?, ?, ?, 'Online', NOW())
                ON DUPLICATE KEY UPDATE 
                    total_amount = VALUES(total_amount),
                    created_at = NOW()
            ");
            $stmtSales->execute([
                $order['order_id'],
                $order['customer_name'],
                $order['total_amount']
            ]);
        }

        echo json_encode(['status' => 'success', 'message' => 'Order marked as completed and added to Sales Report.']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }

    exit;
}

// get order info
if (isset($_POST['ref']) && $_POST['ref'] === 'get_order_info') {
    $order_id = intval($_POST['order_id']);

    $stmt = $db->conn->prepare("
        SELECT o.ref_no, o.receipt, c.customer_FN, c.customer_LN
        FROM order_online o
        JOIN customer c ON o.customer_id = c.customer_ID
        WHERE o.order_id = :order_id
    ");
    $stmt->execute([':order_id' => $order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($order) {
        echo json_encode([
            'status' => 'success',
            'ref_no' => $order['ref_no'],
            'receipt' => $order['receipt'],
            'customer_FN' => $order['customer_FN'],
            'customer_LN' => $order['customer_LN']
        ]);
    } else {
        echo json_encode(['status' => 'error']);
    }
    exit;
}


?>