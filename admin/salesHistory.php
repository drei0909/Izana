<?php
session_start();

require_once('../classes/database.php');
require_once (__DIR__. "/../classes/config.php");
$db = new Database();

if (!isset($_SESSION['admin_ID'])) {
    header("Location: admin_L.php");
    exit();
}

$activePage = 'salesHistory';

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

<?php include ('templates/header.php'); ?>

<div class="wrapper">

 <?php include ('templates/sidebar.php'); ?>

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