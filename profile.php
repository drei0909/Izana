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
.container-profile {
  max-width: 1100px;
  margin: 0 auto 40px auto;
  background: var(--gray-mid);
  border: 1px solid var(--gray-light);
  border-radius: 18px;
  padding: 35px;
  box-shadow: 0 12px 30px rgba(0,0,0,0.4);
}
.card {
  background: var(--gray-dark);
  border: 1px solid var(--gray-light);
  border-radius: 14px;
  color: var(--text-light);
}
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
.btn-warning {
  background-color: var(--accent);
  border: none;
  color: #fff;
  font-weight: 600;
}
.btn-warning:hover {
  background-color: var(--accent-dark);
}
.btn-danger {
  font-weight: 600;
}
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
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-custom fixed-top">
  <div class="container-fluid" style="max-width:1400px;">
    <a class="navbar-brand" href="#">
      <img src="uploads/izana_logo.png" alt="IZANA Logo" style="height: 80px;">
    </a>
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
  <h2 class="mb-4">Profile</h2>

  <!-- Customer Info -->
  <div class="card mb-4">
    <div class="card-body">
      <h5 class="card-title"><?= escape($customer['customer_FN'].' '.$customer['customer_LN']) ?></h5>
      <p><strong>Username:</strong> <?= escape($customer['customer_username']) ?></p>
      <p><strong>Email:</strong> <?= escape($customer['customer_email']) ?></p>
      <p><strong>Member Since:</strong> <?= date('F j, Y', strtotime($customer['created_at'])) ?></p>

      <button class="btn btn-sm btn-warning mt-2" data-bs-toggle="modal" data-bs-target="#editModal">
        <i class="fas fa-pen"></i> Edit Account Info
      </button>
      <button class="btn btn-sm btn-danger mt-2 ms-2" id="deleteAccountBtn">
        <i class="fas fa-trash"></i> Delete Account
      </button>
    </div>
  </div>

  <!-- Orders -->
  <h4 class="mb-3">Your Orders</h4>
  <?php if ($orders): ?>
    <?php foreach ($orders as $order): ?>
      <div class="order-card">
        <div class="order-header">
          Order #<?= $order['order_id'] ?> — ₱<?= number_format($order['total_amount'], 2) ?>
        </div>
        <div><strong>Ref No:</strong> <?= escape($order['ref_no']) ?></div>
        <div><strong>Order Date:</strong> <?= date('F j, Y g:i A', strtotime($order['created_at'])) ?></div>
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
          <?= escape($order['payment_method'] ?? 'N/A') ?> -
          ₱<?= number_format($order['payment_amount'] ?? 0, 2) ?>
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
            <i class="fas fa-shopping-bag"></i> Rebuy
          </button>
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
            <label class="form-label">New Username</label>
            <input type="text" class="form-control" name="new_username">
          </div>
          <div class="mb-3">
            <label class="form-label">New Email</label>
            <input type="email" class="form-control" name="new_email">
          </div>
          <div class="mb-3">
            <label class="form-label">New Password</label>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll(".rebuy-btn").forEach(btn => {
  btn.addEventListener("click", function() {
    const orderID = this.getAttribute("data-order-id");
    Swal.fire({
      title: "Rebuy this order?",
      text: "All items from this order will be added to your cart.",
      icon: "question",
      showCancelButton: true,
      confirmButtonText: "Yes, Rebuy",
      cancelButtonText: "Cancel",
      confirmButtonColor: "#b07542"
    }).then((result) => {
      if (result.isConfirmed) {
        fetch("functions.php", {
          method: "POST",
          headers: {"Content-Type": "application/x-www-form-urlencoded"},
          body: `ref=rebuy_order&order_id=${orderID}`
        })
        .then(res => res.json())
        .then(data => {
          if (data.status === "success") {
            Swal.fire({icon:"success",title:"Items Added!",text:"Redirecting to checkout...",showConfirmButton:false,timer:1500});
            setTimeout(() => window.location.href = "checkout.php", 1500);
          } else {
            Swal.fire({icon:"error",title:"Error",text:data.message || "Something went wrong."});
          }
        })
        .catch(() => Swal.fire({icon:"error",title:"Error",text:"Unable to process your request."}));
      }
    });
  });
});

// 🗑️ Delete Account
document.getElementById("deleteAccountBtn").addEventListener("click", () => {
  Swal.fire({
    title: "Delete Account?",
    text: "This action is permanent and cannot be undone.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Yes, Delete It",
    cancelButtonText: "Cancel",
    confirmButtonColor: "#dc3545"
  }).then((result) => {
    if (result.isConfirmed) {
      fetch("delete_account.php", {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: "ref=delete_account"
      })
      .then(res => res.json())
      .then(data => {
        if (data.status === "success") {
          Swal.fire({icon:"success",title:"Account Deleted",text:"Redirecting...",showConfirmButton:false,timer:1500});
          setTimeout(() => window.location.href = "registration.php", 1500);
        } else {
          Swal.fire({icon:"error",title:"Error",text:data.message || "Failed to delete account."});
        }
      })
      .catch(() => Swal.fire({icon:"error",title:"Error",text:"Unable to delete account."}));
    }
  });
});
</script>
</body>
</html>
