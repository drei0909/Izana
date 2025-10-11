<?php
session_start();

  require_once('../classes/database.php');
  require_once (__DIR__. "/../classes/config.php");

  $db = new Database();

  if (!isset($_SESSION['admin_ID'])) {
      header("Location: admin_L.php");
      exit();
}

$adminName = htmlspecialchars($_SESSION['admin_FN'] ?? 'Admin');
?>

<?php include ('templates/header.php'); ?>  

<style>
  body { background: #f8f9fa; padding: 30px; }
  .list-group { min-height: 200px; }
  .list-group-item { cursor: move; }
  .placeholder-highlight { border: 2px dashed #6c757d; background: #e9ecef; }
</style>

<!-- modal -->
<div class="modal fade" id="viewOrderModal" tabindex="-1" aria-labelledby="viewOrderLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">

      <!-- Header -->
      <div class="modal-header">
        <h5 class="modal-title" id="viewOrderLabel">Order Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <!-- Body -->
      <div class="modal-body">
        <div class="mb-1">
          <p><strong>Customer:</strong> <span class="customer-name"></span></p>
          <p><strong>Reference No:</strong> <span class="ref-no"></span></p>
          <p><strong>Customer Number:</strong> <span class="con-no"></span></p>


        </div>

        <div class="order-items mb-0"></div>

        <!-- <div class="receipt text-center"></div> -->
      </div>

      <!-- Footer -->
      <div class="modal-footer justify-content-between">
        <button class="btn btn-danger btn-sm btn-cancel-order">
          <i class="fas fa-times"></i> Cancel
        </button>

        <button class="btn btn-success btn-sm btn-complete-order">
          <i class="fas fa-check"></i> Complete
        </button>
      </div>

    </div>
  </div>
</div>


<div class="wrapper">

<?php include ('templates/sidebar.php'); ?>

  <!-- Main content -->
 <div class="main">
  <div class="admin-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Welcome, <?= $adminName ?></h5>
    <span class="text-muted"><i class="fas fa-user-shield me-1"></i>Admin Panel</span>
  </div>

  <div class="content">
    <h4 class="section-title"><i class="fas fa-inbox me-2"></i>Order Management Queue</h4>
 
    <div class="load-order-que"></div>

  </div>
</div>

</div>


<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- jQuery (needed for DataTables only) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

<!-- Bootstrap 5 Bundle (includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<?php if (isset($_GET['updated']) && $_GET['updated'] === 'success'): ?>
<script>
  function toggleSidebar() {
    document.getElementById("sidebar").classList.toggle("show");
  }
</script>
<?php endif; ?>

<script>
  
    $(document).ready(function(){
        $('#productTable').DataTable();

        $(document).on("click", ".btn-view-order", function(){
          let order_id = $(this).data('order-id');
          $("#viewOrderModal").modal('show'); 


           $.ajax({
            url: "admin_functions.php",
            method: "POST",
            data: { 
              ref: "get_order_item",
              order_id: order_id 
            
            },

            dataType: "json",
            success: function(response) {
              console.log(response);
              if (response.status === "success") {
                $(".order-items").html(response.html); 

              } else {

              }
            },
            error: function() {
              // Swal.fire({ icon:'error', title:'Unable to verify cart', text:'Please try again.' });
            }
          });

        });


// Handle Cancel and Completed button actions
$(document).ready(function() {

    $(document).on('click', '.btn-cancel-order', function() {
    const orderId = $(this).data('order-id');
    const row = $(this).closest('li'); // assuming your queue uses <li> items

    Swal.fire({
        title: 'Cancel this order?',
        text: 'This order will be moved to Cancelled Orders in Sales Report.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Cancel it!',
        cancelButtonText: 'No',
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'admin_functions.php',
                method: 'POST',
                data: { ref: 'cancel_order', order_id: orderId },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success')
                               {
                        Swal.fire('Cancelled!', response.message, 'success');

                        // Remove from order queue visually
                        row.fadeOut(500, function() { $(this).remove(); });

                        // Update Sales Report dynamically (optional)
                        let cancelled = parseFloat($('#cancelledSales').text()) || 0;
                        cancelled += parseFloat(response.total_amount);
                        $('#cancelledSales').text(cancelled.toFixed(2));

                        // Also update total sales if needed
                        let total = parseFloat($('#totalSales').text()) || 0;
                        total -= parseFloat(response.total_amount);
                        $('#totalSales').text(total.toFixed(2));
                    } else {
                        Swal.fire('Error!', response.message, 'error');
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

// Enable double-click to view order details
$(document).off("dblclick", ".order-item").on("dblclick", ".order-item", function() {
    const orderId = $(this).data("id");
    $("#viewOrderModal").modal("show");
    $(".order-items").html("<p class='text-center text-muted'>Loading...</p>");

    $.ajax({
        url: "admin_functions.php",
        type: "POST",
        data: {
            ref: "get_order_item",
            order_id: orderId
        },
        dataType: "json",
        success: function(response) {
            if (response.status === "success") {
                $(".order-items").html(response.html);
            } else {
                $(".order-items").html("<p class='text-danger text-center'>Unable to load order details.</p>");
            }
        },
        error: function() {
            $(".order-items").html("<p class='text-danger text-center'>Error fetching order data.</p>");
        }
    });
});



function viewOrder(orderId) {
    $("#viewOrderModal").modal("show");
    $(".order-items").html("<p class='text-center text-muted'>Loading...</p>");
    $(".customer-name, .ref-no, .receipt").html(""); // clear previous data

    $.ajax({
        url: "admin_functions.php",
        method: "POST",
        data: { ref: "get_order_item", order_id: orderId },
        dataType: "json",
        success: function(response) {
            if (response.status === "success") {
                $(".order-items").html(response.html);

                // Fetch customer name and ref_no and contact
                $.ajax({
                    url: "admin_functions.php",
                    method: "POST",
                    data: { ref: "get_order_info", order_id: orderId },
                    dataType: "json",
                    success: function(info) {
                        if (info.status === "success") {
                            $(".customer-name").text(info.customer_FN + " " + info.customer_LN);
                            $(".ref-no").text(info.ref_no || "N/A");
                            $(".con-no").text(info.customer_contact || "N/A");

                            if (info.receipt) {
                                $(".receipt").html(`
                                    <p class="fw-bold">Receipt:</p>
                                    <img src="../uploads/receipts/${info.receipt}" class="img-fluid rounded shadow-sm border"
                                         style="max-width: 350px; cursor: zoom-in;"
                                         onclick="window.open(this.src)">
                                `);
                            } else {
                                $(".receipt").html("<p class='text-muted'>No receipt uploaded.</p>");
                            }

                            // Update buttons
                            $("#viewOrderModal .btn-cancel-order, #viewOrderModal .btn-complete-order")
                                .data("order-id", orderId);
                        }
                    },
                    error: function() {
                        $(".customer-name, .ref-no").text("Error fetching customer info");
                    }
                });

            } else {
                $(".order-items").html("<p class='text-danger text-center'>Unable to load order items.</p>");
            }
        },
        error: function() {
            $(".order-items").html("<p class='text-danger text-center'>Server error occurred.</p>");
        }
    });
}

// Trigger modal on double-click or button
$(document).on("dblclick", ".order-item, .btn-view-order", function() {
    const orderId = $(this).data("order-id") || $(this).data("id");
    viewOrder(orderId);
});


        function refreshOrderQue(){
          $.ajax({
            url: "admin_functions.php",
            method: "POST",
            data: { 
              ref: "get_orders_que"
            
            },

            dataType: "json",
            success: function(response) {
              console.log(response);
              if (response.status === "success") {

                $(".load-order-que").html(response.html); 
                // Make all list-groups sortable and connected
                $(".list-group").sortable({
                    connectWith: ".list-group",
                    items: "> li:not(:first-child)", // Prevent the header from being draggable
                    placeholder: "placeholder-highlight",
                    start: function(e, ui) {
                    ui.placeholder.height(ui.item.height());
                  },

                  receive: function(event, ui) {
                    const orderName = ui.item.text().trim();
                    const newStatusText = $(this).find('li:first').text();
                    const orderId = ui.item.data('id'); // we'll use data-id to track order id
                    const orderType = ui.item.data('order-type'); // we'll use data-id to track order id
              
                    // Convert header text to numeric status
                    let newStatus = 0;
                    switch (newStatusText) {
                      case 'Pending': newStatus = 1; break;
                      case 'Preparing': newStatus = 2; break;
                      case 'Ready for Pickup': newStatus = 3; break;
                    }

                    console.log(`${orderName} moved to ${newStatusText} (status: ${newStatus})`);

                    // AJAX update
                        $.ajax({
                          url: "admin_functions.php",
                          type: "POST",
                          data: { 
                            ref: "update_order_stats",
                            id: orderId,
                            status: newStatus,
                            orderType: orderType
                          },
                          dataType: "json",
                          success: function(response) {
                            if (response.status === "success") {
                              // ✅ SweetAlert toast feedback for admin
                              Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'success',
                                title: `Order #${orderId} updated to ${newStatusText}`,
                                showConfirmButton: false,
                                timer: 1500
                              });
                            } else {
                              Swal.fire('Error', response.message || 'Failed to update order.', 'error');
                            }
                          },
                          error: function(xhr, status, error) {
                            Swal.fire('Error', 'Unable to update order status.', 'error');
                            console.error('AJAX Error:', error);
                          }
                        });


                  }
                }).disableSelection();

              } else {

              }
            },
            error: function() {
              // Swal.fire({ icon:'error', title:'Unable to verify cart', text:'Please try again.' });
            }
          });
        }

        setInterval(refreshOrderQue, 1000);

        if($(".load-order-que").length) {
          refreshOrderQue();
        }


    });
</script>

</body>
</html>