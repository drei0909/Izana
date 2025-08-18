<?php
session_start();
if (!isset($_SESSION['admin_ID'])) {
    header("Location: admin_L.php");
    exit();
}

require_once('./classes/database.php');
$db = new Database();

$keyword = $_GET['search'] ?? '';

try {
    if (!empty($keyword)) {
        $stmt = $db->conn->prepare("
            SELECT o.*, c.customer_FN, c.customer_LN, p.payment_method 
            FROM `order` o 
            JOIN customer c ON o.customer_ID = c.customer_ID 
            LEFT JOIN payment p ON o.order_id = p.order_id 
            WHERE o.order_status != 'completed'
              AND (
                  o.order_id LIKE :keyword
                  OR CONCAT(c.customer_FN, ' ', c.customer_LN) LIKE :keyword
                  OR c.customer_FN LIKE :keyword
                  OR c.customer_LN LIKE :keyword
                  OR c.customer_email LIKE :keyword
              )
            ORDER BY o.order_date DESC
        ");
        $stmt->execute([':keyword' => "%$keyword%"]);
    } else {
        $stmt = $db->conn->query("
            SELECT o.*, c.customer_FN, c.customer_LN, p.payment_method 
            FROM `order` o 
            JOIN customer c ON o.customer_ID = c.customer_ID 
            LEFT JOIN payment p ON o.order_id = p.order_id 
            WHERE o.order_status != 'completed' 
            ORDER BY o.order_date DESC
        ");
    }
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

     .sidebar {
      height: 100vh;
      background-color: rgba(52, 58, 64, 0.95);
    }
    .sidebar .nav-link {
      color: #ffffff;
    }
    .sidebar .nav-link.active,
    .sidebar .nav-link:hover {
      background-color: #6c757d;
    }
    .admin-header {
      background-color: rgba(255,255,255,0.9);
      padding: 15px 20px;
      border-bottom: 1px solid #dee2e6;
    }
    .dashboard-content {
      padding: 25px;
      background-color: rgba(255, 255, 255, 0.95);
      min-height: 100vh;
    }
    .card {
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }

  </style>
</head>
<body>

<div class="d-flex">
  <!-- Sidebar -->
  <div class="sidebar d-flex flex-column p-3 text-white" style="width: 250px;">
    <h4 class="text-white mb-4"><i ></i>Izana Admin</h4>
    <ul class="nav nav-pills flex-column">
      <li><a href="admin.php" class="nav-link active"></i>Dashboard</a></li>
      <li><a href="view_customers.php" class="nav-link"><i class=></i>View Customers</a></li>
      <li><a href="view_orders.php" class="nav-link"><i class=></i>View Orders</a></li>
      <li><a href="manage_products.php" class="nav-link"><i class=></i>Manage Products</a></li>
      <li><a href="cashier.php" class="nav-link"><i class=></i>Cashier</a></li>
      <li><a href="manage_cashier.php" class="nav-link"><i class=></i>Manage Cashier</a></li>
      <li><a href="sales_report.php" class="nav-link"><i class=></i>Sales Report</a></li>
      <li><a href="edit_profile.php" class="nav-link"><i class=></i>Edit Profile</a></li>
      <li><a href="admin_L.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
    </ul>
  </div>

<div class="container mt-5">
  <h3><i class="fas fa-cash-register"></i> Cashier Panel</h3>

  <!-- Filter/Search Bar -->
<form method="GET" class="d-flex justify-content-center mb-4">
    <input type="text" 
           name="search" 
           class="form-control w-50 me-2" 
           placeholder="Search orders..." 
           value="<?= htmlspecialchars($keyword) ?>">
    <button type="submit" class="btn btn-primary me-2">
        <i class="fas fa-search"></i> Filter
    </button>
    <a href="cashier.php" class="btn btn-secondary">
        <i class="fas fa-undo"></i> Reset
    </a>
</form>


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
