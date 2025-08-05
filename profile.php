<?php
session_start();
if (!isset($_SESSION['customer_ID'])) {
    header("Location: login.php");
    exit();
}

require_once('./classes/database.php');
$db = new Database();
$customerID = $_SESSION['customer_ID'];

$customer = $db->getCustomerByID($customerID);
$orders = $db->getCustomerOrders($customerID);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Profile | Izana Coffee</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    body {
      background-image: url('uploads/bgg.jpg');
      background-size: cover;
      background-repeat: no-repeat;
      background-attachment: fixed;
      font-family: 'Quicksand', sans-serif;
      padding-top: 70px;
    }
    .container {
      background-color: rgba(255,255,255,0.9);
      border-radius: 12px;
      padding: 30px;
    }
    .order-card {
      background: #fff;
      border: 1px solid #ddd;
      border-radius: 12px;
      padding: 20px;
      margin-bottom: 20px;
    }
    .order-header {
      font-weight: bold;
      font-size: 1.1rem;
    }
    .order-items {
      margin-left: 15px;
    }
    .dropdown-menu-scrollable {
      max-height: 300px;
      overflow-y: auto;
    }
  </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top transparent-navbar">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">Izana Coffee</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMenu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarMenu">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Orders</a>
          <ul class="dropdown-menu dropdown-menu-end dropdown-menu-scrollable p-2" style="min-width: 300px;">
  <input type="text" class="form-control mb-2" id="orderSearchInput" placeholder="Search Order #..." onkeyup="filterOrders()">

  <div id="ordersList">
    <?php if ($orders): ?>
      <?php foreach ($orders as $order): ?>
        <li>
          <a class="dropdown-item order-item" href="#order<?= $order['order_id'] ?>">
            Order #<?= $order['order_id'] ?> - ₱<?= number_format($order['total_amount'], 2) ?>
          </a>
        </li>
      <?php endforeach; ?>
    <?php else: ?>
      <li><span class="dropdown-item text-muted">No Orders</span></li>
    <?php endif; ?>
  </div>
</ul>

        <li class="nav-item"><a class="nav-link" href="menu.php">Menu</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-4">
  <h2 class="mb-4">👤 Owner Profile</h2>

  <!-- Customer Info Card -->
  <div class="card mb-4">
    <div class="card-body">
      <h5 class="card-title"><?= htmlspecialchars($customer['customer_FN'] . ' ' . $customer['customer_LN']) ?></h5>
      <p class="card-text"><strong>Username:</strong> <?= htmlspecialchars($customer['customer_username']) ?></p>
      <p class="card-text"><strong>Email:</strong> <?= htmlspecialchars($customer['customer_email']) ?></p>
      <p class="card-text"><strong>Member Since:</strong> <?= date('F j, Y', strtotime($customer['created_at'])) ?></p>
      <button class="btn btn-sm btn-warning mt-2" data-bs-toggle="modal" data-bs-target="#editModal">Edit Account Info</button>
    </div>
  </div>

  <!-- Previous Orders -->
  <h4 class="mb-3">📦 Previous Orders</h4>
  <?php if ($orders): ?>
    <?php foreach ($orders as $order): ?>
      <div class="order-card" id="order<?= $order['order_id'] ?>">
        <div class="order-header">
          Order #<?= $order['order_id'] ?> - ₱<?= number_format($order['total_amount'], 2) ?> (<?= $order['order_type'] ?>)
        </div>
        <div><strong>Date:</strong> <?= date('F j, Y', strtotime($order['order_date'])) ?></div>
        <div><strong>Payment Receipt:</strong> <?= htmlspecialchars($order['receipt']) ?: 'N/A' ?></div>
        <?php if ($order['promo_discount'] > 0): ?>
          <div><strong>Promo Discount:</strong> ₱<?= number_format($order['promo_discount'], 2) ?></div>
        <?php endif; ?>
        <?php if ($order['point_discount'] > 0): ?>
          <div><strong>Point Discount:</strong> ₱<?= number_format($order['point_discount'], 2) ?></div>
        <?php endif; ?>
        <div class="order-items mt-2">
          <strong>Items:</strong>
          <ul>
            <?php foreach ($db->getOrderItem($order['order_id']) as $item): ?>
              <li><?= $item['quantity'] ?> × <?= $item['product_name'] ?> (₱<?= number_format($item['price'], 2) ?> each)</li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <p>You have not placed any orders yet.</p>
  <?php endif; ?>
</div>

<!--Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form action="update_profile.php" method="POST">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editModalLabel">Update Account Info</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="customer_id" value="<?= $customerID ?>">

          <div class="mb-3">
            <label class="form-label">New Username <small class="text-muted">(optional)</small></label>
            <input type="text" class="form-control" name="new_username" placeholder="Leave blank to keep current">
          </div>

          <div class="mb-3">
            <label class="form-label">New Email <small class="text-muted">(optional)</small></label>
            <input type="email" class="form-control" name="new_email" placeholder="Leave blank to keep current">
          </div>

          <div class="mb-3">
            <label class="form-label">New Password <small class="text-muted">(optional)</small></label>
            <input type="password" class="form-control" name="new_password" placeholder="Leave blank to keep current">
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Save Changes</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
function filterOrders() {
  const input = document.getElementById('orderSearchInput');
  const filter = input.value.toUpperCase();
  const orders = document.querySelectorAll('.order-item');

  orders.forEach(order => {
    const txt = order.textContent || order.innerText;
    if (txt.toUpperCase().includes(filter)) {
      order.parentElement.style.display = "";
    } else {
      order.parentElement.style.display = "none";
    }
  });
}
</script>




<?php if (isset($_GET['success'])): ?>
<script>
Swal.fire({
  icon: 'success',
  title: 'Updated!',
  text: 'Your profile has been updated successfully.',
  timer: 2500,
  showConfirmButton: false
});
</script>
<?php elseif (isset($_GET['error']) && $_GET['error'] === 'duplicate'): ?>
<script>
Swal.fire({
  icon: 'warning',
  title: 'Duplicate!',
  text: 'The username or email is already taken.',
});
</script>
<?php elseif (isset($_GET['error']) && $_GET['error'] === 'email'): ?>
<script>
Swal.fire({
  icon: 'error',
  title: 'Invalid Email!',
  text: 'Please enter a valid email address.',
});
</script>
<?php elseif (isset($_GET['error']) && $_GET['error'] === 'empty'): ?>
<script>
Swal.fire({
  icon: 'warning',
  title: 'Missing Info!',
  text: 'Username and email cannot be empty.',
});
</script>
<?php elseif (isset($_GET['error'])): ?>
<script>
Swal.fire({
  icon: 'error',
  title: 'Error!',
  text: 'Something went wrong. Please try again.',
});
</script>
<?php endif; ?>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
