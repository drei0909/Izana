<?php
session_start();
require_once('./classes/database.php');
$db = new Database();

if (!isset($_SESSION['admin_ID'])) {
    header("Location: admin_L.php");
    exit();
}

$adminName = htmlspecialchars($_SESSION['admin_FN'] ?? 'Admin');

// Pagination setup
$limit = 10;  // Number of records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Current page
$offset = ($page - 1) * $limit; // Offset calculation

// Fetch sales history data with pagination
$salesHistory = $db->getSalesHistory($limit, $offset);

// Get total number of records for pagination
$totalSalesHistory = $db->conn->query("SELECT COUNT(*) FROM sales_history")->fetchColumn();
$totalPages = ceil($totalSalesHistory / $limit);

// Handle delete request
if (isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    $db->deleteSalesHistory($deleteId);
    header("Location: salesHistory.php"); // Redirect to refresh page after deletion
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Sales History | Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>
body {
  font-family: 'Quicksand', sans-serif;
  background: #f8f8f8;
  color: #333;
  margin: 0;
  height: 100vh;
  overflow: hidden;
}
.wrapper { display: flex; height: 100vh; overflow: hidden; }
.sidebar {
  width: 250px;
  flex-shrink: 0;
  background: #1c1c1c;
  color: #fff;
  box-shadow: 3px 0 12px rgba(0,0,0,0.25);
  display: flex;
  flex-direction: column;
  position: fixed;
  top: 0;
  bottom: 0;
  left: 0;
  overflow-y: auto;
}
.main {
  margin-left: 250px;
  flex-grow: 1;
  display: flex;
  flex-direction: column;
  height: 100vh;
  overflow: hidden;
}
.content {
  flex-grow: 1;
  overflow-y: auto;
  padding: 20px;
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

.admin-header {
  background: #f4f4f4;
  padding: 15px 25px;
  border-bottom: 1px solid #d6d6d6;
  box-shadow: 0 2px 6px rgba(0,0,0,0.08);
  flex-shrink: 0;
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

.table {
  background: #fff;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.table thead {
  background: #6f4e37;
  color: #fff;
}
.table tbody tr:hover {
  background: #f8f1ed;
}

.pagination .page-item .page-link {
  border-radius: 50%;
  margin: 0 3px;
  color: #1c1c1c;
  background-color: #f4f4f4;
  border: none;
  font-weight: bold;
}
.pagination .page-item.active .page-link {
  background-color: #6f4e37;
  color: #fff;
}
.pagination .page-item .page-link:hover {
  background-color: #333;
  color: #fff;
}
</style>
</head>
<body>
<div class="wrapper">

  <!-- Sidebar -->
  <div class="sidebar p-3">
    <h4 class="text-white mb-4 text-center"><i class="fas fa-mug-hot me-2"></i>Izana Admin</h4>
    <ul class="nav nav-pills flex-column">
      <li><a href="admin.php" class="nav-link"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
      <li><a href="view_customers.php" class="nav-link"><i class="fas fa-users me-2"></i>View Customers</a></li>
      <li><a href="view_orders.php" class="nav-link"><i class="fas fa-receipt me-2"></i>View Orders</a></li>
      <li><a href="manage_products.php" class="nav-link"><i class="fas fa-mug-hot me-2"></i>Manage Products</a></li>
      <li><a href="cashier.php" class="nav-link"><i class="fas fa-cash-register me-2"></i>Online Cashier</a></li>
      <li><a href="manage_cashier.php" class="nav-link"><i class="fas fa-users-cog me-2"></i>POS</a></li>
      <li><a href="sales_report.php" class="nav-link"><i class="fas fa-chart-line me-2"></i>Sales Report</a></li>
      <li><a href="salesHistory.php" class="nav-link active"><i class="fas fa-history me-2"></i>Sales History</a></li>
      <li><a href="edit_profile.php" class="nav-link"><i class="fas fa-user-edit me-2"></i>Edit Profile</a></li>
      <li><a href="Logout_A.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
    </ul>
  </div>

<div class="main">
    <div class="admin-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">Welcome, <?= $adminName ?></h5>
      <span class="text-muted"><i class="fas fa-user-shield me-1"></i>Admin Panel</span>
    </div>

    <div class="content">
      <div class="container-fluid">
        <h4 class="section-title"><i class="fas fa-history me-2"></i>Sales History</h4>

        <!-- Sales Data Table -->
        <div class="card mb-4">
          <div class="card-body">
            <table class="table table-bordered table-hover">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Walk-in Sales (₱)</th>
                  <th>Online Sales (₱)</th>
                  <th>Total Sales (₱)</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($salesHistory as $record): ?>
                  <tr>
                    <td><?= htmlspecialchars($record['order_date']) ?></td>
                    <td><?= number_format($record['walk_in_sales'], 2) ?></td>
                    <td><?= number_format($record['online_sales'], 2) ?></td>
                    <td><?= number_format($record['total_sales'], 2) ?></td>
                    <td>
                      <a href="salesHistory.php?delete=<?= $record['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this record?');">
                        <i class="fas fa-trash-alt"></i> Delete
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Pagination Controls -->
        <nav>
          <ul class="pagination justify-content-center">
            <li class="page-item <?= $page == 1 ? 'disabled' : '' ?>">
              <a class="page-link" href="salesHistory.php?page=<?= $page - 1 ?>">Previous</a>
            </li>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
              <li class="page-item <?= $page == $i ? 'active' : '' ?>">
                <a class="page-link" href="salesHistory.php?page=<?= $i ?>"><?= $i ?></a>
              </li>
            <?php endfor; ?>
            <li class="page-item <?= $page == $totalPages ? 'disabled' : '' ?>">
              <a class="page-link" href="salesHistory.php?page=<?= $page + 1 ?>">Next</a>
            </li>
          </ul>
        </nav>
      </div>
    </div>
  </div>
</div>

</body>
</html>
