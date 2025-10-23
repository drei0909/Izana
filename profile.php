<?php
session_start();
require_once('./classes/database.php');
$db = new Database();

if (!isset($_SESSION['customer_ID'])) {
  header("Location: login.php");
  exit();
}

$customerID = $_SESSION['customer_ID'];
$customer = $db->getCustomerByID($customerID);

$stmt = $db->conn->prepare("
  SELECT o.order_id, o.total_amount, o.receipt, o.ref_no, o.created_at, o.status,
         p.payment_method, p.payment_amount, p.payment_status
  FROM order_online o
  LEFT JOIN payment p ON o.order_id = p.order_id
  WHERE o.customer_id = ?
  ORDER BY o.created_at DESC
");
$stmt->execute([$customerID]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

function escape($s) {
  return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Account | Izana Coffee</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap / FontAwesome / SweetAlert -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <!-- Font -->
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <style>
  :root {
    --accent: #b07542;
    --accent-dark: #8a5c33;
    --gray-dark: #1e1e1e;
    --gray-mid: #2b2b2b;
    --gray-light: #444;
    --text-light: #f5f5f5;
  }

  * { font-family: 'Montserrat', sans-serif; }

  body {
    margin: 0;
    color: var(--text-light);
    background: url('uploads/bgg.jpg') no-repeat center center fixed;
    background-size: cover;
    padding-top: 100px;
  }

  body::before {
    content: '';
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.6);
    z-index: -1;
  }

  /* Navbar */
  .navbar-custom {
    background: var(--gray-dark);
    border-bottom: 1px solid var(--gray-light);
  }
  .navbar-brand {
    color: var(--accent) !important;
    font-weight: 800;
    font-size: 1.8rem;
  }
  .navbar-custom .nav-link {
    color: var(--text-light) !important;
    font-weight: 500;
  }
  .navbar-custom .nav-link:hover {
    color: var(--accent) !important;
  }

  .dropdown-menu {
    background: var(--gray-mid);
    border: 1px solid var(--gray-light);
  }
  .dropdown-item {
    color: var(--text-light);
    font-weight: 500;
  }
  .dropdown-item:hover {
    background: var(--accent-dark);
    color: #fff;
  }

  /* Container */
  .container-profile {
    max-width: 1000px;
    margin: 0 auto 40px auto;
    background: var(--gray-mid);
    border: 1px solid var(--gray-light);
    border-radius: 18px;
    padding: 40px;
    box-shadow: 0 12px 30px rgba(0,0,0,0.4);
  }

  h2, h4, h5 { font-weight: 700; color: var(--accent); }

  .profile-header {
    border-bottom: 1px solid var(--gray-light);
    padding-bottom: 15px;
    margin-bottom: 25px;
  }

  .card, .order-card {
    background: var(--gray-dark);
    border: 1px solid var(--gray-light);
    border-radius: 14px;
    color: var(--text-light);
    padding: 20px;
  }

  .order-card { margin-bottom: 20px; }

  .btn-warning {
    background-color: var(--accent);
    border: none;
    color: #fff;
    font-weight: 600;
    transition: all .2s ease;
  }
  .btn-warning:hover {
    background-color: var(--accent-dark);
    transform: translateY(-1px);
  }

  .modal-content {
    background: var(--gray-mid);
    border: 1px solid var(--gray-light);
    border-radius: 14px;
    color: var(--text-light);
  }

  @media (max-width: 992px) {
    .container-profile { padding: 25px; }
    .navbar-brand img { height: 50px; }
    .profile-header h2 { font-size: 1.6rem; }
  }

  @media (max-width: 576px) {
    .container-profile { padding: 20px; }
    .order-card { padding: 15px; }
    .btn-sm { width: 100%; margin-top: 5px; }
    .order-header { font-size: 1rem; }
  }
  </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark navbar-custom fixed-top">
  <div class="container-fluid" style="max-width:1400px;">
    <a class="navbar-brand" href="#">
      <img src="uploads/izana_logo.png" alt="IZANA" style="height:65px;">
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse justify-content-end" id="navMenu">
      <ul class="navbar-nav align-items-center">
        <li class="nav-item"><a class="nav-link" href="menu.php"><i class="fa-solid fa-mug-hot me-1"></i> Menu</a></li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
            <i class="fa-solid fa-user-circle me-1"></i> <?= escape($customer['customer_FN']) ?>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="profile.php"><i class="fa-solid fa-id-card me-2"></i> My Account</a></li>
            <li><a class="dropdown-item" href="#orders"><i class="fa-solid fa-receipt me-2"></i> My Orders</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i> Logout</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>

<!-- Profile Section -->
<div class="container-profile mt-4">
  <div class="profile-header">
    <h2><?= escape($customer['customer_FN'].' '.$customer['customer_LN']) ?></h2>
    <p class="text-secondary">@<?= escape($customer['customer_username']) ?></p>
    <p class="small">Member since <?= date('F j, Y', strtotime($customer['created_at'])) ?></p>
  </div>

  <div class="card mb-4">
    <div class="card-body">
      <h5 class="card-title mb-3">Account Information</h5>
      <p><strong>Email:</strong> <?= escape($customer['customer_email']) ?></p>
      <p><strong>Contact Number:</strong> <?= escape($customer['customer_contact'] ?? 'Not provided') ?></p>

      <button class="btn btn-sm btn-warning mt-2" data-bs-toggle="modal" data-bs-target="#editModal">
        <i class="fas fa-pen"></i> Update Information
      </button>
      <button class="btn btn-sm btn-danger mt-2 ms-2" id="deleteAccountBtn">
        <i class="fas fa-trash"></i> Delete Account
      </button>
    </div>
  </div>

  <!-- Orders Section -->
  <h4 id="orders" class="mb-3">Order History</h4>
  <?php if ($orders): ?>
    <?php foreach ($orders as $order): ?>
      <div class="order-card">
        <div class="order-header">
          Order #<?= $order['order_id'] ?> — ₱<?= number_format($order['total_amount'], 2) ?>
        </div>
        <div><strong>Reference No:</strong> <?= escape($order['ref_no']) ?></div>
        <div><strong>Date Placed:</strong> <?= date('F j, Y g:i A', strtotime($order['created_at'])) ?></div>
        <div><strong>Status:</strong>
          <?php
          echo match ($order['status']) {
              1 => '<span class="text-warning">Pending</span>',
              2 => '<span class="text-info">Processing</span>',
              3 => '<span class="text-primary">Ready for Pickup</span>',
              4 => '<span class="text-success">Completed</span>',
              default => '<span class="text-muted">Unknown</span>',
          };
          ?>
        </div>
        <div><strong>Payment:</strong>
          <?= escape($order['payment_method'] ?? 'N/A') ?> - ₱<?= number_format($order['payment_amount'] ?? 0, 2) ?>
          (<?= escape($order['payment_status'] ?? 'Pending') ?>)
        </div>
        <div><strong>Receipt:</strong>
          <?php if (!empty($order['receipt'])): ?>
            <a href="uploads/receipts/<?= urlencode($order['receipt']) ?>" target="_blank">View</a>
          <?php else: ?> N/A <?php endif; ?>
        </div>

        <div class="order-items mt-2">
          <strong>Items:</strong>
          <ul>
            <?php
            $itemStmt = $db->conn->prepare("
              SELECT oi.quantity, oi.price, p.product_name
              FROM order_item oi
              JOIN product p ON oi.product_id = p.product_id
              WHERE oi.order_id = ?
            ");
            $itemStmt->execute([$order['order_id']]);
            foreach ($itemStmt->fetchAll(PDO::FETCH_ASSOC) as $item): ?>
              <li><?= $item['quantity'] ?> × <?= escape($item['product_name']) ?> (₱<?= number_format($item['price'], 2) ?>)</li>
            <?php endforeach; ?>
          </ul>
        </div>

        <div class="text-end mt-3">
          <button class="btn btn-warning btn-sm rebuy-btn" data-order-id="<?= $order['order_id'] ?>">
            <i class="fas fa-cart-plus"></i> Reorder
          </button>
        </div>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <p>You have not placed any orders yet.</p>
  <?php endif; ?>
</div>

<!-- Update Info Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Update Information</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="updateInfoForm">
          <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control" value="<?= escape($customer['customer_username']) ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="<?= escape($customer['customer_email']) ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Contact Number</label>
            <input type="text" name="contact" class="form-control" value="<?= escape($customer['customer_contact'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">New Password (optional)</label>
            <input type="password" name="password" class="form-control" placeholder="Leave blank to keep current password">
          </div>
          <button type="submit" class="btn btn-warning w-100">Save Changes</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {

// ✅ Update Info
$('#updateInfoForm').on('submit', function(e) {
  e.preventDefault();
  $.ajax({
    url: 'functions.php',
    type: 'POST',
    dataType: 'json',
    data: {
      ref: 'update_customer_info',
      username: $('input[name="username"]').val(),
      email: $('input[name="email"]').val(),
      contact: $('input[name="contact"]').val(),
      password: $('input[name="password"]').val()
    },
    success: function(r) {
      Swal.fire({
        icon: r.status === 'success' ? 'success' : 'error',
        title: r.status === 'success' ? 'Updated!' : 'Error',
        text: r.message,
        confirmButtonColor: '#b07542'
      }).then(() => {
        if (r.status === 'success') location.reload();
      });
    },
    error: function() {
      Swal.fire('Error', 'Unable to connect to the server.', 'error');
    }
  });
});

// ✅ Delete Account
$('#deleteAccountBtn').on('click', function() {
  Swal.fire({
    title: 'Are you sure?',
    text: 'This will permanently delete your account.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#b07542',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Yes, delete it!'
  }).then((result) => {
    if (result.isConfirmed) {
      $.ajax({
        url: 'functions.php',
        type: 'POST',
        dataType: 'json',
        data: { ref: 'delete_account' },
        success: function(response) {
          if (response.status === 'success') {
            Swal.fire({
              icon: 'success',
              title: 'Deleted!',
              text: response.message,
              confirmButtonColor: '#b07542'
            }).then(() => window.location.href = 'registration.php');
          } else {
            Swal.fire('Error', response.message, 'error');
          }
        },
        error: function() {
          Swal.fire('Error', 'Unable to connect to server.', 'error');
        }
      });
    }
  });
});

// ✅ Reorder / Rebuy
$('.rebuy-btn').on('click', function() {
  const id = $(this).data('order-id');
  $.ajax({
    url: 'functions.php',
    type: 'POST',
    dataType: 'json',
    data: { ref: 'rebuy_order', order_id: id },
    success: function(r) {
      if (r.status === 'success') {
        Swal.fire({
          icon: 'success',
          title: 'Items added to cart!',
          text: 'Redirecting to checkout...',
          showConfirmButton: false,
          timer: 1200
        });
        setTimeout(() => window.location.href = 'checkout.php', 1300);
      } else {
        Swal.fire('Error', r.message, 'error');
      }
    },
    error: function(xhr, status, error) {
      console.error('AJAX error:', error);
      Swal.fire('Error', 'Unable to connect to server.', 'error');
    }
  });
});

});
</script>
</body>
</html>
