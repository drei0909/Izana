<?php
require_once('./classes/database.php');
session_start();

if (!isset($_SESSION['admin_ID'])) {
    header("Location: admin_L.php");
    exit();
}

$db = new database();
$customers = $db->getAllCustomers();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>View Customers</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
  <style>
    body {
      background: url('uploads/bgg.jpg') no-repeat center center fixed;
      background-size: cover;
      min-height: 100vh;
      backdrop-filter: blur(3px);
    }
    .overlay-container {
      background: rgba(255, 255, 255, 0.95);
      padding: 30px;
      margin-top: 40px;
      border-radius: 8px;
      box-shadow: 0 0 15px rgba(0,0,0,0.1);
    }
    .back-btn {
      position: absolute;
      top: 20px;
      left: 20px;
    }
    h2 i {
      color: #b07542;
    }
  </style>
</head>
<body>
<div class="container position-relative">
  <!-- Back Button -->
  <a href="admin.php" class="btn btn-outline-secondary back-btn"><i class="fas fa-arrow-left"></i> Back</a>

  <div class="overlay-container">
    <h2 class="text-center mb-4"><i class="fas fa-users"></i> Customer List</h2>

    <div class="table-responsive">
      <table class="table table-striped table-hover align-middle">
        <thead class="table-dark">
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
                <td><?= date("F j, Y h:i A", strtotime($customer['created_at'])) ?></td>
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
</body>
</html>
