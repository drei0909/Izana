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
          <p><strong>Pick Up Time:</strong> <span class="pickup_time"></span></p>


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

<!-- Reject Reason Modal -->
<div class="modal fade" id="rejectReasonModal" tabindex="-1" aria-labelledby="rejectReasonModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="rejectReasonModalLabel">Reject Order</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <label for="rejectReasonSelect" class="form-label">Select a common reason (optional)</label>
        <select id="rejectReasonSelect" class="form-select mb-3">
          <option value="">-- Select a reason --</option>
          <option value="Out of stock">Out of stock</option>
          <option value="Payment issue">Payment issue</option>
          <option value="Unable to fulfill order">Unable to fulfill order</option>
        </select>

        <label for="rejectReasonText" class="form-label">Or type a custom reason</label>
        <textarea id="rejectReasonText" class="form-control" rows="3" placeholder="Type your reason here..."></textarea>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="submitRejectReason">Reject Order</button>
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

//reject newly added
  $(document).on('click', '.btn-reject', function() {
    const orderId = $(this).data('id');
    const orderType = $(this).data('type');

    // Store IDs in modal
    $('#rejectOrderId').val(orderId);
    $('#rejectOrderType').val(orderType);
    $('#rejectReasonDropdown').val(''); // reset dropdown
    $('#rejectReasonText').val(''); // reset text area

    $('#rejectOrderModal').modal('show');
});


    let rejectOrderId = null;
    let rejectOrderType = null;

// Open modal when clicking Reject
$(document).on('click', '.btn-reject', function() {
    rejectOrderId = $(this).data('id');
    rejectOrderType = $(this).data('type');

    // Reset previous input
    $('#rejectReasonText').val('');
    $('#rejectReasonSelect').val('');

    // Show modal
    $('#rejectReasonModal').modal('show');
});

