<?php
session_start();
if (!isset($_SESSION['admin_ID'])) {
    header("Location: admin_L.php");
    exit();
}

require_once('./classes/database.php');
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

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Cashier Panel | Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    body { font-family: 'Quicksand', sans-serif; background: #e0e0e0; color: #2b2b2b; margin: 0; height: 100vh; overflow: hidden; }
    .wrapper { display: flex; height: 100vh; overflow: hidden; }
    .sidebar { width: 250px; flex-shrink: 0; background: #1c1c1c; color: #fff; box-shadow: 3px 0 12px rgba(0,0,0,0.25); display: flex; flex-direction: column; position: fixed; top: 0; bottom: 0; left: 0; overflow-y: auto; }
    .main { margin-left: 250px; flex-grow: 1; display: flex; flex-direction: column; height: 100vh; overflow: hidden; }
    .content { flex-grow: 1; overflow-y: auto; padding: 20px; }
    .sidebar .nav-link { color: #bdbdbd; font-weight: 500; margin-bottom: 10px; padding: 10px 15px; border-radius: 12px; transition: all 0.3s ease; }
    .sidebar .nav-link.active, .sidebar .nav-link:hover { background-color: #6f4e37; color: #fff; transform: translateX(6px); }
    .admin-header { background: #f4f4f4; padding: 15px 25px; border-bottom: 1px solid #d6d6d6; box-shadow: 0 2px 6px rgba(0,0,0,0.08); flex-shrink: 0; }
    .section-title { border-left: 6px solid #6f4e37; padding-left: 12px; margin: 30px 0 20px; font-weight: 700; color: #333; text-transform: uppercase; letter-spacing: 1px; }
    .table { background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
    .table thead { background: #6f4e37; color: #fff; }
    .table tbody tr:hover { background: #f8f1ed; }
    .pagination .page-item .page-link { border-radius: 50%; margin: 0 3px; color: #1c1c1c; background-color: #f4f4f4; border: none; font-weight: bold; }
    .pagination .page-item.active .page-link { background-color: #6f4e37; color: #fff; }
    .pagination .page-item .page-link:hover { background-color: #333; color: #fff; }
    .btn-small { padding: .35rem .6rem; font-size: .85rem; border-radius: .35rem; }
  </style>
</head>
<body>
<div class="wrapper">
  <div class="sidebar p-3">
    <h4 class="text-white mb-4 text-center"><i class="fas fa-mug-hot me-2"></i>Izana Admin</h4>
    <ul class="nav nav-pills flex-column">
      <li><a href="admin.php" class="nav-link"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
      <li><a href="view_customers.php" class="nav-link"><i class="fas fa-users me-2"></i>View Customers</a></li>
      <li><a href="view_orders.php" class="nav-link"><i class="fas fa-receipt me-2"></i>View Orders</a></li>
      <li><a href="manage_products.php" class="nav-link"><i class="fas fa-mug-hot me-2"></i>Manage Products</a></li>
      <li><a href="cashier.php" class="nav-link active"><i class="fas fa-cash-register me-2"></i>Online Cashier</a></li>
      <li><a href="manage_cashier.php" class="nav-link"><i class="fas fa-users-cog me-2"></i>POS</a></li>
      <li><a href="sales_report.php" class="nav-link"><i class="fas fa-chart-line me-2"></i>Sales Report</a></li>
      <li><a href="edit_profile.php" class="nav-link"><i class="fas fa-user-edit me-2"></i>Edit Profile</a></li>
      <li><a href="Logout_A.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
    </ul>
  </div>

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
