<?php
session_start();
require_once('./classes/database.php');
$db = new Database();

if (!isset($_SESSION['admin_ID'])) {
    header("Location: admin.php");
    exit();
}

$admin_ID = $_SESSION['admin_ID'];
$admin = $db->getAdminById($admin_ID);
$adminName = htmlspecialchars($_SESSION['admin_FN'] ?? 'Admin');

$success = $error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firstName = $_POST['admin_FN'];
    $lastName  = $_POST['admin_LN'];
    $password  = $_POST['new_password'];
    $confirm   = $_POST['confirm_password'];

    if (!empty($password)) {
        if ($password !== $confirm) {
            $error = "Passwords do not match.";
        } else {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $db->updateAdminPassword($admin_ID, $hashed);
            $success = "Password updated successfully.";
        }
    }

    if (!$error) {
        $updated = $db->updateAdminProfile($admin_ID, $firstName, $lastName);
        if ($updated) {
            $_SESSION['admin_FN'] = $firstName;
            $success = "Profile updated successfully.";
            $admin = $db->getAdminById($admin_ID);
        } else {
            $error = "Failed to update profile.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Profile | Izana Coffee</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
      max-width: 600px;
      margin: auto;
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
      <li><a href="view_orders.php" class="nav-link"><i class="fas fa-receipt me-2"></i>View Orders</a></li>
      <li><a href="manage_products.php" class="nav-link"><i class="fas fa-mug-hot me-2"></i>Manage Products</a></li>
      <li><a href="cashier.php" class="nav-link"><i class="fas fa-cash-register me-2"></i>Online Cashier</a></li>
      <li><a href="manage_cashier.php" class="nav-link"><i class="fas fa-users-cog me-2"></i>POS</a></li>
      <li><a href="sales_report.php" class="nav-link"><i class="fas fa-chart-line me-2"></i>Sales Report</a></li>
       <li><a href="salesHistory.php" class="nav-link"><i class="fas fa-history me-2"></i>Sales History</a></li>
      <li><a href="edit_profile.php" class="nav-link active"><i class="fas fa-user-edit me-2"></i>Edit Profile</a></li>
      <li><a href="Logout_A.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
    </ul>
  </div>

  <!-- Main -->
  <div class="main">
    <!-- Header -->
    <div class="admin-header d-flex justify-content-between align-items-center">
      <div class="d-flex align-items-center gap-3">
        <span class="toggle-btn d-lg-none text-dark" onclick="toggleSidebar()"><i class="fas fa-bars"></i></span>
        <h5 class="mb-0">Welcome, <?= $adminName ?></h5>
      </div>
      <span class="text-muted"><i class="fas fa-user-shield me-1"></i>Admin Panel</span>
    </div>

    <!-- Content -->
    <div class="dashboard-content">
      <div class="container-fluid">
        <h4 class="section-title"><i class="fas fa-user-cog me-2"></i>Edit Profile</h4>
        <div class="card p-4">
          <form method="POST">
            <div class="mb-3">
              <label for="admin_FN" class="form-label">First Name</label>
              <input type="text" name="admin_FN" class="form-control" value="<?= htmlspecialchars($admin['admin_FN']) ?>" required>
            </div>
            <div class="mb-3">
              <label for="admin_LN" class="form-label">Last Name</label>
              <input type="text" name="admin_LN" class="form-control" value="<?= htmlspecialchars($admin['admin_LN']) ?>" required>
            </div>
            <hr>
            <h6>Change Password <small class="text-muted">(optional)</small></h6>
            <div class="mb-3">
              <label for="new_password" class="form-label">New Password</label>
              <input type="password" name="new_password" class="form-control">
            </div>
            <div class="mb-3">
              <label for="confirm_password" class="form-label">Confirm Password</label>
              <input type="password" name="confirm_password" class="form-control">
            </div>
            <button type="submit" class="btn btn-success w-100"><i class="fas fa-save"></i> Update Profile</button>
          </form>
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

<?php if ($success): ?>
<script>
Swal.fire({
  icon: 'success',
  title: 'Success',
  text: '<?= $success ?>',
  confirmButtonColor: '#198754'
});
</script>
<?php elseif ($error): ?>
<script>
Swal.fire({
  icon: 'error',
  title: 'Error',
  text: '<?= $error ?>',
  confirmButtonColor: '#dc3545'
});
</script>
<?php endif; ?>
</body>
</html>
