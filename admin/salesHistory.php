<?php
session_start();
require_once('../classes/database.php');
require_once(__DIR__ . "/../classes/config.php");
$db = new Database();

if (!isset($_SESSION['admin_ID'])) {
    header("Location: admin_L.php");
    exit();
}

$adminName = htmlspecialchars($_SESSION['admin_FN'] ?? 'Admin');
$salesHistory = $db->getSalesHistory();
?>

<?php include('templates/header.php'); ?>

<div class="wrapper">
    <?php include('templates/sidebar.php'); ?>

    <div class="main">
        <div class="admin-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Welcome, <?= $adminName ?></h5>
            <span class="text-muted"><i class="fas fa-user-shield me-1"></i>Admin Panel</span>
        </div>

        <div class="content">
            <div class="container-fluid">
                <h4 class="section-title"><i class="fas fa-history me-2"></i>Sales History</h4>

                <div class="card mb-4">
                    <div class="card-body">
                        <table id="salesTable" class="table table-bordered table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Walk-in Sales (₱)</th>
                                    <th>Online Sales (₱)</th>
                                    <th>Total Sales (₱)</th>
                                    <th>Void Sales (₱)</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($salesHistory as $record): ?>
                                    <tr id="row-<?= $record['id'] ?>">
                                        <td><?= date("F d, Y", strtotime($record['order_date'])) ?></td>
                                        <td><?= number_format($record['walk_in_sales'], 2) ?></td>
                                        <td><?= number_format($record['online_sales'], 2) ?></td>
                                        <td><?= number_format($record['total_sales'], 2) ?></td>
                                        <td class="text-danger"><?= number_format($record['void_sales'], 2) ?></td>
                                        <td>
                                            <button class="btn btn-info btn-sm view-details" data-date="<?= $record['order_date'] ?>">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            <button class="btn btn-danger btn-sm delete-record" data-id="<?= $record['id'] ?>">
                                                <i class="fas fa-trash-alt"></i> Delete
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 🔹 View Details Modal -->
<div class="modal fade" id="viewDetailsModal" tabindex="-1" aria-labelledby="viewDetailsLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title" id="viewDetailsLabel">Sales Details - <span id="salesDate"></span></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="detailsContent" class="text-center text-muted">Loading details...</div>
      </div>
    </div>
  </div>
</div>

<!-- JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    $('#salesTable').DataTable({ order: [[0, 'desc']] });

    // 🔹 View Details
    $('.view-details').click(function() {
        const date = $(this).data('date');
        $('#salesDate').text(new Date(date).toLocaleString('en-US', {month: 'long', day: 'numeric', year: 'numeric'}));
        $('#detailsContent').html('<p class="text-center text-muted">Loading...</p>');
        $('#viewDetailsModal').modal('show');

        $.ajax({
            url: 'admin_functions.php',
            type: 'POST',
            data: { ref: 'get_sales_details', date: date },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    let html = `
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Channel</th>
                                        <th>Total (₱)</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>`;
                    res.data.forEach(order => {
                        html += `
                            <tr>
                                <td>${order.order_id}</td>
                                <td>${order.customer_name || '—'}</td>
                                <td>${order.order_channel}</td>
                                <td>${parseFloat(order.total_amount).toFixed(2)}</td>
                                <td>${order.order_status == 4 ? 
                                    '<span class="badge bg-danger">Void</span>' : 
                                    '<span class="badge bg-success">Completed</span>'}
                                </td>
                            </tr>`;
                    });
                    html += `</tbody></table></div>`;
                    $('#detailsContent').html(html);
                } else {
                    $('#detailsContent').html('<p class="text-danger">No records found for this date.</p>');
                }
            },
            error: function() {
                $('#detailsContent').html('<p class="text-danger">Error loading details.</p>');
            }
        });
    });

    // 🔹 Delete Record (AJAX)
    $('.delete-record').click(function() {
        const id = $(this).data('id');
        const row = $('#row-' + id);

        Swal.fire({
            title: 'Are you sure?',
            text: 'This record will be permanently deleted.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'admin_functions.php',
                    type: 'POST',
                    data: { ref: 'delete_sales_history', id: id },
                    dataType: 'json',
                    success: function(res) {
                        if (res.status === 'success') {
                            Swal.fire('Deleted!', res.message, 'success');
                            row.fadeOut(500, () => row.remove());
                        } else {
                            Swal.fire('Error!', res.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error!', 'Something went wrong.', 'error');
                    }
                });
            }
        });
    });
});
</script>

</body>
</html>
