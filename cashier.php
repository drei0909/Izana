<?php
session_start();
if (!isset($_SESSION['admin_ID'])) {
    header("Location: admin_L.php");
    exit();
}

require_once('./classes/database.php');
$db = new Database();

try {
    $stmt = $db->conn->query("
        SELECT o.*, c.customer_FN, c.customer_LN, p.payment_method 
        FROM `order` o 
        JOIN customer c ON o.customer_ID = c.customer_ID 
        LEFT JOIN payment p ON o.order_id = p.order_id 
        WHERE o.order_status != 'completed' 
        ORDER BY o.order_date DESC
    ");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching orders: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Cashier Panel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <style>
    body {
      background: url('uploads/bgg.jpg') no-repeat center center fixed;
      background-size: cover;
      color: #fff;
    }
    .container {
      background: rgba(0, 0, 0, 0.7);
      padding: 30px;
      border-radius: 15px;
      box-shadow: 0 0 20px rgba(0,0,0,0.5);
    }
    h3 {
      text-align: center;
      margin-bottom: 20px;
    }
    .btn-back {
      position: absolute;
      top: 20px;
      left: 20px;
    }
    .table td, .table th {
      color: #fff;
    }
  </style>
</head>
<body>

<a href="admin.php" class="btn btn-warning btn-back">Back</a>


<div class="container mt-5">
  <h3><i class="fas fa-cash-register"></i> Cashier Panel</h3>

  <div class="table-responsive">
    <table class="table table-dark table-bordered table-hover">
      <thead>
        <tr>
          <th>Order ID</th>
          <th>Customer</th>
          <th>Order Type</th>
          <th>Total</th>
          <th>Payment</th>
          <th>GCash Receipt</th>
          <th>Status</th>
          <th>Placed At</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($orders): ?>
          <?php foreach ($orders as $row): ?>
            <tr>
              <td><?= htmlspecialchars($row['order_id']) ?></td>
              <td><?= htmlspecialchars($row['customer_FN'] . ' ' . $row['customer_LN']) ?></td>
              <td><?= htmlspecialchars($row['order_type']) ?></td>
              <td>₱<?= number_format($row['total_amount'], 2) ?></td>
              <td><?= htmlspecialchars($row['payment_method']) ?></td>
              <td>
                <?php if (!empty($row['receipt'])): ?>
                  <a href="<?= htmlspecialchars($row['receipt']) ?>" target="_blank" class="btn btn-sm btn-primary">View</a>
                <?php else: ?>
                  <span class="text-muted">N/A</span>
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($row['order_status']) ?></td>
              <td><?= htmlspecialchars($row['order_date']) ?></td>
              <td>
                <form action="update_status.php" method="POST" class="d-flex">
                  <input type="hidden" name="order_ID" value="<?= htmlspecialchars($row['order_id']) ?>">
                  <select name="order_status" class="form-select me-2" required>  
                    <option value="">Select Status</option>
                    <option value="pending" <?= $row['order_status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="preparing" <?= $row['order_status'] == 'preparing' ? 'selected' : '' ?>>Preparing</option>
                    <option value="ready for pickup" <?= $row['order_status'] == 'ready for pickup' ? 'selected' : '' ?>>Ready for Pickup</option>
                    <option value="completed" <?= $row['order_status'] == 'completed' ? 'selected' : '' ?>>Completed</option>
                  </select>
                  <button type="submit" class="btn btn-sm btn-success">Update</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="9" class="text-center text-muted">No orders found.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script src="https://kit.fontawesome.com/a076d05399.js"></script>
</body>
</html>
