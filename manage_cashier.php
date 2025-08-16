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
        
        // 🔍 Check if username already exists in cashier
        $checkSql = "SELECT * FROM cashier WHERE username = ?";
        $checkStmt = $db->conn->prepare($checkSql);
        $checkStmt->execute([$username]);

        if ($checkStmt->rowCount() > 0) {
            $message = '<div class="alert alert-danger">⚠️ Username already exists. Choose another one.</div>';
        } else {
            // ✅ Insert new cashier
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
<title>Manage Cashier | Izana Coffee</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
body {
    background: url('uploads/bgg.jpg') no-repeat center center fixed;
    background-size: cover;
    font-family: 'Quicksand', sans-serif;
    min-height: 100vh;
}
.sidebar {
    width: 250px;
    height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    background-color: #343a40;
    padding: 20px;
}
.sidebar h4 {
    color: #fff;
    margin-bottom: 30px;
}
.sidebar .nav-link {
    color: #fff;
    margin-bottom: 5px;
}
.sidebar .nav-link.active,
.sidebar .nav-link:hover {
    background-color: #6c757d;
}
.main-content {
    margin-left: 250px;
    padding: 40px 20px;
}
.card-container {
    max-width: 700px;
    margin: 0 auto;
    background-color: rgba(255,255,255,0.95);
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}
h3 {
    font-weight: 600;
    margin-bottom: 25px;
    text-align: center;
}
.btn-back {
    margin-bottom: 20px;
}
</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar d-flex flex-column">
    <h4><i class="fas fa-coffee me-2"></i>Izana Admin</h4>
    <ul class="nav nav-pills flex-column">
      <li><a href="admin.php" class="nav-link"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
      <li><a href="view_customers.php" class="nav-link"><i class="fas fa-users me-2"></i>View Customers</a></li>
      <li><a href="view_orders.php" class="nav-link"><i class="fas fa-receipt me-2"></i>View Orders</a></li>
      <li><a href="manage_products.php" class="nav-link"><i class="fas fa-mug-hot me-2"></i>Manage Products</a></li>
      <li><a href="manage_cashier.php" class="nav-link active"><i class="fas fa-cash-register me-2"></i>Manage Cashier</a></li>
      <li><a href="sales_report.php" class="nav-link"><i class="fas fa-chart-line me-2"></i>Sales Report</a></li>
      <li><a href="edit_profile.php" class="nav-link"><i class="fas fa-user-edit me-2"></i>Edit Profile</a></li>
      <li><a href="admin_L.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
    </ul>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="card-container">
        <h3><i class="fas fa-cash-register me-2"></i>Register Cashier</h3>
        <?= $message ?>
        <form method="POST" class="row g-3 mt-3">
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
                <button type="submit" name="register_cashier" class="btn btn-primary w-100">
                    <i class="fas fa-plus me-1"></i> Register Cashier
                </button>
            </div>
        </form>
    </div>
</div>

</body>
</html>
