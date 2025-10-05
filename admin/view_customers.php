<?php
session_start();

require_once('../classes/database.php');
require_once (__DIR__. "/../classes/config.php");

$active_page = 'view_customers';

if (!isset($_SESSION['admin_ID'])) {
    header("Location: admin_L.php");
    exit();
}

$db = new Database();
$customers = $db->getAllCustomers();

$adminName = htmlspecialchars($_SESSION['admin_FN'] ?? 'Admin');
?>

<?php include ('templates/header.php'); ?>

<div class="wrapper">

<?php include ('templates/sidebar.php'); ?>

  <!-- Main content -->
 <div class="main">
  <div class="admin-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Welcome, <?= $adminName ?></h5>
    <span class="text-muted"><i class="fas fa-user-shield me-1"></i>Admin Panel</span>
  </div>

   
    <div class="dashboard-content">
      <div class="container-fluid">
        <h4 class="section-title"><i class="fas fa-users me-2"></i>Customer List</h4>

        <div class="card p-3">
          <div class="table-responsive">
            <table id="productTable" class="table table-hover table-bordered align-middle">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Full Name</th>
                  <th>Email</th>
                  <th>Registered At</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($customers)): ?>
                  <?php foreach ($customers as $customer): ?>
                    <tr>
                      <td><?= htmlspecialchars($customer['customer_id']) ?></td>
                      <td><?= htmlspecialchars($customer['customer_FN'] . ' ' . $customer['customer_LN']) ?></td>
                      <td><?= htmlspecialchars($customer['customer_email']) ?></td>
                      <td><?= date("M d, Y h:i A", strtotime($customer['created_at'])) ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="4" class="text-center text-muted py-4">
                      <i class="fas fa-user-slash fa-2x mb-2"></i><br>No customers found.
                    </td>
                  </tr>
                <?php endif; ?>
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