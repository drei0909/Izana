<?php
session_start();
if (!isset($_SESSION['admin_ID'])) {
    header("Location: admin_L.php");
    exit();
}

require_once('./classes/database.php');
$db = new Database();

$adminName = htmlspecialchars($_SESSION['admin_FN'] ?? 'Admin');

// Online Orders
$totalOnlineOrders = $db->conn->query("SELECT COUNT(*) FROM `order` WHERE order_channel = 'online'")->fetchColumn();
$totalOnlineSales  = $db->conn->query("SELECT SUM(total_amount) FROM `order` WHERE order_channel = 'online'")->fetchColumn() ?? 0;

// Walk-In Orders
$totalWalkInOrders = $db->conn->query("SELECT COUNT(*) FROM `order` WHERE order_channel = 'walk-in'")->fetchColumn();
$totalWalkInSales  = $db->conn->query("SELECT SUM(total_amount) FROM `order` WHERE order_channel = 'walk-in'")->fetchColumn() ?? 0;

// Total Customers
$totalCustomers = $db->getTotalCustomers();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard | Izana Coffee</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
body { font-family: 'Quicksand', sans-serif; background: #f7f7f7; }
.sidebar { height: 100vh; background-color: #2c2f38; color: #fff; }
.sidebar .nav-link { color: #fff; font-weight: 500; transition: 0.2s; }
.sidebar .nav-link.active, .sidebar .nav-link:hover { background-color: #5a5d69; }
.admin-header { background-color: #fff; padding: 15px 25px; border-bottom: 1px solid #dee2e6; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
.dashboard-content { padding: 25px; }
.card { border-radius: 15px; box-shadow: 0 8px 25px rgba(0,0,0,0.12); transition: transform 0.3s, box-shadow 0.3s; }
.card:hover { transform: translateY(-5px); box-shadow: 0 12px 30px rgba(0,0,0,0.15); }
.card h5 { font-weight: 600; }
.card p { font-weight: bold; font-size: 1.8rem; }
.section-title { border-left: 5px solid #6f4e37; padding-left: 10px; margin-bottom: 15px; color: #6f4e37; }
</style>
</head>
<body>
<div class="d-flex">

  <!-- Sidebar -->
  <div class="sidebar d-flex flex-column p-3" style="width: 250px;">
    <h4 class="text-white mb-4 text-center"><i class="fas fa-coffee me-2"></i>Izana Admin</h4>
    <ul class="nav nav-pills flex-column">
      <li><a href="admin.php" class="nav-link active"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
      <li><a href="view_customers.php" class="nav-link"><i class="fas fa-users me-2"></i>View Customers</a></li>
      <li><a href="view_orders.php" class="nav-link"><i class="fas fa-receipt me-2"></i>View Orders</a></li>
      <li><a href="manage_products.php" class="nav-link"><i class="fas fa-mug-hot me-2"></i>Manage Products</a></li>
      <li><a href="cashier.php" class="nav-link"><i class="fas fa-cash-register me-2"></i>Cashier</a></li>
      <li><a href="manage_cashier.php" class="nav-link"><i class="fas fa-users-cog me-2"></i>Manage Cashier</a></li>
      <li><a href="sales_report.php" class="nav-link"><i class="fas fa-chart-line me-2"></i>Sales Report</a></li>
      <li><a href="edit_profile.php" class="nav-link"><i class="fas fa-user-edit me-2"></i>Edit Profile</a></li>
      <li><a href="Logout_A.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
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

        <!-- Online Orders -->
        <h4 class="section-title">Online Orders</h4>
        <div class="row g-4 mb-4">
          <div class="col-md-6">
            <div class="card text-center bg-gradient shadow-sm text-white" style="background: #198754;">
              <div class="card-body">
                <i class="fas fa-shopping-cart fa-2x mb-2"></i>
                <h5 class="card-title">Total Orders</h5>
                <p class="card-text"><?= $totalOnlineOrders ?></p>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="card text-center bg-gradient shadow-sm text-white" style="background: #ffc107;">
              <div class="card-body">
                <i class="fas fa-dollar-sign fa-2x mb-2"></i>
                <h5 class="card-title">Total Sales</h5>
                <p class="card-text">₱<?= number_format($totalOnlineSales, 2) ?></p>
              </div>
            </div>
          </div>
        </div>

        <!-- Walk-In Orders -->
        <h4 class="section-title">Walk-In Orders</h4>
        <div class="row g-4 mb-4">
          <div class="col-md-6">
            <div class="card text-center bg-gradient shadow-sm text-white" style="background: #0d6efd;">
              <div class="card-body">
                <i class="fas fa-store fa-2x mb-2"></i>
                <h5 class="card-title">Total Orders</h5>
                <p class="card-text"><?= $totalWalkInOrders ?></p>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="card text-center bg-gradient shadow-sm text-white" style="background: #fd7e14;">
              <div class="card-body">
                <i class="fas fa-coins fa-2x mb-2"></i>
                <h5 class="card-title">Total Sales</h5>
                <p class="card-text">₱<?= number_format($totalWalkInSales, 2) ?></p>
              </div>
            </div>
          </div>
        </div>

        <!-- Total Customers -->
        <h4 class="section-title">Registered Customers</h4>
        <div class="row g-4">
          <div class="col-md-6 col-lg-4">
            <div class="card text-center bg-gradient shadow-sm text-white" style="background: #6f4e37;">
              <div class="card-body">
                <i class="fas fa-users fa-2x mb-2"></i>
                <h5 class="card-title">Total Customers</h5>
                <p class="card-text"><?= $totalCustomers ?></p>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
