<?php
session_start();

  require_once('../classes/database.php');
  require_once (__DIR__. "/../classes/config.php");
  $db = new Database();

  if (!isset($_SESSION['admin_ID'])) {
      header("Location: admin_L.php");
      exit();
}

$adminName = htmlspecialchars($_SESSION['admin_FN'] ?? 'Admin');

$salesHistory = $db->getSalesHistory();

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
      <table id="productTable" class="table table-bordered table-hover">
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
      </div>
    </div>
  </div>
</div>

<!-- jQuery (needed for DataTables only) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Bootstrap 5 Bundle (includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<?php if (isset($_GET['updated']) && $_GET['updated'] === 'success'): ?>
<script>
  function toggleSidebar() {
    document.getElementById("sidebar").classList.toggle("show");
  }
</script>
<?php endif; ?>

<script>
    $(document).ready(function(){
        $('#productTable').DataTable();
    });
</script>

</body>
</html>