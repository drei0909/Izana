<?php
session_start();
if (!isset($_SESSION['admin_ID'])) {
    header("Location: admin_L.php");
    exit();
}

require_once('./classes/database.php');
$db = new Database();

$adminName = htmlspecialchars($_SESSION['admin_FN'] ?? 'Admin');

// Fetch totals
$totalCustomers = $db->getTotalCustomers();
$totalOrders    = $db->getTotalOrders();
$totalSales     = $db->getTotalSales();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard | Izana Coffee</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
body {
    font-family: 'Quicksand', sans-serif;
    background: #f7f7f7;
}
.sidebar {
    height: 100vh;
    background-color: #343a40;
    color: #fff;
}
.sidebar .nav-link { color: #fff; }
.sidebar .nav-link.active,
.sidebar .nav-link:hover { background-color: #6c757d; }
.admin-header {
    background-color: #fff;
    padding: 15px 25px;
    border-bottom: 1px solid #dee2e6;
}
.dashboard-content {
    padding: 25px;
}
.card { box-shadow: 0 5px 15px rgba(0,0,0,0.1); transition: transform 0.2s; }
.card:hover { transform: translateY(-5px); }
</style>
</head>
<body>
<div class="d-flex">

  <!-- Sidebar -->
  <div class="sidebar d-flex flex-column p-3" style="width: 250px;">
    <h4 class="text-white mb-4"><i class="fas fa-coffee me-2"></i>Izana Admin</h4>
    <ul class="nav nav-pills flex-column">
      <li><a href="admin.php" class="nav-link active"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
      <li><a href="view_customers.php" class="nav-link"><i class="fas fa-users me-2"></i>View Customers</a></li>
      <li><a href="view_orders.php" class="nav-link"><i class="fas fa-receipt me-2"></i>View Orders</a></li>
      <li><a href="manage_products.php" class="nav-link"><i class="fas fa-mug-hot me-2"></i>Manage Products</a></li>
      <li><a href="manage_cashier.php" class="nav-link"><i class="fas fa-cash-register me-2"></i>Manage Cashier</a></li>
      <li><a href="sales_report.php" class="nav-link"><i class="fas fa-chart-line me-2"></i>Sales Report</a></li>
      <li><a href="edit_profile.php" class="nav-link"><i class="fas fa-user-edit me-2"></i>Edit Profile</a></li>
      <li><a href="admin_L.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
    </ul>
  </div>

  <!-- Main Content -->
  <div class="flex-grow-1">
    <!-- Top Header -->
    <div class="admin-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">Welcome, <?= $adminName ?></h5>
      <span class="text-muted"><i class="fas fa-user-shield me-1"></i>Admin Panel</span>
    </div>

    <!-- Dashboard Content -->
    <div class="dashboard-content">
      <div class="container-fluid">
        <h4 class="mb-4">Dashboard Overview</h4>
        <div class="row g-4">
          <div class="col-md-4">
            <div class="card text-bg-primary text-center">
              <div class="card-body">
                <h5 class="card-title">Total Customers</h5>
                <p class="card-text fs-3"><?= $totalCustomers ?></p>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card text-bg-success text-center">
              <div class="card-body">
                <h5 class="card-title">Total Orders</h5>
                <p class="card-text fs-3"><?= $totalOrders ?></p>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card text-bg-warning text-center">
              <div class="card-body">
                <h5 class="card-title">Total Sales</h5>
                <p class="card-text fs-3">₱<?= number_format($totalSales, 2) ?></p>
              </div>
            </div>
          </div>
        </div>
        <!-- Dashboard content ends here -->
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
