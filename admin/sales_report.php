<?php
session_start();
require_once('../classes/database.php');
require_once (__DIR__. "/../classes/config.php");
$db = new Database();

if (!isset($_SESSION['admin_ID'])) {
    header("Location: admin_L.php");
    exit();
}

$active_page = 'sales_report';

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

<?php include ('templates/header.php'); ?>

<div class="wrapper">
  
<?php include ('templates/sidebar.php'); ?>

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