// Submit reason and show confirmation
$('#submitRejectReason').on('click', function() {
    let reason = $('#rejectReasonText').val().trim() || $('#rejectReasonSelect').val().trim();

    if (!reason) {
        Swal.fire({
            icon: 'warning',
            title: 'Please provide a reason!',
            text: 'You must type a reason or select one from the dropdown.'
        });
        return;
    }

    // Show confirmation SweetAlert
    Swal.fire({
        title: 'Are you sure you want to reject this order?',
        text: "This action cannot be undone.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, reject it!',
        cancelButtonText: 'No',
        confirmButtonColor: '#dc3545'
    }).then((result) => {
        if (result.isConfirmed) {
            // Send AJAX request to reject
            $.ajax({
                url: 'admin_functions.php',
                type: 'POST',
                data: {
                    ref: 'review_action',
                    order_id: rejectOrderId,
                    orderType: rejectOrderType,
                    action: 'reject',
                    reason: reason
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Order Rejected',
                            text: 'Order has been rejected successfully.',
                            timer: 1500,
                            showConfirmButton: false
                        });

                        // Remove order from Review queue
                        let row = $(".order-item[data-id='" + rejectOrderId + "']");
                        row.fadeOut(400, function() { $(this).remove(); });

                        $('#rejectReasonModal').modal('hide');
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Server error occurred.', 'error');
                }
            });
        }
    });
});




  
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
    const orderType = $(this).data('order-type');
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
                data: { ref: 'cancel_order', order_id: orderId, order_type: orderType },
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
    const orderType = $(this).data("type");

    $("#viewOrderModal").modal("show");
    $(".order-items").html("<p class='text-center text-muted'>Loading...</p>");

    $.ajax({
        url: "admin_functions.php",
        type: "POST",
        data: {
            ref: "get_order_item",
            order_id: orderId,
            orderType: orderType
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



function viewOrder(orderId, orderType) {
    $("#viewOrderModal").modal("show");
    $(".order-items").html("<p class='text-center text-muted'>Loading...</p>");
    $(".customer-name, .ref-no, .receipt, .con-no").html("Walk-in"); // clear previous data

    $.ajax({
        url: "admin_functions.php",
        method: "POST",
        data: { ref: "get_order_item", order_id: orderId, order_type: orderType },
        dataType: "json",
        success: function(response) {
            if (response.status === "success") {
                $(".order-items").html(response.html);

                 $(".btn-cancel-order, .btn-complete-order").attr('data-order-id',orderId);
                 $(".btn-cancel-order, .btn-complete-order").attr('data-order-type',orderType);

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
                             $(".pickup_time").text(info.pickup_time || "N/A");


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

    // Handle "Complete Order" button
    $(document).on('click', '.btn-complete-order', function() {
      const orderId = $(this).data('order-id');
      const orderType = $(this).data('order-type');
      alert(orderId + " " + orderType);
      const row = $(".order-item[data-id='" + orderId + "']"); // Find order in queue

      Swal.fire({
          title: 'Mark as Completed?',
          text: 'This order will be added to Sales Report as completed.',
          icon: 'question',
          showCancelButton: true,
          confirmButtonText: 'Yes, Complete it!',
          cancelButtonText: 'No'
      }).then((result) => {
          if (result.isConfirmed) {
              $.ajax({
                  url: 'admin_functions.php',
                  method: 'POST',
                  data: {
                      ref: 'complete_order',
                      order_id: orderId,
                      order_type: orderType
                  },
                  dataType: 'json',
                  success: function(response) {
                      if (response.status === 'success') {
                          Swal.fire({
                              icon: 'success',
                              title: 'Order Completed!',
                              text: response.message,
                              timer: 1500,
                              showConfirmButton: false
                          });

                          // Remove order from queue visually
                          row.fadeOut(500, function() { $(this).remove(); });

                          // Optionally close modal
                          $("#viewOrderModal").modal("hide");

                          // Send a notification to customer
                          $.ajax({
                              url: 'admin_functions.php',
                              type: 'POST',
                              data: {
                                  ref: 'insert_notification',
                                  order_id: orderId,
                                  message: 'Your order #' + orderId + ' has been completed. Thank you for ordering with us!'
                              },
                              success: function(n) {
                                  console.log('Notification sent to customer.');
                              }
                          });
                      } else {
                          Swal.fire('Error', response.message, 'error');
                      }
                  },
                  error: function() {
                      Swal.fire('Error', 'Unable to complete order.', 'error');
                  }
              });
          }
      });
    });

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
    const orderType = $(this).data("order-type");

    viewOrder(orderId , orderType);
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
                        items: "> li:not(:first-child)", // header row excluded
                        placeholder: "placeholder-highlight",
                        helper: "clone", // prevents original from collapsing

                        start: function(e, ui) {
                            const orderStatus = ui.item.data("status"); 
                            const currentListId = ui.item.closest(".list-group").attr("id");

                            // Match placeholder height & width to dragged item
                            ui.placeholder.height(ui.item.outerHeight());
                            ui.placeholder.width(ui.item.outerWidth());

                            // Block dragging if the order is still under review
                            if (currentListId === "review" && orderStatus === "pending") {
                                $(this).sortable("cancel"); 
                                Swal.fire({
                                    icon: "warning",
                                    title: "Action Not Allowed!",
                                    text: "You can't drag this order. Accept or reject it first.",
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                            }
                        },

                        receive: function(event, ui) {
                            const targetListId = $(this).attr("id");
                            const orderStatus = ui.item.data("status");

                            // Prevent moving pending/review orders into any other column
                            if (orderStatus === "pending" || orderStatus === "review") {
                                $(ui.sender).sortable("cancel");
                                Swal.fire({
                                    icon: "warning",
                                    title: "Action blocked!",
                                    text: "You can't move this order. Accept or reject it first.",
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                                return;
                            }

                            // Prevent moving orders back to Review
                            if (targetListId === "review") {
                                $(ui.sender).sortable("cancel");
                                Swal.fire({
                                    icon: "warning",
                                    title: "Not allowed!",
                                    text: "Orders cannot be moved back to Review.",
                                    timer: 1000,
                                    showConfirmButton: false
                                });
                                return;
                            }

                            // Update status for other columns
                            const orderId = ui.item.data("id");
                            const orderType = ui.item.data("order-type");
                            const newStatusText = $(this).find("li:first").text();

                            let newStatus = 0;
                            switch (newStatusText) {
                                case "Review": newStatus = 1; break;
                                case "Preparing": newStatus = 2; break;
                                case "Ready for Pickup": newStatus = 3; break;
                            }

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
                                        ui.item.attr("data-status", newStatusText.toLowerCase());
                                        Swal.fire({
                                            toast: true,
                                            position: "top-end",
                                            icon: "success",
                                            title: `Order #${orderId} updated to ${newStatusText}`,
                                            showConfirmButton: false,
                                            timer: 1500
                                        });
                                    } else {
                                        $(ui.sender).sortable("cancel");
                                        Swal.fire("Error", response.message || "Failed to update order.", "error");
                                    }
                                },
                                error: function() {
                                    $(ui.sender).sortable("cancel");
                                    Swal.fire("Error", "Unable to update order status.", "error");
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

        setInterval(refreshOrderQue, 5000);

        if($(".load-order-que").length) {
          refreshOrderQue();
        }


// Accept button only
$(document).on('click', '.btn-accept', function() {
    const orderId = $(this).data('id');
    const orderType = $(this).data('type');
    const row = $(this).closest('li');

    Swal.fire({
        title: `Are you sure you want to accept this order?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Accept it',
        cancelButtonText: 'No',
        confirmButtonColor: '#28a745'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: "admin_functions.php",
                type: "POST",
                data: { ref: "review_action", order_id: orderId, action: 'accept', orderType: orderType },
                dataType: "json",
                success: function(response) {
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Order accepted!',
                            text: response.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                        const preparingList = $('#preparing');
                        row.fadeOut(500, function() {
                            $(this).appendTo(preparingList).fadeIn(500);
                            $(this).find('.btn-accept, .btn-reject').remove();
                        });
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Server error occurred.', 'error');
                }
            });
        }
    });
});




    });
</script>

</body>
</html>