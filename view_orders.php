<?php
require_once('./classes/database.php');
session_start();

if (!isset($_SESSION['admin_ID'])) {
    header("Location: admin_L.php");
    exit();
}

$db = new Database();

$search = $_GET['search'] ?? '';
$orders = $db->searchOrder($search); // new function in Database class
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>View Orders</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
  <style>
    body {
      background: url('uploads/bgg.jpg') no-repeat center center fixed;
      background-size: cover;
      color: #fff;
    }
    .container-bg {
      background: rgba(0, 0, 0, 0.75);
      padding: 30px;
      border-radius: 15px;
      box-shadow: 0 0 20px rgba(0,0,0,0.6);
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

<div class="d-flex">
  <!-- Sidebar -->
  <div class="sidebar d-flex flex-column p-3 text-white" style="width: 250px;">
    <h4 class="text-white mb-4"><i class="fas fa-coffee me-2"></i>Izana Admin</h4>
    <ul class="nav nav-pills flex-column">
      <li><a href="admin.php" class="nav-link active"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
      <li><a href="view_customers.php" class="nav-link"><i class="fas fa-users me-2"></i>View Customers</a></li>
      <li><a href="view_orders.php" class="nav-link"><i class="fas fa-receipt  me-2"></i>View Orders</a></li>
      <li><a href="manage_products.php" class="nav-link"><i class="fas fa-mug-hot me-2"></i>Manage Products</a></li>
       <li><a href="cashier.php" class="nav-link"><i class="fas fa-cash-register me-2"></i>Cashier</a></li>
      <li><a href="sales_report.php" class="nav-link"><i class="fas fa-chart-line me-2"></i>Sales Report</a></li>
      <li><a href="edit_profile.php" class="nav-link"><i class="fas fa-user-edit me-2"></i>Edit Profile</a></li>
      <li><a href="admin_L.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
    </ul>
  </div>




  <div class="container mt-5 container-bg">
    <h2 class="mb-4 text-center"><i class="fas fa-shopping-cart"></i> Order List</h2>

    <!-- Search Bar -->
    <form method="GET" class="row g-3 mb-4 justify-content-center">
      <div class="col-md-6">
        <div class="input-group">
          <input type="text" name="search" class="form-control" placeholder="Search by order ID, customer name, or email" value="<?= htmlspecialchars($search) ?>">
          <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i> Search</button>
          <a href="view_orders.php" class="btn btn-secondary">Reset</a>
        </div>
      </div>
    </form>

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
              <td colspan="5" class="text-center text-muted py-4">
                <i class="fas fa-box-open fa-2x mb-2"></i><br>No orders found.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</body>
</html>
