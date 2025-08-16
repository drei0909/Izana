<?php
session_start();
require_once('./classes/database.php');
$db = new Database();

if (!isset($_SESSION['admin_ID'])) {
    header("Location: admin_L.php");
    exit();
}

$keyword = $_GET['keyword'] ?? null;

// Fetch sales data
$sales = $db->getSalesReport(null, null, $keyword);
$chartData = $db->getSalesChartData(null, null, 'daily');

$totalSales = array_sum(array_column($chartData, 'total'));
$labels = array_column($chartData, 'label');
$data = array_column($chartData, 'total');
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Sales Report | Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
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
h2 { text-align: center; }
.btn-back { position: absolute; top: 20px; left: 20px; }

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
    .admin-header {
      background-color: rgba(255,255,255,0.9);
      padding: 15px 20px;
      border-bottom: 1px solid #dee2e6;
    }
    .dashboard-content {
      padding: 25px;
      background-color: rgba(255, 255, 255, 0.95);
      min-height: 100vh;
    }
    .card {
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
</style>
</head>
<body>
<div class="d-flex">
  <!-- Sidebar -->
  <div class="sidebar d-flex flex-column p-3 text-white" style="width: 250px;">
    <h4 class="text-white mb-4"><i class="fas fa-coffee me-2"></i>Izana Admin</h4>
    <ul class="nav nav-pills flex-column">
      <li><a href="admin.php" class="nav-link active"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
      <li><a href="view_customers.php" class="nav-link"><i class="fas fa-users me-2"></i>View Customers</a></li>
      <li><a href="view_orders.php" class="nav-link"><i class="fas fa-receipt  me-2"></i>View Orders</a></li>
      <li><a href="manage_products.php" class="nav-link"><i class="fas fa-mug-hot me-2"></i>Manage Products</a></li>
       <li><a href="cashier.php" class="nav-link"><i class="fas fa-cash-register me-2"></i>Cashier</a></li>
      <li><a href="sales_report.php" class="nav-link"><i class="fas fa-chart-line me-2"></i>Sales Report</a></li>
      <li><a href="edit_profile.php" class="nav-link"><i class="fas fa-user-edit me-2"></i>Edit Profile</a></li>
      <li><a href="admin_L.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
    </ul>
  </div>
<div class="container mt-5">
<h2 class="mb-4"><i class="fas fa-chart-bar"></i> Sales Report</h2>

<!-- Search Bar -->
<form method="GET" class="d-flex justify-content-center mb-4">
    <input type="text" name="keyword" value="<?= htmlspecialchars($keyword) ?>" 
           class="form-control w-50" placeholder="Search by Order ID or Customer Name">
    <button class="btn btn-primary ms-2"><i class="fas fa-search"></i> Search</button>
    <a href="sales_report.php" class="btn btn-secondary ms-2"><i class="fas fa-undo"></i> Reset</a>
    <button type="button" class="btn btn-success ms-2" id="showTotalBtn"><i class="fas fa-coins"></i> Show Total Sales</button>
</form>

<!-- Sales Table -->
<div class="table-responsive mb-4">
<table class="table table-dark table-bordered table-hover">
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
  <td><?= htmlspecialchars($r['order_type']) ?></td>
  <td><?= htmlspecialchars($r['payment_method'] ?? 'N/A') ?></td>
  <td>₱<?= number_format($r['payment_amount'], 2) ?></td>
  <td><?= date('M d, Y H:i A', strtotime($r['order_date'])) ?></td>
</tr>
<?php endforeach; else: ?>
<tr><td colspan="6" class="text-center">No sales found.</td></tr>
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

// Show Total Sales on Button Click
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
<script src="https://kit.fontawesome.com/a076d05399.js"></script>
</body>
</html>
