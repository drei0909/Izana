<?php
session_start();
if (!isset($_SESSION['customer_ID'])) {
    header("Location: login.php");
    exit();
}

$cart = $_SESSION['cart'] ?? [];
$customer_name = $_SESSION['customer_FN'] ?? 'Guest';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Checkout | Izana Coffee</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Quicksand:wght@400;600&display=swap" rel="stylesheet">
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
      font-family: 'Quicksand', sans-serif;
    }

    .btn-place-order:hover {
      background-color: #8a5c33;
    }

    .btn-back {
      background-color: transparent;
      color: #ffe9d1;
      border: none;
      font-weight: 600;
      font-family: 'Quicksand', sans-serif;
      font-size: 1rem;
    }

    .btn-back:hover {
      text-decoration: underline;
    }

    .payment-label {
      font-weight: 600;
      margin-top: 15px;
      font-size: 1rem;
    }

    select.form-select, .form-label, .form-control {
      font-family: 'Quicksand', sans-serif;
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

  <p class="mb-3"><strong>Customer:</strong> <?php echo htmlspecialchars($customer_name); ?></p>

  <?php if (empty($cart)): ?>
    <div class="alert alert-warning text-center">
      Your cart is empty. Please go back to the <a href="menu.php" class="alert-link">menu</a>.
    </div>
  <?php else: ?>
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
        <?php
        $total = 0;
        foreach ($cart as $item):
          $subtotal = $item['price'] * $item['quantity'];
          $total += $subtotal;
        ?>
          <tr>
            <td><?php echo htmlspecialchars($item['name']); ?></td>
            <td><?php echo number_format($item['price'], 2); ?></td>
            <td><?php echo $item['quantity']; ?></td>
            <td><?php echo number_format($subtotal, 2); ?></td>
          </tr>
        <?php endforeach; ?>
        <tr>
          <td colspan="3" class="text-end fw-bold">Total:</td>
          <td class="fw-bold text-danger">₱<?php echo number_format($total, 2); ?></td>
        </tr>
      </tbody>
    </table>

    <form action="place_order.php" method="post" enctype="multipart/form-data">
      
      <!-- Order Type -->
      <div class="mb-3">
        <label for="order_type" class="payment-label">Select Order Type:</label>
        <select class="form-select" name="order_type" id="order_type" required>
          <option value="">-- Choose Order Type --</option>
          <option value="Dine-in">Dine-in</option>
          <option value="Take-out">Take-out</option>
        </select>
      </div>

      <!-- Payment Method -->
      <div class="mb-3">
        <label for="payment_method" class="payment-label">Select Payment Method:</label>
        <select class="form-select" name="payment_method" id="payment_method" required>
          <option value="">-- Choose Payment --</option>
          <option value="Cash">Cash</option>
          <option value="GCash">GCash</option>
        </select>
      </div>

      <!-- GCash Receipt Upload -->
      <div class="mb-4" id="gcash_upload" style="display: none;">
        <label for="gcash_receipt" class="form-label">Upload GCash Receipt:</label>
        <input type="file" class="form-control" name="gcash_receipt" id="gcash_receipt" accept=".jpg,.jpeg,.png" />
      </div>



<div class="mb-3">
  <label for="promo_code" class="form-label">Promo Code</label>
  <input type="text" name="promo_code" class="form-control" placeholder="Enter code (e.g. IZANA10)">
</div>


      <button type="submit" class="btn btn-place-order">Place Order</button>
    </form>
  <?php endif; ?>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  // Toggle GCash receipt field
  document.getElementById('payment_method').addEventListener('change', function () {
    const upload = document.getElementById('gcash_upload');
    const receipt = document.getElementById('gcash_receipt');
    if (this.value === 'GCash') {
      upload.style.display = 'block';
      receipt.required = true;
    } else {
      upload.style.display = 'none';
      receipt.required = false;
    }
  });

  // Block submission if GCash selected and no file uploaded
  document.querySelector('form').addEventListener('submit', function (e) {
    const payment = document.getElementById('payment_method').value;
    const receipt = document.getElementById('gcash_receipt');
    if (payment === 'GCash' && (!receipt.files || receipt.files.length === 0)) {
      e.preventDefault();
      Swal.fire({
        icon: 'warning',
        title: 'Receipt Required',
        text: 'Please upload your GCash receipt before placing your order.',
        confirmButtonColor: '#b07542'
      });
    }
  });

  // SweetAlert if order was successful
  <?php if (isset($_SESSION['order_success']) && $_SESSION['order_success']): ?>
    Swal.fire({
      icon: 'success',
      title: 'Order Placed!',
      text: 'Thank you for your order. We’ll prepare it shortly.',
      confirmButtonColor: '#b07542'
    });
    <?php unset($_SESSION['order_success']); ?>
  <?php endif; ?>
  
  <?php if (isset($_SESSION['promo_applied_successfully']) && $_SESSION['promo_applied_successfully']): ?>
    Swal.fire({
      icon: 'info',
      title: 'Promo Applied!',
      text: 'You’ve successfully claimed 10% off with WELCOME10!',
      confirmButtonColor: '#b07542'
    });
    <?php unset($_SESSION['promo_applied_successfully']); ?>
    <?php endif; ?>
</script>

</body>
</html>
