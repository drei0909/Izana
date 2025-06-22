<?php
session_start();
require_once('./classes/database.php');
$db = new Database();

if (!isset($_SESSION['admin_ID'])) {
    header("Location: admin_L.php");
    exit();
}

$start = $_GET['start'] ?? null;
$end   = $_GET['end'] ?? null;
$period = $_GET['period'] ?? 'daily';

$sales = $db->getSalesReport($start, $end);
$chartData = $db->getSalesChartData($start, $end, $period);

$totalSales = 0;
foreach ($chartData as $d) $totalSales += $d['total'];

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
    h2, h4 {
      text-align: center;
    }
    .toggle-icons {
      font-size: 1.5rem;
      cursor: pointer;
    }
    .toggle-icons.active {
      color: #b07542;
    }
    .btn-back {
      position: absolute;
      top: 20px;
      left: 20px;
    }
  </style>
</head>
<body>
  <a href="admin.php" class="btn btn-warning btn-back"><i class="fas fa-arrow-left"></i> Back</a>

  <div class="container mt-5">
    <h2 class="mb-4"><i class="fas fa-chart-bar"></i> Sales Report</h2>
    <form method="GET" class="row g-3 mb-4 align-items-end justify-content-center">
      <div class="col-md-3">
        <label>From:</label>
        <input type="date" name="start" value="<?=htmlspecialchars($start)?>" class="form-control">
      </div>
      <div class="col-md-3">
        <label>To:</label>
        <input type="date" name="end" value="<?=htmlspecialchars($end)?>" class="form-control">
      </div>
      <input type="hidden" name="period" id="periodInput" value="<?=$period?>">
      <div class="col-md-3 d-flex gap-2">
        <i id="dailyIcon" class="toggle-icons fas fa-calendar-day <?= $period=='daily'?'active':''?>" title="Daily"></i>
        <i id="weeklyIcon" class="toggle-icons fas fa-calendar-week <?= $period=='weekly'?'active':''?>" title="Weekly"></i>
      </div>
      <div class="col-md-3 d-flex gap-2">
        <button class="btn btn-primary me-2">Filter</button>
        <a href="sales_report.php" class="btn btn-secondary">Reset</a>
      </div>
    </form>

    <div class="table-responsive mb-4">
      <table class="table table-dark table-bordered table-hover">
        <thead><tr>
          <th>Order ID</th><th>Customer</th><th>Order Type</th><th>Payment Method</th>
          <th>Payment (₱)</th><th>Date</th>
        </tr></thead>
        <tbody>
          <?php if ($sales): foreach ($sales as $r): ?>
            <tr>
              <td><?=$r['order_id']?></td>
              <td><?=htmlspecialchars($r['customer_FN'].' '.$r['customer_LN'])?></td>
              <td><?=htmlspecialchars($r['order_type'])?></td>
              <td><?=htmlspecialchars($r['payment_method'] ?? 'N/A')?></td>
              <td>₱<?=number_format($r['payment_amount'],2)?></td>
              <td><?=date('M d, Y H:i A', strtotime($r['order_date']))?></td>
            </tr>
          <?php endforeach; else: ?>
            <tr><td colspan="6" class="text-center">No sales found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <h4>Total Sales: <span class="text-success">₱<?=number_format($totalSales,2)?></span></h4>

    <div class="mt-4">
      <canvas id="salesChart"></canvas>
      <button id="exportBtn" class="btn btn-outline-success mt-2"><i class="fas fa-file-pdf"></i> Export PDF</button>
    </div>
  </div>

<script>
document.getElementById('dailyIcon').onclick = () => { periodSwitch('daily'); };
document.getElementById('weeklyIcon').onclick = () => { periodSwitch('weekly'); };
function periodSwitch(p) {
  document.getElementById('periodInput').value = p;
  document.querySelector('form').submit();
}

const ctx = document.getElementById('salesChart').getContext('2d');
const chart = new Chart(ctx, {
  type: 'bar',
  data: { labels: <?=json_encode($labels)?>, datasets: [{
    label: 'Sales (₱)', data: <?=json_encode($data)?>,
    backgroundColor:'#b07542', borderColor:'#8a5c33', borderWidth:1
  }]},
  options:{scales:{y:{beginAtZero:true,ticks:{callback:v=>'₱'+v.toLocaleString()}}}}
});

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
