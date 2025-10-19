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

                <h4 class="section-title"><i class="fas fa-chart-bar me-2"></i>Sales Report</h4>

                <!-- Order Type Filter -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label for="orderType" class="form-label">Select Order Type</label>
                                <select id="orderType" class="form-select">
                                    <option value="All" selected>All Orders</option>
                                    <option value="Online">Online Orders</option>
                                    <option value="POS">POS Orders</option>
                                    <option value="Cancelled">Void Orders</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sales Summary -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h6>Walk-in Sales</h6>
                                <h4>₱<span id="walkinSales">0.00</span></h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h6>Online Sales</h6>
                                <h4>₱<span id="onlineSales">0.00</span></h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h6>Total Sales</h6>
                                <h4>₱<span id="totalSales">0.00</span></h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h6>Void Orders</h6>
                                <h4>₱<span id="cancelledSales">0.00</span></h4>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Orders Table -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5>Orders</h5>
                        <table id="ordersTable" class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>Order Type</th>
                                    <th>Customer</th>
                                    <th>Total Amount</th>
                                    <th>Payment Status</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="ordersBody"></tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- View Order Modal -->
<div class="modal fade" id="viewOrderModal" tabindex="-1" aria-labelledby="viewOrderLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="viewOrderLabel">Order Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p><strong>Customer:</strong> <span id="modalCustomer"></span></p>
        <p><strong>Ref No:</strong> <span id="modalRef"></span></p>
        <p><strong>Pickup Time:</strong> <span id="modalPickup"></span></p>
        <!-- Total placed under Pickup Time -->
        <p><strong>Total:</strong> <span id="modalTotal" class="text-success">₱0.00</span></p>
        <div id="modalItems"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>


<!-- JS Libraries -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function(){

    let dataTable;
    let walkinSales = 0;
    let onlineSales = 0;
    let cancelledSales = 0;
    let totalSales = 0;

    function fetchOrders(orderType = 'All') {
        $.ajax({
            url: 'admin_functions.php',
            type: 'POST',
            data: { ref: 'fetch_orders' },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    let html = '';
                    walkinSales = 0;
                    onlineSales = 0;
                    cancelledSales = 0;

                    res.orders.forEach(order => {
                        const status = parseInt(order.order_status);

                        // Filter by order type
                        if (orderType !== 'All') {
                            if (orderType === 'Cancelled' && status !== 4) return;
                            if (orderType !== 'Cancelled' && order.order_channel !== orderType) return;
                        }

                        let payment_msg = '';
                        if (order.payment_status === 'Completed') payment_msg = '<span class="badge bg-success">Paid</span>';
                        else if (order.payment_status === 'Pending') payment_msg = '<span class="badge bg-warning">Pending</span>';
                        else payment_msg = '<span class="badge bg-secondary">N/A</span>';


                        html += `<tr>
                        <td>${order.order_channel}</td>
                        <td>${order.customer_name || 'Walk-in Customer'}</td>
                        <td>₱${parseFloat(order.total_amount).toFixed(2)}</td>
                        <td>${payment_msg}</td> <!-- <-- Use payment_msg here -->
                        <td>${order.order_date}</td>
                        <td>
                            <button class="btn btn-outline-primary btn-sm viewOrder" data-id="${order.id}" data-type="${order.order_channel}">View</button>
                            <button class="btn btn-outline-danger btn-sm deleteOrder" data-id="${order.id}" data-type="${order.order_channel}">Delete</button>
                        </td>
                        </tr>`;


                        if (status !== 4) {
                            if (order.order_channel === 'Online') onlineSales += parseFloat(order.total_amount);
                            if (order.order_channel === 'POS') walkinSales += parseFloat(order.total_amount);
                        } else {
                            cancelledSales += parseFloat(order.total_amount);
                        }
                    });

                    totalSales = walkinSales + onlineSales;

                    $('#ordersBody').html(html);
                    $('#walkinSales').text(walkinSales.toFixed(2));
                    $('#onlineSales').text(onlineSales.toFixed(2));
                    $('#totalSales').text(totalSales.toFixed(2));
                    $('#cancelledSales').text(cancelledSales.toFixed(2));

                    if (dataTable) dataTable.destroy();
                    dataTable = $('#ordersTable').DataTable({ order: [[4, 'desc']] });
                }
            }
        });
    }

    // Initial load
    fetchOrders();

    // Change order type filter
    $('#orderType').change(function(){
        fetchOrders($(this).val());
    });

    // Delete order
    $(document).on('click', '.deleteOrder', function(){
        let orderId = $(this).data('id');
        let orderType = $(this).data('type');
        Swal.fire({
            title: 'Are you sure?',
            text: "This will delete the order permanently!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if(result.isConfirmed){
                $.post('functions.php', { ref: 'cancel_order', order_id: orderId, order_type: orderType }, function(res){
                    Swal.fire('Deleted!', res.message || 'Order deleted.', 'success');
                    fetchOrders($('#orderType').val());
                }, 'json');
            }
        });
    });

    // View order modal
    $(document).on('click', '.viewOrder', function(){
        let orderId = $(this).data('id');
        let orderType = $(this).data('type').toLowerCase();

        $.post('admin_functions.php', { ref: 'get_order_item', order_id: orderId, order_type: orderType }, function(res){
            // Inside your AJAX success for .viewOrder click
            if(res.status === 'success'){
                $('#modalCustomer').text(res.customer_name || 'Walk-in Customer');
                $('#modalRef').text(res.ref_no || '-');
                $('#modalPickup').text(res.pickup_time || '-');
                $('#modalTotal').text('₱' + parseFloat(res.total_amount).toFixed(2)).addClass('text-success');
                $('#modalItems').html(res.html);
                $('#viewOrderModal').modal('show');
            }

        }, 'json');
    });

});
</script>
</body>
</html>
