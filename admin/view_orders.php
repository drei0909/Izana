<?php
session_start();

require_once('../classes/database.php');
require_once (__DIR__. "/../classes/config.php");


if (!isset($_SESSION['admin_ID'])) {
    header("Location: admin_L.php");
    exit();
}

$activePage = 'view_orders';

$db = new Database();
$adminName = htmlspecialchars($_SESSION['admin_FN'] ?? 'Admin');

// ✅ Get search input
$search = $_GET['search'] ?? '';

$limit = 20; // rows per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// ✅ Fetch orders with pagination, including both 'online' and 'walk-in' orders
$orders = $db->getOrders($search, $limit, $offset);

// ✅ Count total orders for pagination
$total_orders = $db->countOrders($search);
$total_pages = ceil($total_orders / $limit);
if ($total_pages < 1) $total_pages = 1;
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

        <!-- Search Form -->
        <form method="GET" class="row g-3 mb-4 justify-content-center">
          <div class="col-md-6">
            <div class="input-group">
              <input type="text" name="search" class="form-control" placeholder="Search by order ID, customer name, or email" value="<?= htmlspecialchars($search) ?>">
              <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i> Search</button>
              <a href="view_orders.php" class="btn btn-secondary">Reset</a>
            </div>
          </div>
        </form>

        <div class="card p-3">
          <div class="table-responsive">
            <table class="table table-hover table-bordered align-middle">
              <thead>
                <tr>
                  <th>Order ID</th>
                  <th>Customer</th>
                  <th>Total Amount</th>
                  <th>Receipt</th>
                  <th>Order Date</th>
                  <th>Order Channel</th> <!-- New column for Order Channel -->
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
                          <a href="uploads/receipts/<?= htmlspecialchars($order['receipt']) ?>" target="_blank" class="receipt-link">View</a>
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

        <!-- Pagination -->
        <nav class="mt-4">
          <ul class="pagination justify-content-center pagination-lg">
            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
              <a class="page-link" href="?search=<?= urlencode($search) ?>&page=<?= max(1, $page - 1) ?>">Prev</a>
            </li>
            <?php
            $start = max(1, $page - 2);
            $end = min($total_pages, $page + 2);

            if ($end - $start < 4) {
              if ($start == 1) {
                  $end = min(5, $total_pages);
              } elseif ($end == $total_pages) {
                  $start = max(1, $total_pages - 4);
              }
            }

            if ($start > 1) {
              echo '<li class="page-item"><a class="page-link" href="?search=' . urlencode($search) . '&page=1">1</a></li>';
              if ($start > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }

            for ($i = $start; $i <= $end; $i++) {
              $active = ($i == $page) ? 'active' : '';
              echo "<li class='page-item $active'><a class='page-link' href='?search=" . urlencode($search) . "&page=$i'>$i</a></li>";
            }

            if ($end < $total_pages) {
              if ($end < $total_pages - 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
              echo "<li class='page-item'><a class='page-link' href='?search=" . urlencode($search) . "&page=$total_pages'>$total_pages</a></li>";
            }
            ?>
            <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
              <a class="page-link" href="?search=<?= urlencode($search) ?>&page=<?= min($total_pages, $page + 1) ?>">Next</a>
            </li>
          </ul>
        </nav>
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