<?php
session_start();

require_once('../classes/database.php');
require_once(__DIR__ . "/../classes/config.php");

$db = new Database();

if (!isset($_SESSION['admin_ID'])) {
    header("Location: admin_L.php");
    exit();
}

$adminName = htmlspecialchars($_SESSION['admin_FN'] ?? 'Admin');

// === Fetch POS Orders ===
$sqlPos = "SELECT pos_id AS order_id, total_amount, payment_method, created_at, 
                  'POS' AS order_channel, '' AS receipt, '' AS ref_no, 
                  'Walk-in' AS customer_name, 1 AS status
           FROM order_pos ORDER BY created_at DESC";
$stmt = $db->conn->query($sqlPos);
$posOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// === Fetch Online Orders ===
$sqlOnline = "SELECT o.order_id, 
       CONCAT(c.customer_FN, ' ', c.customer_LN) AS customer_name, 
       o.total_amount, o.receipt, o.ref_no, 
       o.order_date, o.status, 'Online' AS order_channel, 
       '' AS payment_method, '' AS created_at
FROM order_online o
JOIN customer c ON o.customer_id = c.customer_id

              ORDER BY o.order_date DESC";
$stmt2 = $db->conn->query($sqlOnline);
$onlineOrders = $stmt2->fetchAll(PDO::FETCH_ASSOC);

// Merge both
$orders = array_merge($posOrders, $onlineOrders);

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
            <table id="productTable" class="table table-hover table-bordered align-middle">
              <thead>
                <tr>
                  <th>Order ID</th>
                  <th>Customer</th>
                  <th>Total Amount</th>
                  <th>Receipt / Payment</th>
                  <th>Reference No.</th>
                  <th>Date</th>
                  <th>Status</th>
                  <th>Order Channel</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($orders)): ?>
                  <?php foreach ($orders as $order): ?>
                    <tr>
                      <td><?= htmlspecialchars($order['order_id']) ?></td>

                      <!-- Customer / POS orders -->
                      <td>
                        <?= isset($order['customer_name']) 
                            ? htmlspecialchars($order['customer_name']) 
                            : '<span class="badge bg-secondary">Walk-in</span>' ?>
                      </td>

                      <td>₱<?= number_format($order['total_amount'], 2) ?></td>

                      <!-- Receipt for online, payment_method for POS -->
                      <td>
                        <?php if ($order['order_channel'] === 'Online'): ?>
                          <?php if (!empty($order['receipt'])): ?>
                            <a href="../uploads/receipts/<?= htmlspecialchars($order['receipt']) ?>" target="_blank" class="receipt-link">View</a>
                          <?php else: ?>
                            <span class="text-muted">No receipt</span>
                          <?php endif; ?>
                        <?php else: ?>
                          <span class="badge bg-info"><?= htmlspecialchars($order['payment_method']) ?></span>
                        <?php endif; ?>
                      </td>

                      <!-- Reference number (only online) -->
                      <td>
                        <?= $order['order_channel'] === 'Online' 
                              ? htmlspecialchars($order['ref_no']) 
                              : '<span class="text-muted">N/A</span>' ?>
                      </td>

                      <!-- Date -->
                      <td>
                        <?= $order['order_channel'] === 'Online'
                              ? date("F j, Y h:i A", strtotime($order['order_date']))
                              : date("F j, Y h:i A", strtotime($order['created_at'])) ?>
                      </td>

                      <!-- Status (only online) -->
                      <td>
                        <?= $order['order_channel'] === 'Online'
                              ? ($order['status'] == 1 ? '<span class="badge bg-success">Completed</span>' : '<span class="badge bg-warning">Pending</span>')
                              : '<span class="badge bg-success">Completed</span>' ?>
                      </td>

                      <!-- Channel -->
                      <td>
                        <span class="badge <?= $order['order_channel'] === 'Online' ? 'bg-primary' : 'bg-dark' ?>">
                          <?= htmlspecialchars($order['order_channel']) ?>
                        </span>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="8" class="text-center text-muted py-4">
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

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
  $(document).ready(function(){
    $('#productTable').DataTable();
  });
  function toggleSidebar() {
    document.getElementById("sidebar").classList.toggle("show");
  }
</script>

</body>
</html>