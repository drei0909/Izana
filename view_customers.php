<?php
require_once('./classes/database.php');
session_start();

if (!isset($_SESSION['admin_ID'])) {
    header("Location: admin_L.php");
    exit();
}

$db = new Database();
$customers = $db->getAllCustomers();
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
    .btn-back {
      position: absolute;
      top: 20px;
      left: 20px;
    }
  </style>
</head>
<body>

  <!-- Back Button -->
  <a href="admin.php" class="btn btn-warning btn-back">Back</a>

  <div class="container mt-5">
    <h2 class="mb-4"><i class="fas fa-users"></i> Customer List</h2>

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
  </div>

</body>
</html>
