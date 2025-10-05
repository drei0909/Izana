<?php
session_start();

require_once('../classes/database.php');
require_once (__DIR__. "/../classes/config.php");

if (!isset($_SESSION['admin_ID'])) {
    header("Location: admin_L.php");
    exit();
}

$db = new Database();

$adminName = htmlspecialchars($_SESSION['admin_FN'] ?? 'Admin');

// Online Orders
$totalOnlineOrders = $db->conn->query("SELECT COUNT(*) FROM `order_online`")->fetchColumn();
$totalOnlineSales  = $db->conn->query("SELECT SUM(total_amount) FROM `order_online`")->fetchColumn() ?? 0;

// Walk-In Orders
$totalWalkInOrders = $db->conn->query("SELECT COUNT(*) FROM `order_pos`")->fetchColumn();
$totalWalkInSales  = $db->conn->query("SELECT SUM(total_amount) FROM `order_pos`")->fetchColumn() ?? 0;

// Total Customers
$totalCustomers = $db->getTotalCustomers();
?>
<!DOCTYPE html>
  <html lang="en">
  <head>
  <meta charset="UTF-8">
  <title>Admin Dashboard | Izana Coffee</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link rel="icon" type="image/svg+xml" href="uploads/icon.svg">
  <style>
  body {
    font-family: 'Quicksand', sans-serif;
    background: #e0e0e0;
    color: #2b2b2b;
    margin: 0;
    height: 100vh;
    overflow: hidden; /* prevent double scroll */
  }

  /* Layout */
  .wrapper {
    display: flex;
    height: 100vh;
    overflow: hidden;
  }

  .sidebar {
    width: 250px;
    background: #1c1c1c;
    color: #fff;
    flex-shrink: 0;
    overflow-y: auto;
  }
  .sidebar .nav-link {
    color: #bdbdbd;
    font-weight: 500;
    margin-bottom: 10px;
    padding: 10px 15px;
    border-radius: 12px;
    transition: all 0.3s ease;
  }
  .sidebar .nav-link.active, .sidebar .nav-link:hover {
    background-color: #6f4e37;
    color: #fff;
    transform: translateX(6px);
  }

  .main {
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
  }

  .admin-header {
    background: #f4f4f4;
    padding: 15px 25px;
    border-bottom: 1px solid #d6d6d6;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
  }

  .dashboard-content {
    flex-grow: 1;
    overflow-y: auto;
    padding: 20px;
  }

  .section-title {
    border-left: 6px solid #6f4e37;
    padding-left: 12px;
    margin: 30px 0 20px;
    font-weight: 700;
    color: #333;
    text-transform: uppercase;
    letter-spacing: 1px;
  }

  
  .card {
    border: none;
    border-radius: 15px;
    background: #f4f4f4;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }
  
  .card .card-body i {
    opacity: 0.9;
  }
  .card h5 {
    font-weight: 600;
    margin-top: 10px;
  }
  .card p {
    font-weight: bold;
    font-size: 1.7rem;
    margin-top: 8px;
    color: #1c1c1c;
  }

  .bg-coffee { background: #6f4e37 !important; color:#fff; }

  /* Responsive */
  @media (max-width: 992px) {
    .sidebar {
      position: fixed;
      height: 100%;
      z-index: 1000;
      transform: translateX(-100%);
      transition: transform 0.3s ease;
    }
    .sidebar.show {
      transform: translateX(0);
    }
    .main {
      margin-left: 0;
    }
    .toggle-btn {
      display: inline-block;
      cursor: pointer;
      font-size: 1.5rem;
    }
  }
</style>
</head>
<body>
  
<div class="wrapper">

<?php include ('templates/sidebar.php'); ?>

  <!-- Main -->
  <div class="main">
    <!-- Header -->
    <div class="admin-header d-flex justify-content-between align-items-center">
      <div class="d-flex align-items-center gap-3">
        <span class="toggle-btn d-lg-none text-dark" onclick="toggleSidebar()"><i class="fas fa-bars"></i></span>
        <h5 class="mb-0">Welcome, <?= $adminName ?></h5>
      </div>
      <span class="text-muted"><i class="fas fa-user-shield me-1"></i>Admin Panel</span>
    </div>

    <!-- Content -->
    <div class="dashboard-content">
      <div class="container-fluid">

        <!-- Online Orders -->
        <h4 class="section-title">Online Orders</h4>
        <div class="row g-4 mb-4">
          <div class="col-md-6">
            <div class="card text-center">
              <div class="card-body">
                <i class="fas fa-shopping-cart fa-2x mb-2 text-dark"></i>
                <h5>Total Orders</h5>
                <p><?= $totalOnlineOrders ?></p>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="card text-center">
              <div class="card-body">
                <i class="fas fa-dollar-sign fa-2x mb-2 text-dark"></i>
                <h5>Total Sales</h5>
                <p>₱<?= number_format($totalOnlineSales, 2) ?></p>
              </div>
            </div>
          </div>
        </div>

        <!-- Walk-In Orders -->
        <h4 class="section-title">Walk-In Orders</h4>
        <div class="row g-4 mb-4">
          <div class="col-md-6">
            <div class="card text-center">
              <div class="card-body">
                <i class="fas fa-store fa-2x mb-2 text-dark"></i>
                <h5>Total Orders</h5>
                <p><?= $totalWalkInOrders ?></p>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="card text-center">
              <div class="card-body">
                <i class="fas fa-coins fa-2x mb-2 text-dark"></i>
                <h5>Total Sales</h5>
                <p>₱<?= number_format($totalWalkInSales, 2) ?></p>
              </div>
            </div>
          </div>
        </div>

        <!-- Customers -->
        <h4 class="section-title">Registered Customers</h4>
        <div class="row g-4">
          <div class="col-md-6 col-lg-4">
            <div class="card text-center bg-coffee">
              <div class="card-body">
                <i class="fas fa-users fa-2x mb-2"></i>
                <h5>Total Customers</h5>
                <p><?= $totalCustomers ?></p>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>

  </div>
</div>

<script>
function toggleSidebar() {
  document.getElementById("sidebar").classList.toggle("show");
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>