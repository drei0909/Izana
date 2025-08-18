<?php
session_start();
if (!isset($_SESSION['admin_ID'])) {
    header("Location: admin_L.php");
    exit();
}

require_once('./classes/database.php');
$db = new Database();

$adminName = htmlspecialchars($_SESSION['admin_FN'] ?? 'Admin');

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_cashier'])) {
    $fname = trim($_POST['fname']);
    $lname = trim($_POST['lname']);
    $username = trim($_POST['username']);
    $passwordPlain = $_POST['password'];
    $password = password_hash($passwordPlain, PASSWORD_DEFAULT);

    if (!empty($fname) && !empty($lname) && !empty($username) && !empty($passwordPlain)) {
        $checkSql = "SELECT * FROM cashier WHERE username = ?";
        $checkStmt = $db->conn->prepare($checkSql);
        $checkStmt->execute([$username]);

        if ($checkStmt->rowCount() > 0) {
            $message = '<div class="alert alert-danger">⚠️ Username already exists. Choose another one.</div>';
        } else {
            $sql = "INSERT INTO cashier (cashier_FN, cashier_LN, username, password) VALUES (?, ?, ?, ?)";
            $stmt = $db->conn->prepare($sql);
            if ($stmt->execute([$fname, $lname, $username, $password])) {
                $message = '<div class="alert alert-success">✅ Cashier registered successfully!</div>';
            } else {
                $message = '<div class="alert alert-danger">❌ Error registering cashier.</div>';
            }
        }
    } else {
        $message = '<div class="alert alert-warning">⚠️ Please fill in all fields.</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Cashier | Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
body {
    background: url('uploads/bgg.jpg') no-repeat center center fixed;
    background-size: cover;
    font-family: 'Quicksand', sans-serif;
}
.container-bg {
    background: rgba(0, 0, 0, 0.7);
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 0 20px rgba(0,0,0,0.5);
    color: #fff;
    margin-top: 50px;
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
</style>
</head>
<body>

<div class="d-flex">
  <!-- Sidebar -->
  <div class="sidebar d-flex flex-column p-3 text-white" style="width: 250px;">
    <h4 class="text-white mb-4">Izana Admin</h4>
    <ul class="nav nav-pills flex-column">
      <li><a href="admin.php" class="nav-link">Dashboard</a></li>
      <li><a href="view_customers.php" class="nav-link">View Customers</a></li>
      <li><a href="view_orders.php" class="nav-link">View Orders</a></li>
      <li><a href="manage_products.php" class="nav-link">Manage Products</a></li>
      <li><a href="cashier.php" class="nav-link">Cashier</a></li>
      <li><a href="manage_cashier.php" class="nav-link active">Manage Cashier</a></li>
      <li><a href="sales_report.php" class="nav-link">Sales Report</a></li>
      <li><a href="edit_profile.php" class="nav-link">Edit Profile</a></li>
      <li><a href="admin_L.php" class="nav-link text-danger">Logout</a></li>
    </ul>
  </div>

  <!-- Main Content -->
  <div class="container container-bg flex-grow-1">
      <h2 class="text-center mb-4">Register Cashier</h2>
      <?= $message ?>
      <form method="POST" class="row g-3">
          <div class="col-md-6">
              <label class="form-label">First Name</label>
              <input type="text" name="fname" class="form-control" required>
          </div>
          <div class="col-md-6">
              <label class="form-label">Last Name</label>
              <input type="text" name="lname" class="form-control" required>
          </div>
          <div class="col-md-6">
              <label class="form-label">Username</label>
              <input type="text" name="username" class="form-control" required>
          </div>
          <div class="col-md-6">
              <label class="form-label">Password</label>
              <input type="password" name="password" class="form-control" required>
          </div>
          <div class="col-12">
              <button type="submit" name="register_cashier" class="btn btn-success w-100">Register Cashier</button>
          </div>
      </form>
  </div>
</div>

</body>
</html>
