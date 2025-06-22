<?php
require_once('./classes/database.php');
session_start();

if (!isset($_SESSION['admin_ID'])) {
    header("Location: admin_L.php");
    exit();
}

$db = new Database();
$orders = $db->getAllOrder(); // ✅ Make sure this method name matches exactly
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>View Orders</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
  <h2 class="mb-4 text-center">Order List</h2>

  <a href="admin.php" class="btn btn-secondary mb-3">Back</a>

  <div class="table-responsive">
    <table class="table table-striped table-hover align-middle">
      <thead class="table-dark">
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
                  <a href="uploads/<?= htmlspecialchars($order['receipt']) ?>" target="_blank">View</a>
                <?php else: ?>
                  No receipt
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
</body>
</html>
