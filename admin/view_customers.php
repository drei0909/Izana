<?php
session_start();

require_once('../classes/database.php');
require_once (__DIR__. "/../classes/config.php");


$active_page = 'view_customers';

if (!isset($_SESSION['admin_ID'])) {
    header("Location: admin_L.php");
    exit();
}

$active_page = 'view_customers'; 

$db = new Database();

// ✅ Get search input
$search = $_GET['search'] ?? '';

$limit = 20; // rows per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// ✅ Fetch customers with pagination and search filter
$customers = $db->getAllCustomers();

// ✅ Count total customers for pagination
$total_customers = $db->countCustomers($search);
$total_pages = ceil($total_customers / $limit);
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
        <h5 class="mb-0">Welcome, Admin</h5>
      </div>
      <span class="text-muted"><i class="fas fa-user-shield me-1"></i>Admin Panel</span>
    </div>

    <div class="dashboard-content">
      <div class="container-fluid">
        <h4 class="section-title"><i class="fas fa-users me-2"></i>Customer List</h4>

        <!-- Search Form -->
        <form method="GET" class="row g-3 mb-4 justify-content-center">
          <div class="col-md-6">
            <div class="input-group">
              <input type="text" name="search" class="form-control" placeholder="Search by name, email" value="<?= htmlspecialchars($search) ?>">
              <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i> Search</button>
              <a href="view_customers.php" class="btn btn-secondary">Reset</a>
            </div>
          </div>
        </form>

        <div class="card p-3">
          <div class="table-responsive">
            <table class="table table-hover table-bordered align-middle">
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

<script>
  function toggleSidebar() {
    document.getElementById("sidebar").classList.toggle("show");
  }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>