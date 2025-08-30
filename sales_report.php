<?php
session_start();
require_once('./classes/database.php');
$db = new Database();

if (!isset($_SESSION['admin_ID'])) {
    header("Location: admin_L.php");
    exit();
}

$adminName = htmlspecialchars($_SESSION['admin_FN'] ?? 'Admin');
$keyword = $_GET['keyword'] ?? null;

// Fetch sales data
$sales = $db->getSalesReport(null, null, $keyword);
$chartData = $db->getSalesChartData(null, null, 'daily');

$totalSales = array_sum(array_column($chartData, 'total'));
$labels = array_column($chartData, 'label');
$data = array_column($chartData, 'total');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Sales Report | Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

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

        <!-- Search Bar -->
        <form method="GET" class="row g-3 mb-4 justify-content-center">
          <div class="col-md-8">
            <div class="input-group">
              <input type="text" name="keyword" value="<?= htmlspecialchars($keyword) ?>" 
                     class="form-control" placeholder="Search by Order ID or Customer Name">
              <button class="btn btn-dark" type="submit"><i class="fas fa-search"></i></button>
              <a href="sales_report.php" class="btn btn-secondary">Reset</a>
              <button type="button" class="btn btn-success ms-2" id="showTotalBtn"><i class="fas fa-coins"></i> Show Total Sales</button>
            </div>
          </div>
        </form>

        <!-- Sales Table -->
        <div class="table-responsive mb-4">
          <table class="table table-bordered table-hover align-middle">
            <thead>
              <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Order Type</th>
                <th>Payment Method</th>
                <th>Payment (₱)</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($sales): foreach ($sales as $r): ?>
              <tr>
                <td><?= htmlspecialchars($r['order_id']) ?></td>
                <td><?= htmlspecialchars($r['customer_FN'].' '.$r['customer_LN']) ?></td>
                <td><?= htmlspecialchars($r['order_channel']) ?></td>
                <td><?= htmlspecialchars($r['payment_method'] ?? 'N/A') ?></td>
                <td>₱<?= number_format($r['payment_amount'], 2) ?></td>
                <td><?= date('M d, Y H:i A', strtotime($r['order_date'])) ?></td>
              </tr>
              <?php endforeach; else: ?>
              <tr><td colspan="6" class="text-center text-muted py-4"><i class="fas fa-receipt fa-2x mb-2"></i><br>No sales found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Hidden Total Sales -->
        <div id="totalSalesContainer" class="alert alert-success text-center fw-bold" style="display:none;">
          Total Sales: ₱<?= number_format($totalSales, 2) ?>
        </div>

        <!-- Sales Chart -->
        <div class="mt-4 text-center">
          <canvas id="salesChart"></canvas>
          <button id="exportBtn" class="btn btn-outline-success mt-3"><i class="fas fa-file-pdf"></i> Export PDF</button>
        </div>

      </div>
    </div>
  </div>
</div>

<script>
const ctx = document.getElementById('salesChart').getContext('2d');
new Chart(ctx, {
  type: 'bar',
  data: {
    labels: <?= json_encode($labels) ?>,
    datasets: [{
      label: 'Sales (₱)',
      data: <?= json_encode($data) ?>,
      backgroundColor:'#b07542',
      borderColor:'#8a5c33',
      borderWidth:1
    }]
  },
  options:{
    scales:{y:{beginAtZero:true,ticks:{callback:v=>'₱'+v.toLocaleString()}}}
  }
});

// Show Total Sales
document.getElementById('showTotalBtn').addEventListener('click', () => {
  const totalDiv = document.getElementById('totalSalesContainer');
  totalDiv.style.display = 'block';
  totalDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
});

// PDF Export
document.getElementById('exportBtn').addEventListener('click', async () => {
  const chartContainer = document.getElementById('salesChart').parentNode;
  const canvas = await html2canvas(chartContainer);
  const imgData = canvas.toDataURL('image/png');
  const { jsPDF } = window.jspdf;
  const pdf = new jsPDF('landscape');
  const pageWidth = pdf.internal.pageSize.getWidth();
  const imgProps = pdf.getImageProperties(imgData);
  const imgHeight = (imgProps.height * pageWidth) / imgProps.width;
  pdf.addImage(imgData, 'PNG', 0, 10, pageWidth, imgHeight);
  pdf.save("sales-report-<?= date('Ymd') ?>.pdf");
});
</script>
</body>
</html>
