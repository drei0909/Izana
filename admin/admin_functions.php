    <?php
    session_start();

    require_once('../classes/database.php');
    require_once (__DIR__. "/../classes/config.php");


    header('Content-Type: application/json');


    $db = new Database();



    // Get the updated cart content
    if ($_POST['ref'] == 'get_order_item') {
    $order_id = intval($_POST['order_id']);
    $order_type = $_POST['order_type'] ?? 'online';
    $html = '';
    $total_amount = 0;

    if ($order_type == 'online') {
    $sql = "SELECT o.*, p.product_name 
            FROM order_item o
            INNER JOIN product p ON o.product_id = p.product_id
            WHERE o.order_id = :id";
    } else { // POS
    $sql = "SELECT o.*, p.product_name 
            FROM order_item o
            INNER JOIN product p ON o.product_id = p.product_id
            WHERE o.pos_id = :id";
    }

    $stmt = $db->conn->prepare($sql);
    $stmt->bindParam(':id', $order_id, PDO::PARAM_INT);

    if ($stmt->execute()) {
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch receipt
    $stmtOrder = $db->conn->prepare("SELECT receipt FROM order_online WHERE order_id = :order_id");
    $stmtOrder->bindParam(':order_id', $order_id, PDO::PARAM_INT);
    $stmtOrder->execute();
    $order = $stmtOrder->fetch(PDO::FETCH_ASSOC);

    $html .= '<ul class="list-group">';
    foreach ($rows as $row) {
        $subtotal = floatval($row['price']) * intval($row['quantity']);
        $total_amount += $subtotal;

        $html .= '<li class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-primary fw-bold">' . htmlspecialchars($row['product_name']) . '</span><br>
                        <small>' . intval($row['quantity']) . 'x @ ₱' . number_format($row['price'], 2) . '</small>
                    </div>
                    <span class="fw-bold">₱' . number_format($subtotal, 2) . '</span>
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
        'html' => $html,
        'total_amount' => number_format($total_amount, 2)
    ]);
    } else {
    echo json_encode(['status' => 'error']);
    }
    }



    // Get orders que
    if ($_POST['ref'] == 'get_orders_que') {
        $html = '<div class="row">';

        $statuses = [
        1 => 'Review',
        2 => 'Preparing',
        3 => 'Ready for Pickup',
        ];

        foreach ($statuses as $statusId => $statusName) {
        $html .= '
        <div class="col-md-4">
            <div class="mr-1">
                <ul class="list-group" id="' . strtolower(str_replace(' ', '_', $statusName)) . '">
                    <li class="list-group-item bg-success fw-bold text-white">' . $statusName . '</li>';

        // Modified query to include rejected (status 0) for Review column
        if ($statusId == 1) {
            $orders = $db->getOrders([0, 1]); // get both rejected + review
        } else {
            $orders = $db->getOrders($statusId);
        }

        if (!empty($orders)) {
            foreach ($orders as $row) {
                $dataStatus = ($statusName === 'Review') ? 'pending' : strtolower($statusName);

                // Determine visual state (red for rejected without repay)
                $bgClass = '';
                if ($row['status'] == 0 && empty($row['repay_receipt'])) {
                    $bgClass = 'bg-danger bg-opacity-25 border-danger';
                }

                $liClass = 'list-group-item order-item';
                    if ($row['status'] == 0) { // rejected
                        $liClass .= ' rejected-order border-danger bg-light-danger';
                    }   
                    $html .= '<li class="' . $liClass . '" data-id="' . htmlspecialchars($row['order_id']) . '" data-order-type="' . $row['order_type'] . '" data-status="' . $dataStatus . '">';

                $html .= '<div class="d-flex justify-content-between align-items-center">';
                $html .= '<strong>' . htmlspecialchars($row['customer_FN']) . '</strong> <span class="text-muted small">#00' . $row['order_id'] . '</span>';
                $html .= '</div>';

                // Receipt Display
                $hasOriginal = !empty($row['receipt']);
                $hasRepay = !empty($row['repay_receipt']);
                $uploadPath = '../uploads/'; // Adjust if your folder is outside admin (e.g., '../uploads/')

                if ($hasOriginal || $hasRepay) {
                    $html .= '<div class="mt-2">';
                    if ($hasOriginal && file_exists($uploadPath . $row['receipt'])) {
                        $html .= '
                            <div class="mb-1">
                                <small class="text-muted">Original Receipt:</small><br>
                                <a href="' . $uploadPath . htmlspecialchars($row['receipt']) . '" target="_blank">
                                    <img src="' . $uploadPath . htmlspecialchars($row['receipt']) . '" class="img-thumbnail shadow-sm" style="width:75px; height:75px; object-fit:cover;">
                                </a>
                            </div>';
                    }
                    if ($hasRepay && file_exists($uploadPath . $row['repay_receipt'])) {
                        $html .= '
                            <div>
                                <small class="text-danger fw-bold">Repay Receipt:</small><br>
                                <a href="' . $uploadPath . htmlspecialchars($row['repay_receipt']) . '" target="_blank">
                                    <img src="' . $uploadPath . htmlspecialchars($row['repay_receipt']) . '" class="img-thumbnail border-danger shadow-sm" style="width:75px; height:75px; object-fit:cover;">
                                </a>
                            </div>';
                    }
                    $html .= '</div>';
                }

                // If rejected but customer reuploaded a receipt, auto-reset status to Review (1)
                if ($row['status'] == 0 && !empty($row['repay_receipt'])) {
                    $update = $db->conn->prepare("UPDATE order_online SET status = 1 WHERE order_id = ?");
                    $update->execute([$row['order_id']]);
                }

                // Action buttons
                if ($statusName === 'Review') {
                    $html .= '<div class="mt-2 d-flex gap-1">
                                <button class="btn btn-sm btn-success btn-accept w-50" data-id="' . $row['order_id'] . '" data-type="' . $row['order_type'] . '">
                                    <i class="fas fa-check"></i> Accept
                                </button>
                                <button class="btn btn-sm btn-danger btn-reject w-50" data-id="' . $row['order_id'] . '" data-type="' . $row['order_type'] . '">
                                    <i class="fas fa-times"></i> Reject
                                </button>
                            </div>';
                }

                $html .= '</li>';
            }
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


    // --- Update Order Status + Notify Customer ---
    if (isset($_POST['ref']) && $_POST['ref'] === 'update_order_stats') {
        header('Content-Type: application/json; charset=utf-8');
    

        $order_id  = intval($_POST['id'] ?? 0);
        $status    = intval($_POST['status'] ?? 0);
        $orderType = $_POST['orderType'] ?? '';

        try {
        // --- Update order table ---
        if ($orderType === 'online') {
            $stmt = $db->conn->prepare("UPDATE order_online SET status = ? WHERE order_id = ?");
            $stmt->execute([$status, $order_id]);
        } else {
            $stmt = $db->conn->prepare("UPDATE order_pos SET status = ? WHERE pos_id = ?");
            $stmt->execute([$status, $order_id]);
        }

        // --- Get customer for online order ---
        $customer_id = null;
        if ($orderType === 'online') {
            $getCust = $db->conn->prepare("SELECT customer_id FROM order_online WHERE order_id = ?");
            $getCust->execute([$order_id]);
            $cust = $getCust->fetch(PDO::FETCH_ASSOC);
            $customer_id = $cust['customer_id'] ?? null;
        }

        // --- Insert notification if online ---
        if ($customer_id) {
            // $message = match ($status) {
            //     2       => "Your order #$order_id is now being prepared.",
            //     3       => "Your order #$order_id is ready for pickup!",
            //     default => "Your order #$order_id status has been updated.",
            // };

            switch ($status) {
                    case 1:
                    $message = "Your order #$order_id is for review.";
                    break;
                case 2:
                    $message = "Your order #$order_id is now being prepared.";
                    break;
                case 3:
                    $message = "Your order #$order_id is ready for pickup!";
                    break;
                default:
                    $message = "Your order #$order_id is for review.";
                    break;
            }

            $insert = $db->conn->prepare("
                INSERT INTO notifications (customer_id, order_id, message, is_read, created_at)
                VALUES (?, ?, ?, 0, NOW())
            ");
            $insert->execute([$customer_id, $order_id, $message]);
        }

        echo json_encode([
            'status'  => 'success',
            'message' => 'Order status updated successfully.'
        ]);
        } catch (Throwable $e) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Server error: ' . $e->getMessage()
        ]);
        }
        exit;
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
            // --- Get Online Orders with payment_status ---
            $stmtOnline = $db->conn->prepare("
                SELECT o.order_id AS id, 
                    CONCAT(c.customer_FN, ' ', c.customer_LN) AS customer_name,
                    o.status AS order_status,
                    o.total_amount, 
                    o.receipt, 
                    o.ref_no, 
                    o.created_at AS order_date,
                    'Online' AS order_channel,
                    IFNULL(p.payment_status, 'Pending') AS payment_status
                FROM order_online o
                JOIN customer c ON o.customer_id = c.customer_ID
                LEFT JOIN payment p ON p.order_id = o.order_id
                ORDER BY o.created_at DESC
            ");
            $stmtOnline->execute();
            $onlineOrders = $stmtOnline->fetchAll(PDO::FETCH_ASSOC);

            // --- Get POS (Walk-in) Orders with payment_status ---
            $stmtPos = $db->conn->prepare("
                SELECT p.pos_id AS id, 
                    'Walk-in Customer' AS customer_name, 
                    p.total_amount, 
                    NULL AS receipt, 
                    p.payment_method AS ref_no, 
                    p.created_at AS order_date,
                    'POS' AS order_channel,
                    p.status AS order_status,
                    IFNULL(pay.payment_status, 'Pending') AS payment_status
                FROM order_pos p
                LEFT JOIN payment pay ON pay.pos_id = p.pos_id
                ORDER BY p.created_at DESC
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
        $order_type = $_POST['order_type'];

        try {
        // Update order status to 0 (Cancelled)

        if ($order_type == 'online') {
            $sql_1 = "UPDATE order_online SET status = 4 WHERE order_id = ?";

            $sql_2 = "SELECT total_amount  FROM order_online WHERE order_id = ?";

            // Fetch order details and customer info
            $stmtFetch = $db->conn->prepare("
                SELECT 
                    o.order_id, 
                    o.total_amount, 
                    o.created_at, 
                    o.customer_id,
                    CONCAT(c.customer_FN, ' ', c.customer_LN) AS customer_name
                FROM order_online o
                JOIN customer c ON o.customer_id = c.customer_ID
                WHERE o.order_id = ?
            ");
            $stmtFetch->execute([$order_id]);
            $order = $stmtFetch->fetch(PDO::FETCH_ASSOC);

            if ($order) {
                // Insert notification for the customer
                $notif_msg = "Your order #{$order_id} has been Void!";
                $stmtNotif = $db->conn->prepare("
                    INSERT INTO notifications (customer_id, message, order_id, is_read, created_at)
                    VALUES (?, ?, ?, 0, NOW())
                ");
                $stmtNotif->execute([$order['customer_id'], $notif_msg, $order_id]);
            }
        } else { // POS
            $sql_1 = "UPDATE order_pos SET status = 4 WHERE pos_id = ?";

            $sql_2 = "SELECT total_amount  FROM order_pos WHERE pos_id = ?";
        }

        $stmt = $db->conn->prepare($sql_1);
        $stmt->execute([$order_id]);

        // Fetch order details to update Sales Report counters dynamically
        $stmt2 = $db->conn->prepare($sql_2);
        $stmt2->execute([$order_id]);
        $order = $stmt2->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'success',
            'message' => 'Order has been Void.',
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
        $order_type = $_POST['order_type'];

        try {


        if ($order_type == 'online') {
            $sql_1 = "UPDATE order_online SET status = 5 WHERE order_id = ?";

            // Fetch order details and customer info
            $stmtFetch = $db->conn->prepare("
                SELECT 
                    o.order_id, 
                    o.total_amount, 
                    o.created_at, 
                    o.customer_id,
                    CONCAT(c.customer_FN, ' ', c.customer_LN) AS customer_name
                FROM order_online o
                JOIN customer c ON o.customer_id = c.customer_ID
                WHERE o.order_id = ?
            ");
            $stmtFetch->execute([$order_id]);
            $order = $stmtFetch->fetch(PDO::FETCH_ASSOC);

            if ($order) {
                // Insert notification for the customer
                $notif_msg = "Your order #{$order['order_id']} has been completed. Thank you for ordering with us!";
                $stmtNotif = $db->conn->prepare("
                    INSERT INTO notifications (customer_id, message, order_id, is_read, created_at)
                    VALUES (?, ?, ?, 0, NOW())
                ");
                $stmtNotif->execute([$order['customer_id'], $notif_msg, $order['order_id']]);
            }
        } else { // POS
            $sql_1 = "UPDATE order_pos SET status = 5 WHERE pos_id = ?";
        }

        // Update order status to completed (5)
        $stmt = $db->conn->prepare($sql_1);
        $stmt->execute([$order_id]);

        // Return success response
        echo json_encode([
            'status' => 'success',
            'message' => 'Order marked as completed and customer notified.'
        ]);
        } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
        }
        exit;
    }


    // get order info
    if ($_POST['ref'] == 'get_order_info') {
        $order_id = $_POST['order_id'];

        $query = $db->conn->prepare("
        SELECT 
            o.ref_no, 
            o.receipt, 
            o.pickup_time, 
            c.customer_FN, 
            c.customer_LN, 
            c.customer_contact
        FROM order_online o
        INNER JOIN customer c ON o.customer_id = c.customer_id
        WHERE o.order_id = ?
        ");
        $query->execute([$order_id]);
        $info = $query->fetch(PDO::FETCH_ASSOC);

        if ($info) {
        echo json_encode([
            'status' => 'success',
            'ref_no' => $info['ref_no'],
            'receipt' => $info['receipt'],
            'pickup_time' => $info['pickup_time'] ? date("h:i A", strtotime($info['pickup_time'])) : 'N/A',
            'customer_FN' => $info['customer_FN'],
            'customer_LN' => $info['customer_LN'],
            'customer_contact' => $info['customer_contact']
        ]);
        } else {
        echo json_encode(['status' => 'error', 'message' => 'Order info not found']);
        }
        exit;
    }


    // INSERT NOTIFICATION (called after admin updates status)
    if (isset($_POST['ref']) && $_POST['ref'] === 'insert_notification') {
        $order_id = intval($_POST['order_id']);
        $message = trim($_POST['message']);

        // Fetch customer ID from the order table
        $stmt = $db->conn->prepare("SELECT customer_id FROM order_online WHERE order_id = ?");
        $stmt->execute([$order_id]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($customer) {
        $customer_id = $customer['customer_id'];
        $insert = $db->conn->prepare("
            INSERT INTO notifications (customer_id, order_id, message, is_read)
            VALUES (?, ?, ?, 0)
        ");
        $insert->execute([$customer_id, $order_id, $message]);
        echo json_encode(['status' => 'success']);
        } else {
        echo json_encode(['status' => 'error', 'message' => 'Customer not found for this order.']);
        }

        exit();
    }


    // BLOCK / UNBLOCK CUSTOMER
    if (isset($_POST['ref']) && $_POST['ref'] === 'update_customer_status') {
            header('Content-Type: application/json');

            $customer_id = intval($_POST['customer_id'] ?? 0);
            $action      = trim($_POST['action'] ?? '');
            $reason      = trim($_POST['reason'] ?? ''); // new field for block reason

            if ($customer_id <= 0 || empty($action)) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
                exit;
            }

            try {
                if ($action === 'block') {
                    if (empty($reason)) {
                        echo json_encode(['status' => 'error', 'message' => 'Block reason is required.']);
                        exit;
                    }

                    // Block user and save reason
                    $stmt = $db->conn->prepare("UPDATE customer SET status = 'blocked', block_reason = ? WHERE customer_id = ?");
                    $stmt->execute([$reason, $customer_id]);

                    $message = "Your account has been blocked by the admin. Reason: {$reason}";

                } elseif ($action === 'unblock') {
                    // Unblock user and clear reason
                    $stmt = $db->conn->prepare("UPDATE customer SET status = 'active', block_reason = NULL WHERE customer_id = ?");
                    $stmt->execute([$customer_id]);

                    $message = "Your account has been reactivated. You can now continue ordering.";
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
                    exit;
                }

                // Optional: insert a notification for the customer
                $notif = $db->conn->prepare("
                    INSERT INTO notifications (customer_id, order_id, message, is_read, created_at)
                    VALUES (?, NULL, ?, 0, NOW())
                ");
                $notif->execute([$customer_id, $message]);

                echo json_encode([
                    'status' => 'success',
                    'message' => "Customer has been {$action}ed.",
                    'new_status' => ($action === 'block') ? 'blocked' : 'active'
                ]);
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
            exit;
    }


    // // Fetch customer details
    // if (isset($_POST['ref']) && $_POST['ref'] === 'get_customer_details') {
    //     header('Content-Type: application/json');

    //     $customer_id = intval($_POST['customer_id'] ?? 0);

    //     if ($customer_id <= 0) {
    //     echo json_encode(['status' => 'error', 'message' => 'Invalid customer ID.']);
    //     exit;
    //     }

    //     try {
    //     $stmt = $db->conn->prepare("
    //         SELECT 
    //             customer_ID,
    //             CONCAT(customer_FN, ' ', customer_LN) AS full_name,
    //             customer_email AS email,
    //             customer_contact AS contact,
    //             status,
    //             DATE_FORMAT(created_at, '%M %d, %Y %h:%i %p') AS created_at
    //         FROM customer
    //         WHERE customer_ID = ?
    //         LIMIT 1
    //     ");
    //     $stmt->execute([$customer_id]);
    //     $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    //     if ($customer) {
    //         echo json_encode([
    //             'status' => 'success',
    //             'data' => $customer
    //         ]);
    //     } else {
    //         echo json_encode([
    //             'status' => 'error',
    //             'message' => 'Customer not found.'
    //         ]);
    //     }
    //     } catch (Exception $e) {
    //     echo json_encode([
    //         'status' => 'error',
    //         'message' => 'Database error: ' . $e->getMessage()
    //     ]);
    //     }

    //     exit;
    // }

    // Accept or Reject Order
    if (isset($_POST['ref']) && $_POST['ref'] === 'review_action') {

        $order_id   = intval($_POST['order_id'] ?? 0);
        $action     = $_POST['action'] ?? '';
        $orderType  = $_POST['orderType'] ?? 'online';
        $reason     = trim($_POST['reason'] ?? ''); // reason for rejection

        if (!$order_id || !$action) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
            exit;
        }

        try {
            if ($orderType === 'online') {

                // Fetch customer ID
                $getCust = $db->conn->prepare("SELECT customer_id FROM order_online WHERE order_id = ?");
                $getCust->execute([$order_id]);
                $cust = $getCust->fetch(PDO::FETCH_ASSOC);
                $customer_id = $cust['customer_id'] ?? null;

                $message = '';

                if ($action === 'accept') {
                    // 1️⃣ Update order status to Preparing
                    $stmt = $db->conn->prepare("UPDATE order_online SET status = 2 WHERE order_id = ?");
                    $stmt->execute([$order_id]);

                    // 2️⃣ Update payment_status to Completed
                    $stmt2 = $db->conn->prepare("UPDATE payment SET payment_status = 'Completed' WHERE order_id = ?");
                    $stmt2->execute([$order_id]);

                    $message = "Your order #$order_id has been accepted, and payment has been marked as Completed. It is now being prepared.";

                } elseif ($action === 'reject') {
                    // Reject order and keep in Review (status = 0)
                    $stmt = $db->conn->prepare("UPDATE order_online SET status = 0, reject_reason = ? WHERE order_id = ?");
                    $stmt->execute([$reason, $order_id]);

                    $message = "Your order #$order_id has been rejected." . ($reason ? " Reason: $reason" : "");
                }

                // Insert notification
                if ($customer_id && !empty($message)) {
                    $insert = $db->conn->prepare("
                        INSERT INTO notifications (customer_id, order_id, message, reject_reason, is_read, created_at)
                        VALUES (?, ?, ?, ?, 0, NOW())
                    ");
                    $insert->execute([$customer_id, $order_id, $message, $reason]);
                }
            }

                echo json_encode([
                    'status'  => 'success',
                    'message' => $message
                ]);

            } catch (Throwable $e) {
                echo json_encode([
                    'status'  => 'error',
                    'message' => 'Server error: ' . $e->getMessage()
                ]);
            }
            exit;
    }


    // Save today's sales from Sales Report page
    if (isset($_POST['ref']) && $_POST['ref'] === 'save_sales') {
        $walkIn = floatval($_POST['walk_in_sales'] ?? 0);
        $online = floatval($_POST['online_sales'] ?? 0);
        $total = floatval($_POST['total_sales'] ?? 0);
        $void  = floatval($_POST['void_sales'] ?? 0);

        try {
            $result = $db->saveSalesHistory($walkIn, $online, $total, $void);
            if ($result) {
                echo json_encode(['status' => 'success', 'message' => 'Sales saved successfully!']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to save sales.']);
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }

    

    // Get sales items (aggregated) for a specific date (used by Sales History -> View Details)
    if (isset($_POST['ref']) && $_POST['ref'] === 'get_sales_items') {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $date = $_POST['date'] ?? null;
            if (!$date) {
                echo json_encode(['status' => 'error', 'message' => 'Date is required']);
                exit;
            }

            // Aggregate items from online orders and POS orders for the given date
            $sql = "
                SELECT pi.product_id, pi.product_name, SUM(pi.quantity) AS quantity, pi.price_per_unit
                FROM (
                    SELECT oi.product_id, p.product_name, SUM(oi.quantity) AS quantity, p.product_price AS price_per_unit
                    FROM order_item oi
                    JOIN product p ON oi.product_id = p.product_id
                    JOIN order_online o ON oi.order_id = o.order_id
                    WHERE DATE(o.created_at) = ?
                    GROUP BY oi.product_id
                    UNION ALL
                    SELECT oi.product_id, p.product_name, SUM(oi.quantity) AS quantity, p.product_price AS price_per_unit
                    FROM order_item oi
                    JOIN product p ON oi.product_id = p.product_id
                    JOIN order_pos pos ON oi.pos_id = pos.pos_id
                    WHERE DATE(pos.created_at) = ?
                    GROUP BY oi.product_id
                ) AS pi
                GROUP BY pi.product_id, pi.product_name, pi.price_per_unit
                ORDER BY SUM(pi.quantity) DESC
            ";

            $stmt = $db->conn->prepare($sql);
            $stmt->execute([$date, $date]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $items = [];
            $total = 0.0;
            foreach ($rows as $r) {
                $qty = (int)$r['quantity'];
                $price = (float)$r['price_per_unit'];
                $subtotal = $qty * $price;
                $total += $subtotal;
                $items[] = [
                    'product_name' => $r['product_name'],
                    'quantity' => $qty,
                    'price' => number_format($price, 2)
                ];
            }

            if (empty($items)) {
                echo json_encode(['status' => 'empty']);
            } else {
                echo json_encode(['status' => 'success', 'items' => $items, 'total' => number_format($total, 2)]);
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit();
    }

    // Handle order status updates
    if (isset($_POST['ref']) && $_POST['ref'] === 'update_order_status') {
        $orderId = $_POST['order_id'];
        $action = $_POST['action']; // accept, reject, repay, void, complete
        $orderType = $_POST['order_type']; // online or pos

        // Determine table
        $table = ($orderType === 'pos') ? 'order_pos' : 'order_online';

        // Map actions to payment and order statuses
        $statusMap = [
            'accept'   => ['order_status' => 'Completed', 'payment_status' => 'Paid'],
            'reject'   => ['order_status' => 'Pending', 'payment_status' => 'Pending'],
            'repay'    => ['order_status' => 'Pending', 'payment_status' => 'Pending'],
            'void'     => ['order_status' => 'Void', 'payment_status' => 'Void'],
            'complete' => ['order_status' => 'Completed', 'payment_status' => 'Completed'],
        ];

        if (array_key_exists($action, $statusMap)) {
            $statuses = $statusMap[$action];

            // Update order record
            $sql = "
                UPDATE {$table}
                SET status = :order_status,
                    payment_status = :payment_status
                WHERE " . (($orderType === 'pos') ? "pos_id" : "order_id") . " = :id
            ";
            $stmt = $db->conn->prepare($sql);
            $stmt->execute([
                ':order_status' => $statuses['order_status'],
                ':payment_status' => $statuses['payment_status'],
                ':id' => $orderId
            ]);

            echo json_encode([
                'status' => 'success',
                'message' => 'Order updated successfully.',
                'new_status' => $statuses
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
        }
        exit();
    }

    // Delete sales record
    if ($_POST['ref'] === 'delete_sales_history') {
        $id = intval($_POST['id']);
        try {
            $stmt = $db->conn->prepare("DELETE FROM sales_history WHERE id = ?");
            $stmt->execute([$id]);

            if ($stmt->rowCount() > 0) {
                echo json_encode(['status' => 'success', 'message' => 'Sales record deleted successfully.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No record found or already deleted.']);
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit();
    }

    // Fetch product categories
    if ($_POST['ref'] === 'get_product_categories') {
        $stmt = $db->conn->prepare("SELECT category_id, category, is_active FROM product_categories ORDER BY created_at DESC");
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($categories) {
            echo json_encode(['status' => 'success', 'categories' => $categories]);
        } else {
            echo json_encode(['status' => 'empty', 'categories' => []]);
        }
        exit;
    }

   

    ?>