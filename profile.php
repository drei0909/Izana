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

function escape($s) {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Profile | Izana Coffee</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
:root {
  --accent: #b07542;
  --accent-dark: #8a5c33;
  --gray-dark: #1e1e1e;
  --gray-mid: #2b2b2b;
  --gray-light: #444;
  --text-light: #f5f5f5;
  --text-muted: #ccc;
}

body {
  margin: 0;
  font-family: 'Quicksand', sans-serif;
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
  font-family: 'Playfair Display', serif;
  font-weight: 800;
  font-size: 2rem;
}
.navbar-custom .nav-link {
  color: var(--text-light) !important;
  font-weight: 600;
}
.navbar-custom .nav-link:hover {
  color: #fff !important;
}

/* Profile Container */
.container-profile {
  max-width: 1100px;
  margin: 0 auto 40px auto;
  background: var(--gray-mid);
  border: 1px solid var(--gray-light);
  border-radius: 18px;
  padding: 35px;
  box-shadow: 0 12px 30px rgba(0,0,0,0.4);
}

/* Titles */
h2, h4 {
  font-family: 'Playfair Display', serif;
  color: var(--accent);
  font-weight: 900;
}

/* Info Card */
.card {
  background: var(--gray-dark);
  border: 1px solid var(--gray-light);
  border-radius: 14px;
  color: var(--text-light);
  box-shadow: 0 6px 18px rgba(0,0,0,0.4);
}
.card-title {
  font-weight: 700;
  font-size: 1.3rem;
  color: #fff;
}
.btn-warning {
  background-color: var(--accent);
  border: none;
  color: #fff;
  font-weight: 600;
}
.btn-warning:hover {
  background-color: var(--accent-dark);
}

/* Order Cards */
.order-card {
  background: var(--gray-dark);
  border: 1px solid var(--gray-light);
  border-radius: 14px;
  padding: 20px;
  margin-bottom: 20px;
  color: var(--text-light);
  box-shadow: 0 6px 18px rgba(0,0,0,0.3);
}
.order-header {
  font-weight: bold;
  font-size: 1.1rem;
  color: var(--accent);
}

/* Modal */
.modal-content {
  background: var(--gray-mid);
  border: 1px solid var(--gray-light);
  border-radius: 14px;
  color: var(--text-light);
}
.modal-header {
  background: var(--accent-dark);
  color: #fff;
  border-top-left-radius: 14px;
  border-top-right-radius: 14px;
}
.modal-footer {
  border-top: 1px solid var(--gray-light);
  background: var(--gray-mid);
}
</style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark navbar-custom fixed-top">
  <div class="container-fluid" style="max-width:1400px;">
    <a class="navbar-brand" href="#">Izana Coffee</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-end" id="navMenu">
      <ul class="navbar-nav align-items-center">
        <li class="nav-item"><a class="nav-link" href="menu.php">Menu</a></li>
        <li class="nav-item"><a class="nav-link" href="profile.php">Profile</a></li>
        <li class="nav-item"><a class="nav-link text-danger" href="logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container-profile mt-4">
  <h2 class="mb-4">👤 Profile</h2>

  <!-- Customer Info Card -->
  <div class="card mb-4">
    <div class="card-body">
      <h5 class="card-title"><?= escape($customer['customer_FN'].' '.$customer['customer_LN']) ?></h5>
      <p class="card-text"><strong>Username:</strong> <?= escape($customer['customer_username']) ?></p>
      <p class="card-text"><strong>Email:</strong> <?= escape($customer['customer_email']) ?></p>
      <p class="card-text"><strong>Member Since:</strong> <?= date('F j, Y', strtotime($customer['created_at'])) ?></p>
      <button class="btn btn-sm btn-warning mt-2" data-bs-toggle="modal" data-bs-target="#editModal">Edit Account Info</button>
    </div>
  </div>

  <!-- Previous Orders -->
  <h4 class="mb-3"> Previous Orders</h4>
  <?php if ($orders): ?>
    <?php foreach ($orders as $order): ?>
      <div class="order-card" id="order<?= $order['order_id'] ?>">
        <div class="order-header">
          Order #<?= $order['order_id'] ?> - ₱<?= number_format($order['total_amount'], 2) ?> (<?= escape($order['order_type']) ?>)
        </div>
        <div><strong>Date:</strong> <?= date('F j, Y', strtotime($order['order_date'])) ?></div>
        <div><strong>Payment Receipt:</strong>
          <?php if (!empty($order['receipt'])): ?>
            <a href="uploads/<?= urlencode($order['receipt']) ?>" target="_blank">View Receipt</a>
          <?php else: ?>
            N/A
          <?php endif; ?>
        </div>
        <div class="order-items mt-2">
          <strong>Items:</strong>
          <ul>
            <?php foreach ($db->getOrderItem($order['order_id']) as $item): ?>
              <li><?= escape($item['quantity']) ?> × <?= escape($item['product_name']) ?> (₱<?= number_format($item['price'], 2) ?> each)</li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <p>You have not placed any orders yet.</p>
  <?php endif; ?>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form action="update_profile.php" method="POST">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Update Account Info</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="customer_id" value="<?= $customerID ?>">
          <div class="mb-3">
            <label class="form-label">New Username (Optional) <small class="text-muted">(optional)</small></label>
            <input type="text" class="form-control" name="new_username">
          </div>
          <div class="mb-3">
            <label class="form-label">New Email (Optional) <small class="text-muted">(optional)</small></label>
            <input type="email" class="form-control" name="new_email">
          </div>
          <div class="mb-3">
            <label class="form-label">New Password (Optional) <small class="text-muted">(optional)</small></label>
            <input type="password" class="form-control" name="new_password">
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-warning">Save Changes</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Alerts -->
<?php if (isset($_GET['success'])): ?>
<script>
Swal.fire({ icon: 'success', title: 'Updated!', text: 'Your profile has been updated successfully.', timer: 2000, showConfirmButton: false });
</script>
<?php elseif (isset($_GET['error']) && $_GET['error']==='duplicate'): ?>
<script>
Swal.fire({ icon: 'warning', title: 'Duplicate!', text: 'The username or email is already taken.' });
</script>
<?php elseif (isset($_GET['error']) && $_GET['error']==='email'): ?>
<script>
Swal.fire({ icon: 'error', title: 'Invalid Email!', text: 'Please enter a valid email address.' });
</script>
<?php elseif (isset($_GET['error']) && $_GET['error']==='empty'): ?>
<script>
Swal.fire({ icon: 'warning', title: 'Missing Info!', text: 'Username and email cannot be empty.' });
</script>
<?php elseif (isset($_GET['error'])): ?>
<script>
Swal.fire({ icon: 'error', title: 'Error!', text: 'Something went wrong. Please try again.' });
</script>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
