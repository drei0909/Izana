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

// ✅ Pagination setup
$limit = 20; // customers per page (adjust if needed)
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// ✅ Fetch customers with pagination

$customers = $db->searchCustomers($search, $limit, $offset);

// ✅ Count total customers for pagination
$total_customer = $db->countCustomers($search);
$total_pages = ceil($total_customer / $limit);

// ✅ Always at least 1 page
if ($total_pages < 1) {
    $total_pages = 1;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Customer List | Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
  <style>
    body {
      background: url('uploads/bgg.jpg') no-repeat center center fixed;
      background-size: cover;
      color: #fff;
    }
    .container {
      background: rgba(0, 0, 0, 0.7);
      padding: 30px;
      border-radius: 15px;
      box-shadow: 0 0 20px rgba(0,0,0,0.5);
    }
    h2 {
      text-align: center;
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
<body>

<div class="d-flex">
  <!-- Sidebar -->
  <div class="sidebar d-flex flex-column p-3 text-white" style="width: 250px;">
    <h4 class="text-white mb-4">Izana Admin</h4>
    <ul class="nav nav-pills flex-column">
       <li><a href="admin.php" class="nav-link active">Dashboard</a></li>
       <li><a href="view_customers.php" class="nav-link">View Customers</a></li>
       <li><a href="view_orders.php" class="nav-link">View Orders</a></li>
       <li><a href="manage_products.php" class="nav-link">Manage Products</a></li>
       <li><a href="cashier.php" class="nav-link">Cashier</a></li>
       <li><a href="manage_cashier.php" class="nav-link">Manage Cashier</a></li>
       <li><a href="sales_report.php" class="nav-link">Sales Report</a></li>
       <li><a href="edit_profile.php" class="nav-link">Edit Profile</a></li>
       <li><a href="admin_L.php" class="nav-link text-danger">Logout</a></li>
    </ul>
  </div>

  <!-- Main Content -->
  <div class="container mt-5">
    <h2 class="mb-4"><i class="fas fa-users"></i> Customer List</h2>

    <!-- Search Bar -->
    <form method="GET" class="row g-3 mb-4 justify-content-center">
      <div class="col-md-6">
        <div class="input-group">
          <input type="text" name="search" class="form-control" placeholder="Search by name or email" value="<?= htmlspecialchars($search) ?>">
          <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i> Search</button>
          <a href="view_customers.php" class="btn btn-secondary">Reset</a>
        </div>
      </div>
    </form>

    <div class="table-responsive">
      <table class="table table-dark table-bordered table-hover align-middle">
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

    <!-- ✅ Pornhub-style Pagination -->
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
