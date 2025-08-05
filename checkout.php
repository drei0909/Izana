<?php
session_start();
require_once('./classes/database.php');
$db = new Database();

if (!isset($_SESSION['customer_ID'])) {
    header("Location: login.php");
    exit();
}

$cart = $_SESSION['cart'] ?? [];
$customer_name = $_SESSION['customer_FN'] ?? 'Guest';
$customerID = $_SESSION['customer_ID'];

// Fetch active promos
$promoQuery = $db->conn->query("SELECT * FROM promotion WHERE is_active = 1 AND expiry_date >= CURDATE()");
$promoCodes = $promoQuery->fetchAll(PDO::FETCH_ASSOC);

// Fetch current reward points
$currentPoints = 0;
$stmt = $db->conn->prepare("
    SELECT SUM(points_earned - points_redeemed) AS total_points
    FROM reward_transaction
    WHERE customer_id = ?
");
$stmt->execute([$customerID]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$currentPoints = $result['total_points'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Checkout | Izana Coffee</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Quicksand:wght@400;600&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    body {
      background: url('uploads/bgg.jpg') no-repeat center center fixed;
      background-size: cover;
      font-family: 'Quicksand', sans-serif;
      color: #212529;
    }

    .checkout-container {
      max-width: 800px;
      margin: 70px auto;
      padding: 30px;
      background: rgba(255, 255, 255, 0.95);
      border-radius: 15px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    }

    .checkout-title {
      font-family: 'Playfair Display', serif;
      font-size: 2.2rem;
      font-weight: 600;
      margin-bottom: 25px;
      color: #6b3e1d;
      text-align: center;
    }

    .table thead {
      background-color: #ffe9d1;
      font-weight: 600;
    }

    .btn-place-order {
      background-color: #b07542;
      color: white;
      font-weight: 600;
      width: 100%;
    }

    .btn-place-order:hover {
      background-color: #8a5c33;
    }

    .btn-back {
      background-color: transparent;
      color: #6b3e1d;
      border: none;
      font-weight: 600;
    }

    .btn-back:hover {
      text-decoration: underline;
    }

    .payment-label {
      font-weight: 600;
      margin-top: 15px;
    }

    table td, table th {
      font-size: 0.95rem;
    }
  </style>
</head>
<body>

<!-- Back button -->
<div class="position-absolute top-0 start-0 m-3">
  <a href="menu.php" class="btn btn-back">&larr; Back to Menu</a>
</div>

<div class="checkout-container">
  <h2 class="checkout-title">☕ Checkout Summary</h2>
  <p class="mb-3"><strong>Customer:</strong> <?= htmlspecialchars($customer_name); ?></p>

  <?php if (empty($cart)): ?>
    <div class="alert alert-warning text-center">
      Your cart is empty. Please go back to the <a href="menu.php" class="alert-link">menu</a>.
    </div>
  <?php else: ?>
    <form action="place_order.php" method="post" enctype="multipart/form-data">
      <table class="table table-bordered align-middle">
        <thead>
          <tr>
            <th>Drink</th>
            <th>Price (₱)</th>
            <th>Qty</th>
            <th>Subtotal (₱)</th>
          </tr>
        </thead>
        <tbody>
          <?php $total = 0; foreach ($cart as $item): 
            $subtotal = $item['price'] * $item['quantity'];
            $total += $subtotal;
          ?>
            <tr>
              <td><?= htmlspecialchars($item['name']); ?></td>
              <td><?= number_format($item['price'], 2); ?></td>
              <td><?= $item['quantity']; ?></td>
              <td><?= number_format($subtotal, 2); ?></td>
            </tr>
          <?php endforeach; ?>
          <tr>
            <td colspan="3" class="text-end fw-bold">Total:</td>
            <td class="fw-bold text-danger" id="totalDisplay">₱<?= number_format($total, 2); ?></td>
          </tr>
        </tbody>
      </table>

      <!-- Order Type -->
      <div class="mb-3">
        <label for="order_type" class="payment-label">Order Type:</label>
        <select class="form-select" name="order_type" id="order_type" required>
          <option value="">-- Choose --</option>
          <option value="Dine-in">Dine-in</option>
          <option value="Take-out">Take-out</option>
        </select>
      </div>

      <!-- Payment Method -->
      <div class="mb-3">
        <label for="payment_method" class="payment-label">Payment Method:</label>
        <select class="form-select" name="payment_method" id="payment_method" required>
          <option value="">-- Choose --</option>
          <option value="Cash">Cash</option>
          <option value="GCash">GCash</option>
        </select>
      </div>

      <!-- GCash Receipt -->
      <div class="mb-3" id="gcash_upload" style="display: none;">
        <label class="form-label">Upload GCash Receipt:</label>
        <input type="file" class="form-control" name="gcash_receipt" id="gcash_receipt" accept=".jpg,.jpeg,.png" />
      </div>

      <!-- Promo Code -->
      <div class="mb-3">
        <label class="form-label">Available Promos:</label>
        <select name="promo_code" id="promo_code" class="form-select">
          <option value="">-- Select Promo Code --</option>
          <?php foreach ($promoCodes as $promo): ?>
            <option 
              value="<?= htmlspecialchars($promo['promo_code']); ?>"
              data-type="<?= $promo['discount_type']; ?>"
              data-value="<?= $promo['discount_value']; ?>"
              data-min="<?= $promo['minimum_order_amount']; ?>"
            >
              <?= $promo['promo_code'] . ' - ' . $promo['description']; ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Reward Points -->
      <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" name="redeem_points" id="redeem_points" value="1" <?= ($currentPoints >= 1 ? '' : 'disabled'); ?>>
        <label class="form-check-label" for="redeem_points">
          Redeem 5 Points for ₱50 Off (You have <?= $currentPoints; ?> points)
        </label>
      </div>

      <button type="submit" class="btn btn-place-order">Place Order</button>
    </form>
  <?php endif; ?>
</div>






<script>
document.getElementById('payment_method').addEventListener('change', function () {
  const uploadDiv = document.getElementById('gcash_upload');
  const receiptInput = document.getElementById('gcash_receipt');
  if (this.value === 'GCash') {
    uploadDiv.style.display = 'block';
    receiptInput.required = true;
  } else {
    uploadDiv.style.display = 'none';
    receiptInput.required = false;
  }
});

// Get necessary DOM elements
const promoSelect = document.getElementById('promo_code');
const redeemCheckbox = document.getElementById('redeem_points');
const totalDisplay = document.getElementById('totalDisplay');
const originalTotal = <?= $total ?>;
const customerPoints = <?= $currentPoints ?>;
const redeemAmount = 50;

// Create discount notice
const redeemNotice = document.createElement('p');
redeemNotice.textContent = "✔ Redeeming 5 points for ₱50 discount applied!";
redeemNotice.style.color = "green";
redeemNotice.style.display = "none";
totalDisplay.parentElement.appendChild(redeemNotice);

// Total calculator function
function updateTotal() {
  let total = originalTotal;
  redeemNotice.style.display = "none";

  // Handle promo
  const selected = promoSelect.options[promoSelect.selectedIndex];
  const discountType = selected.getAttribute('data-type');
  const discountValue = parseFloat(selected.getAttribute('data-value'));
  const minOrder = parseFloat(selected.getAttribute('data-min'));

  if (promoSelect.value && originalTotal >= minOrder) {
    if (discountType === 'percent') {
      total -= originalTotal * (discountValue / 100);
    } else if (discountType === 'fixed') {
      total -= discountValue;
    }
  }

  // Handle redeem checkbox
  if (redeemCheckbox.checked) {
    if (customerPoints >= 5) {
      total -= redeemAmount;
      redeemNotice.style.display = "block";
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Not Enough Points',
        text: 'You need at least 5 points to redeem ₱50 off.',
        confirmButtonColor: '#b07542'
      });
      redeemCheckbox.checked = false;
    }
  }

  // Apply to DOM
  total = Math.max(0, total);
  totalDisplay.textContent = `₱${total.toFixed(2)}`;
}

// Events
promoSelect.addEventListener('change', updateTotal);
redeemCheckbox.addEventListener('change', updateTotal);

// Validate GCash receipt before submit
document.querySelector('form').addEventListener('submit', function (e) {
  const paymentMethod = document.getElementById('payment_method').value;
  const receiptInput = document.getElementById('gcash_receipt');
  if (paymentMethod === 'GCash' && (!receiptInput.files || receiptInput.files.length === 0)) {
    e.preventDefault();
    Swal.fire({
      icon: 'warning',
      title: 'Receipt Required',
      text: 'Please upload your GCash receipt before placing your order.',
      confirmButtonColor: '#b07542'
    });
  }

  // Final validation for redeem on submit
  if (redeemCheckbox.checked && customerPoints < 5) {
    e.preventDefault();
    Swal.fire({
      icon: 'error',
      title: 'Not Enough Points',
      text: 'You need at least 5 points to redeem ₱50 off.',
      confirmButtonColor: '#b07542'
    });
  }
});
</script>




<script>
document.getElementById('payment_method').addEventListener('change', function () {
  const uploadDiv = document.getElementById('gcash_upload');
  const receiptInput = document.getElementById('gcash_receipt');
  if (this.value === 'GCash') {
    uploadDiv.style.display = 'block';
    receiptInput.required = true;
  } else {
    uploadDiv.style.display = 'none';
    receiptInput.required = false;
  }
});

// Dynamic promo code effect
const promoSelect = document.getElementById('promo_code');
const totalDisplay = document.getElementById('totalDisplay');
const originalTotal = <?= $total ?>;

promoSelect.addEventListener('change', function () {
  const selected = this.options[this.selectedIndex];
  const discountType = selected.getAttribute('data-type');
  const discountValue = parseFloat(selected.getAttribute('data-value'));
  const minOrder = parseFloat(selected.getAttribute('data-min'));

  let newTotal = originalTotal;

  if (originalTotal >= minOrder) {
    if (discountType === 'percent') {
      newTotal -= originalTotal * (discountValue / 100);
    } else if (discountType === 'fixed') {
      newTotal -= discountValue;
    }
  }

  totalDisplay.textContent = `₱${newTotal.toFixed(2)}`;
});

// Validate receipt upload on submit
document.querySelector('form').addEventListener('submit', function (e) {
  const paymentMethod = document.getElementById('payment_method').value;
  const receiptInput = document.getElementById('gcash_receipt');
  if (paymentMethod === 'GCash' && (!receiptInput.files || receiptInput.files.length === 0)) {
    e.preventDefault();
    Swal.fire({
      icon: 'warning',
      title: 'Receipt Required',
      text: 'Please upload your GCash receipt before placing your order.',
      confirmButtonColor: '#b07542'
    });
  }
});
</script>

<!-- SweetAlert messages -->
<?php if (isset($_SESSION['order_success'])): ?>
<script>
Swal.fire({
  icon: 'success',
  title: 'Order Placed!',
  text: 'Thank you for your order. We’ll prepare it shortly.',
  confirmButtonColor: '#b07542'
});
</script>
<?php unset($_SESSION['order_success']); endif; ?>

<?php if (isset($_SESSION['error'])): ?>
<script>
Swal.fire({
  icon: 'error',
  title: 'Oops!',
  text: '<?= addslashes($_SESSION["error"]); ?>',
  confirmButtonColor: '#b07542'
});
</script>
<?php unset($_SESSION['error']); endif; ?>

<?php if (isset($_SESSION['promo_applied_successfully'])): ?>
<script>
Swal.fire({
  icon: 'info',
  title: 'Promo Applied!',
  text: 'You’ve successfully claimed a promo!',
  confirmButtonColor: '#b07542'
});
</script>
<?php unset($_SESSION['promo_applied_successfully']); endif; ?>

</body>
</html>
