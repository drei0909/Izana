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
                                    <option value="Cancelled">Cancelled Orders</option>
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
                                <h6>Cancelled Orders</h6>
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
                    const status = parseInt(order.order_status); // Ensure numeric
          
                    // Filter by order type
                    if (orderType !== 'All') {
                        if (orderType === 'Cancelled' && status !== 4) return;
                        if (orderType !== 'Cancelled' && order.order_channel !== orderType) return;
                    }
               
                    var status_msg = '';
                    if (status === 4) {
                        status_msg = '<span class="badge bg-danger">Cancelled</span>';
                    } else if (status === 5) {
                        status_msg = '<span class="badge bg-success">Completed</span>';
                    } else {
                        status_msg = '<span class="badge bg-warning">Pending</span>';
                    }

                    // Build table row
                    html += `<tr>
                        <td>${order.order_channel}</td>
                        <td>${order.customer_name || 'Walk-in Customer'}</td>
                        <td>₱${parseFloat(order.total_amount).toFixed(2)}</td>
                        <td>${status_msg}</td>
                        <td>${order.order_date}</td>
                        <td>
                            <button class="btn btn-outline-danger btn-sm deleteOrder" data-id="${order.id}">Delete</button>
                        </td>
                    </tr>`;

                    // Update sales totals
                    if (status !== 4) { // Only include active/completed orders
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

                // Initialize or refresh DataTable
                if (dataTable) dataTable.destroy();
                dataTable = $('#ordersTable').DataTable({ order: [[4, 'desc']] }); // date column index = 4
            }
        },
        error: function() {
            console.error('Error fetching orders');
        }
    });
}

    // Initial load
    fetchOrders();

    // Change order type filter
    $('#orderType').change(function(){
        let type = $(this).val();
        fetchOrders(type);
    });

    // Delete order
    $(document).on('click', '.deleteOrder', function(){
        let orderId = $(this).data('id');
        Swal.fire({
            title: 'Are you sure?',
            text: "This will delete the order permanently!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if(result.isConfirmed){
                $.ajax({
                    url: 'functions.php',
                    type: 'POST',
                    data: { ref: 'delete_order', order_id: orderId },
                    dataType: 'json',
                    success: function(res){
                        Swal.fire('Deleted!', res.message || 'Order deleted.', 'success');
                        fetchOrders($('#orderType').val());
                    }
                });
            }
        });
    });

});
</script>
</body>
</html>
