<?php
session_start();

require_once('../classes/database.php');
require_once(__DIR__ . "/../classes/config.php");

$active_page = 'view_customers';

if (!isset($_SESSION['admin_ID'])) {
    header("Location: admin_L.php");
    exit();
}

$db = new Database();
$customers = $db->getAllCustomers();

$adminName = htmlspecialchars($_SESSION['admin_FN'] ?? 'Admin', ENT_QUOTES);
?>
<?php include('templates/header.php'); ?>

<div class="wrapper">
  <?php include('templates/sidebar.php'); ?>

  <!-- Main content -->
  <div class="main">
    <div class="admin-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">Welcome, <?= $adminName ?></h5>
      <span class="text-muted"><i class="fas fa-user-shield me-1"></i>Admin Panel</span>
    </div>

    <div class="dashboard-content">
      <div class="container-fluid">
        <h4 class="section-title"><i class="fas fa-users me-2"></i>Customer List</h4>

        <div class="card p-3">
          <div class="table-responsive">
            <table id="customerTable" class="table table-hover table-bordered align-middle">
              <thead class="table-light">
                <tr>
                  <th>ID</th>
                  <th>Full Name</th>
                  <th>Email</th>
                  <th>Registered At</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($customers)): ?>
                  <?php foreach ($customers as $cust): ?>
                      <?php
                        $id = (int)($cust['customer_id'] ?? 0); 
                        $fullname = htmlspecialchars(trim(($cust['customer_FN'] ?? '') . ' ' . ($cust['customer_LN'] ?? '')), ENT_QUOTES);
                        $email = htmlspecialchars($cust['customer_email'] ?? '', ENT_QUOTES);
                        $created = !empty($cust['created_at']) ? date("M d, Y h:i A", strtotime($cust['created_at'])) : '-';
                        $status = strtolower($cust['status'] ?? 'active');
                      ?>
                      <tr id="cust-row-<?= $id ?>">
                        <td><?= $id ?></td>
                        <td><?= $fullname ?></td>
                        <td><?= $email ?></td>
                        <td><?= $created ?></td>
                        <td>
                          <?php if ($status === 'blocked'): ?>
                            <span class="badge bg-danger">Blocked</span>
                          <?php else: ?>
                            <span class="badge bg-success">Active</span>
                          <?php endif; ?>
                        </td>
                        <td>
                          <?php if ($status === 'blocked'): ?>
                            <button class="btn btn-sm btn-success unblock-btn" data-id="<?= $id ?>">
                              <i class="fas fa-unlock"></i> Unblock
                            </button>
                          <?php else: ?>
                            <button class="btn btn-sm btn-danger block-btn" data-id="<?= $id ?>">
                              <i class="fas fa-ban"></i> Block
                            </button>
                          <?php endif; ?>
                          <button class="btn btn-sm btn-secondary ms-1 view-btn" data-id="<?= $id ?>">
                            <i class="fas fa-eye"></i> View
                          </button>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<!-- Customer Details Modal -->
<div class="modal fade" id="viewCustomerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title"><i class="fas fa-user-circle me-2"></i>Customer Details</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="text-center mb-3">
          
        </div>
        <p><strong>Full Name:</strong> <span id="custName">-</span></p>
        <p><strong>Email:</strong> <span id="custEmail">-</span></p>
        <p><strong>Contact:</strong> <span id="custContact">-</span></p>
        <p><strong>Status:</strong> <span id="custStatus">-</span></p>
        <p><strong>Registered At:</strong> <span id="custCreated">-</span></p>
      </div>
    </div>
  </div>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- DataTables -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
  // Initialize table
  $('#customerTable').DataTable({
    order: [[0, 'asc']]
  });

  // --- Block Customer ---
  $(document).on('click', '.block-btn', function() {
    const id = $(this).data('id');
    Swal.fire({
      title: 'Block Customer?',
      text: 'This user will not be able to log in or order until unblocked.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, Block',
      cancelButtonText: 'Cancel'
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
      title: 'Unblock Customer?',
      text: 'This user will regain access to their account.',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Yes, Unblock',
      cancelButtonText: 'Cancel'
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

  // --- View Customer Modal ---
  $(document).on('click', '.view-btn', function() {
    const id = $(this).data('id');

    $.ajax({
      url: 'admin_functions.php',
      type: 'POST',
      data: { ref: 'get_customer_details', customer_id: id },
      dataType: 'json',
      success: function(response) {
        if (response.status === 'success') {
          const c = response.data;
          $('#custImage').attr('src', c.profile_img || '../uploads/default.png');
          $('#custName').text(c.full_name);
          $('#custEmail').text(c.email);
          $('#custContact').text(c.contact || 'N/A');
          $('#custStatus').html(
            c.status === 'blocked'
              ? '<span class="badge bg-danger">Blocked</span>'
              : '<span class="badge bg-success">Active</span>'
          );
          $('#custCreated').text(c.created_at);
          $('#viewCustomerModal').modal('show');
        } else {
          Swal.fire('Error', response.message, 'error');
        }
      },
      error: function() {
        Swal.fire('Error', 'Could not fetch customer details.', 'error');
      }
    });
  });
});
</script>

</body>
</html>
