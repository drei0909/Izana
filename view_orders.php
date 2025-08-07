<?php
require_once('./classes/database.php');
session_start();

if (!isset($_SESSION['admin_ID'])) {
    header("Location: admin_L.php");
    exit();
}

$db = new Database();
$orders = $db->getAllOrder();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>View Orders</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: url('uploads/bgg.jpg') no-repeat center center fixed;
      background-size: cover;
    }
    .container-bg {
      background: rgba(0, 0, 0, 0.75);
      padding: 30px;
      border-radius: 15px;
      box-shadow: 0 0 20px rgba(0,0,0,0.6);
      color: #fff;
    }
    table {
      color: #fff;
    }
    .table-dark th {
      background-color: #343a40 !important;
    }
    .btn-back {
      position: absolute;
      top: 20px;
      left: 20px;
    }
    a.receipt-link {
      color: #ffc107;
      text-decoration: underline;
    }
    a.receipt-link:hover {
      color: #ffca2c;
    }
  </style>
</head>
<body>
  <a href="admin.php" class="btn btn-warning btn-back"><i class="fas fa-arrow-left"></i> Back</a>

  <div class="container mt-5 container-bg">
    <h2 class="mb-4 text-center">Order List</h2>

    <div class="table-responsive">
      <table class="table table-dark table-hover table-bordered align-middle">
        <thead>
          <tr>
            <th>Order ID</th>
            <th>Customer</th>
            <th>Total Amount</th>
            <th>Receipt</th>
            <th>Order Date</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($orders)): ?>
            <?php foreach ($orders as $order): ?>
              <tr>
                <td><?= htmlspecialchars($order['order_id']) ?></td>
                <td><?= htmlspecialchars($order['customer_FN'] . ' ' . $order['customer_LN']) ?></td>
                <td>₱<?= number_format($order['total_amount'], 2) ?></td>
                <td>
                 <?php if (!empty($order['receipt'])): ?>
   <a href="uploads/receipts/<?= htmlspecialchars($order['receipt']) ?>" target="_blank" class="receipt-link">View</a>

<?php else: ?>
    <span class="text-muted">No receipt</span>
<?php endif; ?>
                </td>
                <td><?= date("F j, Y h:i A", strtotime($order['order_date'])) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="5" class="text-center">No orders found.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <script src="https://kit.fontawesome.com/a076d05399.js"></script>
</body>
</html>
