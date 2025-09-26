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

$orders = $db->getOrders();
?>

<?php include ('templates/header.php'); ?>

<div class="wrapper">

<?php include ('templates/sidebar.php'); ?>

  <!-- Main content -->
  <div class="main">
    <div class="admin-header d-flex justify-content-between align-items-center">
      <div class="d-flex align-items-center gap-3">
        <span class="toggle-btn d-lg-none text-dark" onclick="toggleSidebar()"><i class="fas fa-bars"></i></span>
        <h5 class="mb-0">Welcome, <?= $adminName ?></h5>
      </div>
      <span class="text-muted"><i class="fas fa-user-shield me-1"></i>Admin Panel</span>
    </div>

    <div class="dashboard-content">
      <div class="container-fluid">
        <h4 class="section-title"><i class="fas fa-shopping-cart me-2"></i>Order List</h4>

        <div class="card p-3">
          <div class="table-responsive">
            <table id="productTable" class ="table table-hover table-bordered align-middle">
              <thead>
                <tr>
                  <th>Order ID</th>
                  <th>Customer</th>
                  <th>Total Amount</th>
                  <th>Receipt</th>
                  <th>Order Date</th>
                  <th>Order Channel</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($orders)): ?>
                  <?php foreach ($orders as $order): ?>
                    <tr>
                      <td><?= htmlspecialchars($order['order_id']) ?></td>
                      <td><?= htmlspecialchars($order['customer_name']) ?></td>
                      <td>₱<?= number_format($order['total_amount'], 2) ?></td>
                      <td>
                        <?php if (!empty($order['receipt'])): ?>
                          <a href="../uploads/receipts/<?= htmlspecialchars($order['receipt']) ?>" target="_blank" class="receipt-link">View</a>
                        <?php else: ?>
                          <span class="text-muted">No receipt</span>
                        <?php endif; ?>
                      </td>
                      <td><?= date("F j, Y h:i A", strtotime($order['order_date'])) ?></td>
                      <td><?= htmlspecialchars($order['order_channel']) ?></td> <!-- Display order channel -->
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                      <i class="fas fa-box-open fa-2x mb-2"></i><br>No orders found.
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