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

try {
    // Fetch active orders from the `order` table
    $orders = $db->getCashierOrders();
    $total_rows = $db->countCashierOrders();
  } catch (PDOException $e) {
      die("Error fetching orders: " . $e->getMessage());
  }
?>

<?php include ('templates/header.php'); ?>

<div class="wrapper">

<?php include ('templates/sidebar.php'); ?>

  <div class="main">
    <div class="admin-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">Welcome, <?= $adminName ?></h5>
    </div>

    <div class="content">
      <h4 class="section-title"><i class="fas fa-inbox me-2"></i>Active Online Orders</h4>

      <div class="table-responsive">
        <table id="productTable" class="table table-bordered table-hover align-middle">
          <thead>
            <tr>
              <th>Order ID</th>
              <th>Customer</th>
              <th>Total</th>
              <th>Payment</th>
              <th>Receipt</th>
              <th>Placed At</th>
            </tr>
          </thead>
          <tbody id="order-body">
            <?php if (count($orders) > 0): ?>
              <?php foreach ($orders as $row): ?>
                <tr id="order-row-<?= (int)$row['order_id'] ?>">
                  <td><?= (int)$row['order_id'] ?></td>
                  <td><?= htmlspecialchars($row['customer_FN'] . ' ' . $row['customer_LN']) ?></td>
                  <td>₱<?= number_format($row['total_amount'], 2) ?></td>
                  <td><?= htmlspecialchars($row['payment_method'] ?? 'N/A') ?></td>
                  <td>
                    <?php if (!empty($row['receipt'])): ?>
                      <a href="<?= htmlspecialchars('../uploads/receipts/' . $row['receipt']) ?>" target="_blank" class="btn btn-sm btn-primary btn-small">View</a>
                    <?php else: ?>
                      <span class="text-muted">N/A</span>
                    <?php endif; ?>
                  </td>
                  <td><?= date("M d, Y h:i A", strtotime($row['order_date'])) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="6" class="text-center text-muted py-4">
                  <i class="fas fa-receipt fa-2x mb-2"></i><br>No active orders.
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
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