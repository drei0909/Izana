<?php
session_start();
require_once('./classes/database.php');
$db = new Database();

// Get the order_id from the URL
$orderID = $_GET['order_id'] ?? null;

if (!$orderID) {
    header("Location: menu.php");
    exit();
}

// Fetch the order details
$stmt = $db->conn->prepare("SELECT * FROM `order` WHERE order_id = ? LIMIT 1");
$stmt->execute([$orderID]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header("Location: menu.php");
    exit();
}

// Fetch customer details for the receipt
$stmt = $db->conn->prepare("SELECT * FROM customer WHERE customer_id = ?");
$stmt->execute([$order['customer_id']]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt | Izana Coffee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Custom styles for receipt page */
        body {
            font-family: 'Quicksand', sans-serif;
            background: #f4f4f4;
            padding: 20px;
        }
        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .receipt-title {
            text-align: center;
            font-size: 2rem;
            margin-bottom: 20px;
        }
        .receipt-details {
            margin-bottom: 20px;
        }
        .table th, .table td {
            text-align: left;
        }
        .btn-complete {
            background: #28a745;
            color: #fff;
            padding: 10px 20px;
            border-radius: 30px;
            width: 100%;
        }
        .btn-complete:hover {
            background: #218838;
        }
    </style>
</head>
<body>

<div class="receipt-container">
    <h2 class="receipt-title">Order Receipt</h2>

    <div class="receipt-details">
        <p><strong>Customer:</strong> <?= htmlspecialchars($customer['customer_FN'] . ' ' . $customer['customer_LN']) ?></p>
        <p><strong>Order ID:</strong> <?= $order['order_id'] ?></p>
        <p><strong>Order Date:</strong> <?= date('M d, Y h:i A', strtotime($order['order_date'])) ?></p>
        <p><strong>Status:</strong> <?= htmlspecialchars($order['order_status']) ?></p>
        <p><strong>Total Amount:</strong> ₱<?= number_format($order['total_amount'], 2) ?></p>
    </div>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Product</th>
                <th>Price (₱)</th>
                <th>Qty</th>
                <th>Subtotal (₱)</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $stmt = $db->conn->prepare("SELECT * FROM order_item WHERE order_id = ?");
            $stmt->execute([$orderID]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($items as $item) {
                $subtotal = $item['price'] * $item['quantity'];
                echo "<tr>
                        <td>" . htmlspecialchars($item['product_name']) . "</td>
                        <td>₱" . number_format($item['price'], 2) . "</td>
                        <td>" . (int)$item['quantity'] . "</td>
                        <td>₱" . number_format($subtotal, 2) . "</td>
                    </tr>";
            }
            ?>
        </tbody>
    </table>

    <button class="btn-complete" onclick="markAsCompleted(<?= $orderID ?>)">Mark as Completed</button>
</div>

<script>
function markAsCompleted(orderID) {
    Swal.fire({
        title: 'Mark this order as completed?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Complete',
        cancelButtonText: 'Cancel'
    }).then(result => {
        if (result.isConfirmed) {
            fetch('update_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    order_ID: orderID,
                    order_status: 'Completed'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Order Completed',
                        text: 'Thank you for your order!',
                        icon: 'success'
                    }).then(() => {
                        window.location.href = 'menu.php'; // Redirect back to the menu page
                    });
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: 'There was an error completing the order.',
                        icon: 'error'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error',
                    text: 'Network error occurred.',
                    icon: 'error'
                });
            });
        }
    });
}
</script>

</body>
</html>
