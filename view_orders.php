<?php
require_once('./classes/database.php');
session_start();

if (!isset($_SESSION['admin_ID'])) {
    header("Location: admin_L.php");
    exit();
}

$db = new Database();

// ✅ Get search input
$search = $_GET['search'] ?? '';

$limit = 20; // rows per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// ✅ Fetch orders with pagination
$orders = $db->getOrders($search, $limit, $offset);

// ✅ Count total orders for pagination
$total_orders = $db->countOrders($search);
$total_pages = ceil($total_orders / $limit);
if ($total_pages < 1) $total_pages = 1;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>View Orders</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
  <style>
    body {
      background: url('uploads/bgg.jpg  ') no-repeat center center fixed;
      background-size: cover;
      color: #fff;
    }
    .container-bg {
      background: rgba(0, 0, 0, 0.75);
      padding: 30px;
      border-radius: 15px;
      box-shadow: 0 0 20px rgba(0,0,0,0.6);
    }
    table {
      color: #fff;
    }
    .table-dark th {
      background-color: #343a40 !important;
    }
    .sidebar {
      height: 100vh;
      background-color: rgba(52, 58, 64, 0.95);
    }
    .sidebar .nav-link {
      color: #ffffff;
    }
    .sidebar .nav-link.active,
    .sidebar .nav-link:hover {
      background-color: #6c757d;
    }
    /* ✅ Pornhub-style pagination */
    .pagination .page-item .page-link {
      border-radius: 50%;
      margin: 0 3px;
      color: #fff;
      background-color: #1c1c1c;
      border: none;
      font-weight: bold;
    }
    .pagination .page-item.active .page-link {
      background-color: #ff9000; /* Pornhub orange */
      border-color: #ff9000;
    }
    .pagination .page-item .page-link:hover {
      background-color: #333;
      color: #ff9000;
    }
  </style>
</head>

<div class="d-flex">
  <!-- Sidebar -->
  <div class="sidebar d-flex flex-column p-3 text-white" style="width: 250px;">
    <h4 class="text-white mb-4"><i class="fas fa-coffee me-2"></i>Izana Admin</h4>
    <ul class="nav nav-pills flex-column">
      <li><a href="admin.php" class="nav-link active"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
      <li><a href="view_customers.php" class="nav-link"><i class="fas fa-users me-2"></i>View Customers</a></li>
      <li><a href="view_orders.php" class="nav-link"><i class="fas fa-receipt  me-2"></i>View Orders</a></li>
      <li><a href="manage_products.php" class="nav-link"><i class="fas fa-mug-hot me-2"></i>Manage Products</a></li>
      <li><a href="cashier.php" class="nav-link"><i class="fas fa-cash-register me-2"></i>Cashier</a></li>
      <li><a href="manage_cashier.php" class="nav-link"><i class="fas fa-cash-register me-2"></i>Manage Cashier</a></li>
      <li><a href="sales_report.php" class="nav-link"><i class="fas fa-chart-line me-2"></i>Sales Report</a></li>
      <li><a href="edit_profile.php" class="nav-link"><i class="fas fa-user-edit me-2"></i>Edit Profile</a></li>
      <li><a href="admin_L.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
    </ul>
  </div>

  <div class="container mt-5 container-bg">
    <h2 class="mb-4 text-center"><i class="fas fa-shopping-cart"></i> Order List</h2>

    <!-- Search Bar -->
    <form method="GET" class="row g-3 mb-4 justify-content-center">
      <div class="col-md-6">
        <div class="input-group">
          <input type="text" name="search" class="form-control" placeholder="Search by order ID, customer name, or email" value="<?= htmlspecialchars($search) ?>">
          <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i> Search</button>
          <a href="view_orders.php" class="btn btn-secondary">Reset</a>
        </div>
      </div>
    </form>

    <div class="table-responsive">
      <table class="table table-dark table-hover table-bordered align-middle">
        <thead>
          <tr>
            <th>Order ID</th>
            <th>Customer</th>
            <th>Total Amount</th>
            <th>Receipt</th>
            <th>Order Date</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($orders)): ?>
            <?php foreach ($orders as $order): ?>
              <tr>
                <td><?= htmlspecialchars($order['order_id']) ?></td>
                <td><?= htmlspecialchars($order['customer_FN'] . ' ' . $order['customer_LN']) ?></td>
                <td>₱<?= number_format($order['total_amount'], 2) ?></td>
                <td>
                  <?php if (!empty($order['receipt'])): ?>
                    <a href="uploads/receipts/<?= htmlspecialchars($order['receipt']) ?>" target="_blank" class="receipt-link">View</a>
                  <?php else: ?>
                    <span class="text-muted">No receipt</span>
                  <?php endif; ?>
                </td>
                <td><?= date("F j, Y h:i A", strtotime($order['order_date'])) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="5" class="text-center text-muted py-4">
                <i class="fas fa-box-open fa-2x mb-2"></i><br>No orders found.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- ✅ Pagination -->
    <nav>
      <ul class="pagination justify-content-center pagination-lg">
        <!-- Prev -->
        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
          <a class="page-link" href="?search=<?= urlencode($search) ?>&page=<?= max(1, $page - 1) ?>">Prev</a>
        </li>

        <?php
        // ✅ Show max 5 page numbers
        $start = max(1, $page - 2);
        $end = min($total_pages, $page + 2);

        if ($end - $start < 4) {
            if ($start == 1) {
                $end = min(5, $total_pages);
            } elseif ($end == $total_pages) {
                $start = max(1, $total_pages - 4);
            }
        }

        // First page + ellipsis
        if ($start > 1) {
            echo '<li class="page-item"><a class="page-link" href="?search=' . urlencode($search) . '&page=1">1</a></li>';
            if ($start > 2) {
                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }

        // Middle pages
        for ($i = $start; $i <= $end; $i++) {
            $active = ($i == $page) ? 'active' : '';
            echo "<li class='page-item $active'><a class='page-link' href='?search=" . urlencode($search) . "&page=$i'>$i</a></li>";
        }

        // Last page + ellipsis
        if ($end < $total_pages) {
            if ($end < $total_pages - 1) {
                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
            echo "<li class='page-item'><a class='page-link' href='?search=" . urlencode($search) . "&page=$total_pages'>$total_pages</a></li>";
        }
        ?>

        <!-- Next -->
        <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
          <a class="page-link" href="?search=<?= urlencode($search) ?>&page=<?= min($total_pages, $page + 1) ?>">Next</a>
        </li>
      </ul>
    </nav>
  </div>
</div>
</body>
</html>
