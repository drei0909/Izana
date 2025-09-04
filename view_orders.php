<?php
require_once('./classes/database.php');
session_start();

if (!isset($_SESSION['admin_ID'])) {
    header("Location: admin_L.php");
    exit();
}

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
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>View Orders | Izana Coffee</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <style>
    body {
      font-family: 'Quicksand', sans-serif;
      background: #e0e0e0;
      color: #2b2b2b;
      margin: 0;
      height: 100vh;
      overflow: hidden;
    }
    .wrapper {
      display: flex;
      height: 100vh;
      overflow: hidden;
    }
    .sidebar {
      width: 250px;
      background: #1c1c1c;
      color: #fff;
      flex-shrink: 0;
      overflow-y: auto;
    }
    .sidebar .nav-link {
      color: #bdbdbd;
      font-weight: 500;
      margin-bottom: 10px;
      padding: 10px 15px;
      border-radius: 12px;
      transition: all 0.3s ease;
    }
    .sidebar .nav-link.active,
    .sidebar .nav-link:hover {
      background-color: #6f4e37;
      color: #fff;
      transform: translateX(6px);
    }
    .main {
      flex-grow: 1;
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }
    .admin-header {
      background: #f4f4f4;
      padding: 15px 25px;
      border-bottom: 1px solid #d6d6d6;
      box-shadow: 0 2px 6px rgba(0,0,0,0.08);
    }
    .dashboard-content {
      flex-grow: 1;
      overflow-y: auto;
      padding: 20px;
    }
    .section-title {
      border-left: 6px solid #6f4e37;
      padding-left: 12px;
      margin: 30px 0 20px;
      font-weight: 700;
      color: #333;
      text-transform: uppercase;
      letter-spacing: 1px;
    }
    .card {
      border: none;
      border-radius: 15px;
      background: #f4f4f4;
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    .table th {
      background: #6f4e37;
      color: #fff;
    }
    .pagination .page-item .page-link {
      border-radius: 10px;
      margin: 0 3px;
      color: #1c1c1c;
      border: none;
      font-weight: 600;
    }
    .pagination .page-item.active .page-link {
      background-color: #6f4e37;
      color: #fff;
    }
    .pagination .page-item .page-link:hover {
      background-color: #333;
      color: #fff;
    }
    @media (max-width: 992px) {
      .sidebar {
        position: fixed;
        height: 100%;
        z-index: 1000;
        transform: translateX(-100%);
        transition: transform 0.3s ease;
      }
      .sidebar.show {
        transform: translateX(0);
      }
      .toggle-btn {
        display: inline-block;
        cursor: pointer;
        font-size: 1.5rem;
      }
    }
  </style>
</head>
<body>
<div class="wrapper">
  <!-- Sidebar -->
  <div class="sidebar p-3" id="sidebar">
    <h4 class="text-white mb-4 text-center"><i class="fas fa-mug-hot me-2"></i>Izana Admin</h4>
    <ul class="nav nav-pills flex-column">
      <li><a href="admin.php" class="nav-link"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
      <li><a href="view_customers.php" class="nav-link"><i class="fas fa-users me-2"></i>View Customers</a></li>
      <li><a href="view_orders.php" class="nav-link active"><i class="fas fa-receipt me-2"></i>View Orders</a></li>
      <li><a href="manage_products.php" class="nav-link"><i class="fas fa-mug-hot me-2"></i>Manage Products</a></li>
      <li><a href="cashier.php" class="nav-link"><i class="fas fa-cash-register me-2"></i>Online Cashier</a></li>
      <li><a href="manage_cashier.php" class="nav-link"><i class="fas fa-users-cog me-2"></i>POS</a></li>
      <li><a href="sales_report.php" class="nav-link"><i class="fas fa-chart-line me-2"></i>Sales Report</a></li>
      <li><a href="edit_profile.php" class="nav-link"><i class="fas fa-user-edit me-2"></i>Edit Profile</a></li>
      <li><a href="Logout_A.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
    </ul>
  </div>

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
