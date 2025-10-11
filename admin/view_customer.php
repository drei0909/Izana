<?php
session_start();

require_once('../classes/database.php');
require_once(__DIR__ . "/../classes/config.php");

if (!isset($_SESSION['admin_ID'])) {
    header("Location: admin_L.php");
    exit();
}

$db = new Database();

$customerID = $_GET['id'] ?? null;
if (!$customerID) {
    header("Location: view_customers.php");
    exit();
}

// Fetch customer details
$stmt = $db->conn->prepare("SELECT customer_ID, customer_FN, customer_LN, customer_email, contact_number, created_at, status 
FROM customer 
WHERE customer_ID = ?");
$stmt->execute([$customerID]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    header("Location: view_customers.php");
    exit();
}

$adminName = htmlspecialchars($_SESSION['admin_FN'] ?? 'Admin', ENT_QUOTES);
?>

<?php include('templates/header.php'); ?>

<div class="wrapper">
  <?php include('templates/sidebar.php'); ?>

  <div class="main">
    <div class="admin-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">Customer Profile</h5>
      <a href="view_customers.php" class="btn btn-sm btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
    </div>

    <div class="dashboard-content">
      <div class="container-fluid">
        <div class="card shadow p-4 mt-3">
          <h4 class="mb-3 text-primary"><i class="fas fa-user-circle me-2"></i>Customer Details</h4>
          
          <div class="row mb-3">
            <div class="col-md-6">
              <label class="fw-bold">Full Name</label>
              <p><?= htmlspecialchars($customer['customer_FN'] . ' ' . $customer['customer_LN']) ?></p>
            </div>
            <div class="col-md-6">
              <label class="fw-bold">Email</label>
              <p><?= htmlspecialchars($customer['customer_email']) ?></p>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <label class="fw-bold">Contact Number</label>
              <p><?= htmlspecialchars($customer['contact_number'] ?? 'Not Provided') ?></p>
            </div>
            <div class="col-md-6">
              <label class="fw-bold">Status</label>
              <?php if (strtolower($customer['status']) === 'blocked'): ?>
                <span class="badge bg-danger">Blocked</span>
              <?php else: ?>
                <span class="badge bg-success">Active</span>
              <?php endif; ?>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <label class="fw-bold">Account Created At</label>
              <p><?= date("M d, Y h:i A", strtotime($customer['created_at'])) ?></p>
            </div>
          </div>

          <hr>

          <div class="text-end">
            <?php if (strtolower($customer['status']) === 'blocked'): ?>
              <button class="btn btn-success unblock-btn" data-id="<?= $customer['customer_ID'] ?>">
                <i class="fas fa-unlock"></i> Unblock Customer
              </button>
            <?php else: ?>
              <button class="btn btn-danger block-btn" data-id="<?= $customer['customer_ID'] ?>">
                <i class="fas fa-ban"></i> Block Customer
              </button>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
  // --- Block Customer ---
  $(document).on('click', '.block-btn', function() {
    const id = $(this).data('id');
    Swal.fire({
      title: 'Block this customer?',
      text: 'They will not be able to log in or order until unblocked.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, Block'
    }).then((result) => {
      if (result.isConfirmed) {
        $.post('admin_functions.php', {
          ref: 'update_customer_status',
          customer_id: id,
          action: 'block'
        }, function(response) {
          if (response.status === 'success') {
            Swal.fire('Blocked!', response.message, 'success').then(() => location.reload());
          } else {
            Swal.fire('Error', response.message, 'error');
          }
        }, 'json');
      }
    });
  });

  // --- Unblock Customer ---
  $(document).on('click', '.unblock-btn', function() {
    const id = $(this).data('id');
    Swal.fire({
      title: 'Unblock this customer?',
      text: 'They will regain access to their account.',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Yes, Unblock'
    }).then((result) => {
      if (result.isConfirmed) {
        $.post('admin_functions.php', {
          ref: 'update_customer_status',
          customer_id: id,
          action: 'unblock'
        }, function(response) {
          if (response.status === 'success') {
            Swal.fire('Unblocked!', response.message, 'success').then(() => location.reload());
          } else {
            Swal.fire('Error', response.message, 'error');
          }
        }, 'json');
      }
    });
  });
});
</script>
</body>
</html>
