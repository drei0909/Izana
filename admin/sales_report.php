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

                <!-- Filter Dates -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="startDate" class="form-label">Start Date</label>
                                <input type="date" id="startDate" class="form-control" value="<?= date('Y-m-01') ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="endDate" class="form-label">End Date</label>
                                <input type="date" id="endDate" class="form-control" value="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button id="filterBtn" class="btn btn-outline-primary">Filter</button>
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
                                <h4>₱<span id="totalSales">0.00</span></h4>
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
                                    <th>Order ID</th>
                                    <th>Order Type</th>
                                    <th>Customer</th>
                                    <th>Total Amount</th>
                                    <th>Payment Method</th>
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

    let walkinSales = 0;
    let onlineSales = 0;
    let totalSales = 0;
    let dataTable;

    function fetchOrders(startDate = '', endDate = '') {
        $.ajax({
            url: 'admin_functions.php',
            type: 'POST',
            data: { ref: 'fetch_orders' },
            dataType: 'json',
            success: function(res){
                if(res.status === 'success'){
                    let html = '';
                    walkinSales = 0;
                    onlineSales = 0;
                    totalSales = 0;

                    res.orders.forEach(order => {
                        // Filter by dates if specified
                        if(startDate && endDate){
                            const orderDate = new Date(order.order_date);
                            const start = new Date(startDate);
                            const end = new Date(endDate);
                            if(orderDate < start || orderDate > end) return;
                        }

                        html += `<tr>
                            <td>${order.id}</td>
                            <td>${order.order_channel}</td>
                            <td>${order.customer_name || 'Walk-in Customer'}</td>
                            <td>₱${parseFloat(order.total_amount).toFixed(2)}</td>
                            <td>${order.ref_no || order.payment_method}</td>
                            <td>${order.payment_status === 'Completed' ? '<span class="badge bg-success">Completed</span>' : '<span class="badge bg-warning">Pending</span>'}</td>
                            <td>${order.order_date}</td>
                            <td>
                                <button class="btn btn-outline-danger btn-sm deleteOrder" data-id="${order.id}">Delete</button>
                            </td>
                        </tr>`;

                        if(order.order_channel === 'Online') onlineSales += parseFloat(order.total_amount);
                        if(order.order_channel === 'POS') walkinSales += parseFloat(order.total_amount);
                    });

                    totalSales = walkinSales + onlineSales;

                    $('#ordersBody').html(html);
                    $('#walkinSales').text(walkinSales.toFixed(2));
                    $('#onlineSales').text(onlineSales.toFixed(2));
                    $('#totalSales').text(totalSales.toFixed(2));

                    if(dataTable) dataTable.destroy();
                    dataTable = $('#ordersTable').DataTable({ order: [[6, 'desc']] });
                }
            }
        });
    }

    fetchOrders();

    // Filter button
    $('#filterBtn').click(function(){
        let start = $('#startDate').val();
        let end = $('#endDate').val();
        fetchOrders(start, end);
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
                        fetchOrders();
                    }
                });
            }
        });
    });

});
</script>

</body>
</html>
