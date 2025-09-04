<?php
session_start();
require_once('./classes/database.php');
$db = new Database();

if (!isset($_SESSION['admin_ID'])) {
    header("Location: admin_L.php");
    exit();
}

$adminName = htmlspecialchars($_SESSION['admin_FN'] ?? 'Admin');
$orders = $db->getAllOrder();
// Get today's date in the format 'Y-m-d'
$today = date('Y-m-d');

// Initialize sales amounts to 0
$walkinSales = 0;
$onlineSales = 0;
$totalSales = 0;

// Fetch sales data for today
$stmtSales = $db->conn->prepare("
    SELECT order_channel, SUM(total_amount) AS total_sales
    FROM `order`
    WHERE DATE(order_date) = CURDATE()
    GROUP BY order_channel
");
$stmtSales->execute();
$sales = $stmtSales->fetchAll(PDO::FETCH_ASSOC);

foreach ($sales as $sale) {
    if ($sale['order_channel'] == 'walk-in') {
        $walkinSales = $sale['total_sales'];
    } elseif ($sale['order_channel'] == 'online') {
        $onlineSales = $sale['total_sales'];
    }
}

$totalSales = $walkinSales + $onlineSales;

// Save Sales to History
if (isset($_POST['save_sales'])) {
    // Save today's sales to the history
    $db->saveSalesToHistory($walkinSales, $onlineSales, $totalSales);

    // Store the success message in the session
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Sales saved successfully!'];
    header("Location: sales_report.php");
    exit();
}

// Reset Sales Data Logic (set to 0 on the page only)
if (isset($_POST['reset_sales'])) {


    // Store the reset sales message in the session
    $_SESSION['flash'] = ['type' => 'info', 'message' => 'Sales data reset successfully!'];

    // Reload the page after reset to apply changes
    header("Location: sales_report.php");
    exit();
}


// Delete Order Logic
if (isset($_POST['delete_order'])) {
    $orderId = $_POST['order_id'];
    $db->deleteOrder($orderId);
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Order deleted successfully!'];
    header("Location: sales_report.php");
    exit();
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Report | Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <!-- SweetAlert2 -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

    <style>
        body {
            font-family: 'Quicksand', sans-serif;
            background: #f8f8f8;
            color: #333;
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
            flex-shrink: 0;
            background: #1c1c1c;
            color: #fff;
            box-shadow: 3px 0 12px rgba(0,0,0,0.25);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            overflow-y: auto;
        }
        .main {
            margin-left: 250px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            height: 100vh;
            overflow: hidden;
        }
        .content {
            flex-grow: 1;
            overflow-y: auto;
            padding: 20px;
        }

        .sidebar .nav-link {
            color: #bdbdbd;
            font-weight: 500;
            margin-bottom: 10px;
            padding: 10px 15px;
            border-radius: 12px;
            transition: all 0.3s ease;
        }
        .sidebar .nav-link.active, .sidebar .nav-link:hover {
            background-color: #6f4e37;
            color: #fff;
            transform: translateX(6px);
        }

        .admin-header {
            background: #f4f4f4;
            padding: 15px 25px;
            border-bottom: 1px solid #d6d6d6;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
            flex-shrink: 0;
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

        .table {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .table thead {
            background: #6f4e37;
            color: #fff;
        }
        .table tbody tr:hover {
            background: #f8f1ed;
        }

        .pagination .page-item .page-link {
            border-radius: 50%;
            margin: 0 3px;
            color: #1c1c1c;
            background-color: #f4f4f4;
            border: none;
            font-weight: bold;
        }
        .pagination .page-item.active .page-link {
            background-color: #6f4e37;
            color: #fff;
        }
        .pagination .page-item .page-link:hover {
            background-color: #333;
            color: #fff;
        }
    </style>
</head>
<body>
<div class="wrapper">
  <!-- Sidebar -->
  <div class="sidebar p-3">
    <h4 class="text-white mb-4 text-center"><i class="fas fa-mug-hot me-2"></i>Izana Admin</h4>
    <ul class="nav nav-pills flex-column">
      <li><a href="admin.php" class="nav-link"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
      <li><a href="view_customers.php" class="nav-link"><i class="fas fa-users me-2"></i>View Customers</a></li>
      <li><a href="view_orders.php" class="nav-link"><i class="fas fa-receipt me-2"></i>View Orders</a></li>
      <li><a href="manage_products.php" class="nav-link"><i class="fas fa-mug-hot me-2"></i>Manage Products</a></li>
      <li><a href="cashier.php" class="nav-link"><i class="fas fa-cash-register me-2"></i>Online Cashier</a></li>
      <li><a href="manage_cashier.php" class="nav-link"><i class="fas fa-users-cog me-2"></i>POS</a></li>
      <li><a href="sales_report.php" class="nav-link active"><i class="fas fa-chart-line me-2"></i>Sales Report</a></li>
      <li><a href="salesHistory.php" class="nav-link"><i class="fas fa-history me-2"></i>Sales History</a></li>
      <li><a href="edit_profile.php" class="nav-link"><i class="fas fa-user-edit me-2"></i>Edit Profile</a></li>
      <li><a href="Logout_A.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
    </ul>
  </div>

  <!-- Main -->
  <div class="main">
    <!-- Header -->
    <div class="admin-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">Welcome, <?= $adminName ?></h5>
      <span class="text-muted"><i class="fas fa-user-shield me-1"></i>Admin Panel</span>
    </div>

    <!-- Content -->
    <div class="content">
      <div class="container-fluid">

        <h4 class="section-title"><i class="fas fa-chart-bar me-2"></i>Sales Report</h4>

        <!-- Sales Data Display -->
        <div class="card mb-4">
          <div class="card-body">
            <h5>Today's Sales</h5>
            <p><strong>Total Sales</strong>: ₱<?= number_format($totalSales, 2) ?></p>
            <form method="post">
              <button type="submit" name="save_sales" class="btn btn-outline-primary">Save Today's Sales</button>
              <button type="submit" name="reset_sales" class="btn btn-outline-danger">Reset Sales</button>
            </form>
          </div>
        </div>

        <!-- Orders Table -->
        <div class="card mb-4">
          <div class="card-body">
            <h5>Today's Orders</h5>
            <table class="table table-bordered table-hover">
              <thead>
                <tr>
                  <th>Order ID</th>
                  <th>Customer</th>
                  <th>Order Channel</th>
                  <th>Total Amount</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($orders as $order): ?>
                  <tr>
                    <td><?= $order['order_id'] ?></td>
                    <td><?= $order['customer_FN'] ?> <?= $order['customer_LN'] ?></td>
                    <td><?= $order['order_channel'] ?></td>
                    <td>₱<?= number_format($order['total_amount'], 2) ?></td>
                    <td>
                      <form method="post" style="display:inline;">
                        <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                        <button type="submit" name="delete_order" class="btn btn-outline-danger">Delete</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<script>
    <?php if(isset($_SESSION['flash'])): ?>
        const flash = <?php echo json_encode($_SESSION['flash']); ?>;
        Swal.fire({
            icon: flash.type,
            title: flash.message,
            showConfirmButton: true
        });
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>
</script>

</body>
</html>
