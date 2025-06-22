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
      background: url('uploads/bgg.jpg') no-repeat center center fixed;
      background-size: cover;
      min-height: 100vh;
      backdrop-filter: blur(4px);
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
    <h4 class="text-white mb-4"><i class="fas fa-coffee me-2"></i>Izana Admin</h4>
    <ul class="nav nav-pills flex-column">
      <li><a href="admin.php" class="nav-link active"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
      <li><a href="view_customers.php" class="nav-link"><i class="fas fa-users me-2"></i>View Customers</a></li>
      <li><a href="view_orders.php" class="nav-link"><i class="fas fa-receipt me-2"></i>View Orders</a></li>
      <li><a href="manage_products.php" class="nav-link"><i class="fas fa-mug-hot me-2"></i>Manage Products</a></li>
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
            <div class="card text-bg-primary">
              <div class="card-body">
                <h5 class="card-title"><i class="fas fa-users me-2"></i>Total Customers</h5>
                <p class="card-text fs-4"><?= $totalCustomers ?></p>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card text-bg-success">
              <div class="card-body">
                <h5 class="card-title"><i class="fas fa-shopping-cart me-2"></i>Total Orders</h5>
                <p class="card-text fs-4"><?= $totalOrders ?></p>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card text-bg-warning">
              <div class="card-body">
                <h5 class="card-title"><i class="fas fa-coins me-2"></i>Total Sales</h5>
                <p class="card-text fs-4">₱<?= number_format($totalSales, 2) ?></p>
              </div>
            </div>
          </div>
        </div>

        <!-- Activity -->
        <div class="mt-5">
          <h5>Recent Activity</h5>
          <div class="alert alert-info">This section can show the 5 latest orders or actions.</div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
