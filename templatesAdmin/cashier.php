<?php
session_start();
if (!isset($_SESSION['admin_ID'])) {
    header("Location: admin_L.php");
    exit();
}

require_once('./classes/database.php');
include_once __DIR__. "/../classes/config.php";
$db = new Database();

$adminName = htmlspecialchars($_SESSION['admin_FN'] ?? 'Admin');

// Pagination for Active Orders
$limit = 20;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$keyword = trim((string)($_GET['search'] ?? ''));

try {
    // Fetch active orders from the `order` table
    $orders = $db->getCashierOrders($keyword, $limit, $offset);
    $total_rows = $db->countCashierOrders($keyword);

    $total_pages = $limit > 0 ? (int)ceil($total_rows / $limit) : 1;

} catch (PDOException $e) {
    die("Error fetching orders: " . $e->getMessage());
}
?>

<?php include ('templatesAdmin/header.php'); ?>

<div class="wrapper">

<?php include ('templatesAdmin/sidebar.php'); ?>

  <div class="main">
    <div class="admin-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">Welcome, <?= $adminName ?></h5>
    </div>

    <div class="content">
      <h4 class="section-title"><i class="fas fa-inbox me-2"></i>Active Online Orders</h4>

      <form method="GET" class="row g-3 mb-4 justify-content-center">
        <div class="col-md-6">
          <div class="input-group">
            <input type="text" name="search" class="form-control" placeholder="Search orders..." value="<?= htmlspecialchars($keyword) ?>">
            <button class="btn btn-dark" type="submit"><i class="fas fa-search"></i></button>
            <a href="cashier.php" class="btn btn-secondary">Reset</a>
          </div>
        </div>
      </form>

      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
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
                      <a href="<?= htmlspecialchars('uploads/receipts/' . $row['receipt']) ?>" target="_blank" class="btn btn-sm btn-primary btn-small">View</a>
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

      <?php if ($total_pages > 1): ?>
        <nav>
          <ul class="pagination justify-content-center pagination-lg">
            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
              <a class="page-link" href="?search=<?= urlencode($keyword) ?>&page=<?= max(1, $page - 1) ?>">Prev</a>
            </li>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
              <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                <a class="page-link" href="?search=<?= urlencode($keyword) ?>&page=<?= $i ?>"><?= $i ?></a>
              </li>
            <?php endfor; ?>
            <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
              <a class="page-link" href="?search=<?= urlencode($keyword) ?>&page=<?= min($total_pages, $page + 1) ?>">Next</a>
            </li>
          </ul>
        </nav>
      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>
