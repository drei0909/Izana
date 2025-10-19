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
    .btn-rejected {
    opacity: 0.9;
    cursor: not-allowed;

    .rejected-order {
    background-color: #ffe5e5 !important;
    border-left: 5px solid #dc3545 !important;
    color: #b30000 !important;
  }
  .bg-light-danger {
    background-color: #fff0f0 !important;
  }

  }

  </style>

  <!-- modal -->
  <!-- Improved View Order Modal -->
  <div class="modal fade" id="viewOrderModal" tabindex="-1" aria-labelledby="viewOrderLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg rounded-4">

        <!-- Header -->
        <div class="modal-header bg-dark text-light">
          <h5 class="modal-title fw-semibold" id="viewOrderLabel">
            <i class="fas fa-receipt me-2"></i>Order Details
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <!-- Body -->
        <div class="modal-body bg-light">
          <div class="border rounded bg-white p-3 shadow-sm mb-3">
            <div class="row g-3">
              <div class="col-md-6">
                <p class="mb-1"><strong>Customer:</strong> <span class="customer-name"></span></p>
                <p class="mb-1"><strong>Contact No:</strong> <span class="con-no"></span></p>
                <p class="mb-1"><strong>Reference No:</strong> <span class="ref-no"></span></p>
              </div>
              <div class="col-md-6">
                <p class="mb-1"><strong>Pickup Time:</strong> <span class="pickup_time"></span></p>
                <p class="mb-1"><strong>Status:</strong> <span class="badge bg-secondary order-status">Pending</span></p>
                <p class="mb-1"><strong>Total Amount:</strong> <span class="fw-bold text-success total-amount">₱0.00</span></p>
              </div>
            </div>
          </div>

          <h6 class="fw-bold border-bottom pb-1 mb-2"><i class="fas fa-mug-hot me-1 text-secondary"></i>Ordered Items</h6>
          <div class="order-items mb-3"></div>

          <div class="receipt text-center mt-4">
            <p class="fw-bold mb-2">Receipt:</p>
            <p class="text-muted small">No receipt uploaded.</p>
          </div>
        </div>

        <!-- Footer -->
        <div class="modal-footer bg-white border-top d-flex justify-content-between">
          <button class="btn btn-danger btn-sm btn-cancel-order">
            <i class="fas fa-times me-1"></i> Void
          </button>
          <button class="btn btn-success btn-sm btn-complete-order">
            <i class="fas fa-check me-1"></i> Complete
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

  // //reject newly added
  //   $(document).on('click', '.btn-reject', function() {
  //     const orderId = $(this).data('id');
  //     const orderType = $(this).data('type');

  //     // Store IDs in modal
  //     $('#rejectOrderId').val(orderId);
  //     $('#rejectOrderType').val(orderType);
  //     $('#rejectReasonDropdown').val(''); // reset dropdown
  //     $('#rejectReasonText').val(''); // reset text area

  //     $('#rejectOrderModal').modal('show');
  // });


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

  // Submit reason and show confirmation okay na rin to
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

      Swal.fire({
          title: 'Are you sure you want to reject this order?',
          text: "The customer will be notified and can repay.",
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Yes, reject it!',
          cancelButtonText: 'No',
          confirmButtonColor: '#dc3545'
      }).then((result) => {
          if (result.isConfirmed) {
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
                              text: 'The customer has been notified. They can repay this order.',
                              timer: 1500,
                              showConfirmButton: false
                          });

                          // Mark the order as rejected visually
                          let row = $(".order-item[data-id='" + rejectOrderId + "']");
                          row.addClass('bg-danger text-white');       // red highlight
                          row.find('.status-label').text('Rejected'); // optional
                          row.find('.btn-accept, .btn-reject').remove(); // remove buttons

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
          title: 'Void this order?',
          text: 'This order will be moved to Void Orders in Sales Report.',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Yes, Void it!',
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





  let repaymentInterval = null;

 function viewOrder(orderId, orderType) {
  // Reset modal UI
  $("#viewOrderModal").modal("show");
  $(".order-items").html("<p class='text-center text-muted'>Loading...</p>");
  $(".customer-name, .ref-no, .receipt, .con-no, .pickup_time, .total-amount").html("—");
  $(".order-status")
    .removeClass("bg-danger bg-success bg-warning bg-secondary text-dark")
    .addClass("bg-secondary")
    .text("Loading...");

  if (typeof repaymentInterval !== "undefined" && repaymentInterval) {
    clearInterval(repaymentInterval);
  }

  $.ajax({
    url: "admin_functions.php",
    method: "POST",
    data: { ref: "get_order_item", order_id: orderId, order_type: orderType },
    dataType: "json",
    success: function (response) {
      if (response.status === "success") {
        $(".order-items").html(response.html);

        // ✅ Display computed total amount directly from PHP
        $(".total-amount").text("₱" + (response.total_amount || "0.00"));

        $(".btn-cancel-order, .btn-complete-order")
          .attr("data-order-id", orderId)
          .attr("data-order-type", orderType);

        // Fetch order info
        $.ajax({
          url: "admin_functions.php",
          method: "POST",
          data: { ref: "get_order_info", order_id: orderId },
          dataType: "json",
          success: function (info) {
            if (info.status !== "success") return;

            // Display order details
            $(".customer-name").text(`${info.customer_FN} ${info.customer_LN}`);
            $(".ref-no").text(info.ref_no || "N/A");
            $(".con-no").text(info.customer_contact || "N/A");
            $(".pickup_time").text(info.pickup_time || "N/A");

            // If backend total differs (optional override)
            if (!response.total_amount && info.total_amount) {
              $(".total-amount").text(`₱${parseFloat(info.total_amount || 0).toFixed(2)}`);
            }

            // Handle order status display
            let statusClass = "bg-secondary",
                statusText = info.status || "Pending";

            switch (info.status) {
              case "Rejected":
                statusClass = "bg-danger";
                statusText = "Rejected — Waiting for Repayment";
                break;
              case "Pending":
                statusClass = "bg-warning text-dark";
                break;
              case "Completed":
                statusClass = "bg-success";
                break;
            }

            $(".order-status")
              .removeClass("bg-danger bg-success bg-warning bg-secondary text-dark")
              .addClass(statusClass)
              .text(statusText);

            // // Show receipt
            // $(".receipt").empty().html(
            //   info.receipt
            //     ? `<p class="fw-bold">Receipt:</p>
            //       <img src="../uploads/receipts/${info.receipt}"
            //             class="img-fluid rounded shadow-sm border"
            //             style="max-width:350px;cursor:zoom-in"
            //             onclick="window.open(this.src)">`
            //     : `<p class="text-muted small">No receipt uploaded.</p>`
            // );

            // Auto detect repayment
            if (info.status === "Rejected") {
              repaymentInterval = setInterval(() => {
                $.ajax({
                  url: "admin_functions.php",
                  method: "POST",
                  data: { ref: "check_repayment", order_id: orderId },
                  dataType: "json",
                  success: function (r) {
                    if (r.status === "repaid") {
                      clearInterval(repaymentInterval);

                      $(".order-status")
                        .removeClass("bg-danger")
                        .addClass("bg-warning text-dark")
                        .text("Pending Review (New Receipt)");

                      $(".receipt").html(`
                        <p class="fw-bold">New Receipt:</p>
                        <img src="../uploads/receipts/${r.new_receipt}"
                            class="img-fluid rounded shadow-sm border"
                            style="max-width:350px;cursor:zoom-in"
                            onclick="window.open(this.src)">
                      `);

                      Swal.fire({
                        icon: "info",
                        title: "Repayment Detected",
                        text: "Customer uploaded a new receipt.",
                        timer: 2500,
                        showConfirmButton: false,
                      });
                    }
                  },
                });
              }, 8000); // check every 8 seconds
            }
          },
        });
      } else {
        $(".order-items").html("<p class='text-danger text-center'>Unable to load order items.</p>");
      }
    },
    error: function () {
      $(".order-items").html("<p class='text-danger text-center'>Server error occurred.</p>");
    },
  });
}


  // ✅ Stop auto-check when modal closes
  $("#viewOrderModal").on("hidden.bs.modal", function () {
    if (repaymentInterval) clearInterval(repaymentInterval);
  });

  // ✅ Complete Order button
  $(document).on("click", ".btn-complete-order", function () {
    const orderId = $(this).data("order-id");
    const orderType = $(this).data("order-type");
    const row = $(".order-item[data-id='" + orderId + "']");

    Swal.fire({
      title: "Mark as Completed?",
      text: "This order will be added to Sales Report as completed.",
      icon: "question",
      showCancelButton: true,
      confirmButtonText: "Yes, Complete it!",
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: "admin_functions.php",
          method: "POST",
          data: { ref: "complete_order", order_id: orderId, order_type: orderType },
          dataType: "json",
          success: function (response) {
            if (response.status === "success") {
              Swal.fire({
                icon: "success",
                title: "Order Completed!",
                text: response.message,
                timer: 1500,
                showConfirmButton: false,
              });
              row.fadeOut(500, function () {
                $(this).remove();
              });
              $("#viewOrderModal").modal("hide");
            } else {
              Swal.fire("Error", response.message, "error");
            }
          },
          error: function () {
            Swal.fire("Error", "Unable to complete order.", "error");
          },
        });
      }
    });
  });



  // Trigger modal on double-click or button
  $(document).on("dblclick", ".order-item, .btn-view-order", function() {
      const orderId = $(this).data("order-id") || $(this).data("id");
      const orderType = $(this).data("order-type");

      viewOrder(orderId , orderType);
  });

  function refreshOrderQue() {
    $.ajax({
      url: "admin_functions.php",
      method: "POST",
      data: { ref: "get_orders_que" },
      dataType: "json",
      success: function (response) {
        console.log(response);

        if (response.status === "success") {
          // Load all order queues
          $(".load-order-que").html(response.html);

          // === Initialize sortable lists ===
          $(".list-group")
            .sortable({
              connectWith: ".list-group",
              items: "> li:not(:first-child)", // skip headers
              placeholder: "placeholder-highlight",
              helper: "clone",

              start: function (e, ui) {
                const orderStatus = ui.item.data("status");
                const currentListId = ui.item.closest(".list-group").attr("id");

                ui.placeholder.height(ui.item.outerHeight());
                ui.placeholder.width(ui.item.outerWidth());

                // Prevent dragging under review orders
                if (currentListId === "review" && (orderStatus === "pending" || orderStatus === 1)) {
                  $(this).sortable("cancel");
                  Swal.fire({
                    icon: "warning",
                    title: "Action Not Allowed!",
                    text: "You can't drag this order. Accept or reject it first.",
                    timer: 2000,
                    showConfirmButton: false,
                  });
                }
              },

              receive: function (event, ui) {
                const targetListId = $(this).attr("id");
                const orderStatus = ui.item.data("status");

                // Prevent moving pending/review orders
                if (orderStatus === "pending" || orderStatus === "review") {
                  $(ui.sender).sortable("cancel");
                  Swal.fire({
                    icon: "warning",
                    title: "Action blocked!",
                    text: "You can't move this order. Accept or reject it first.",
                    timer: 2000,
                    showConfirmButton: false,
                  });
                  return;
                }

                // Prevent moving back to Review
                if (targetListId === "review") {
                  $(ui.sender).sortable("cancel");
                  Swal.fire({
                    icon: "warning",
                    title: "Not allowed!",
                    text: "Orders cannot be moved back to Review.",
                    timer: 1000,
                    showConfirmButton: false,
                  });
                  return;
                }

                // Update status for valid moves
                const orderId = ui.item.data("id");
                const orderType = ui.item.data("order-type");
                const newStatusText = $(this).find("li:first").text();

                let newStatus = 0;
                switch (newStatusText) {
                  case "Review":
                    newStatus = 1;
                    break;
                  case "Preparing":
                    newStatus = 2;
                    break;
                  case "Ready for Pickup":
                    newStatus = 3;
                    break;
                  case "Completed":
                    newStatus = 4;
                    break;
                }

                $.ajax({
                  url: "admin_functions.php",
                  type: "POST",
                  data: {
                    ref: "update_order_stats",
                    id: orderId,
                    status: newStatus,
                    orderType: orderType,
                  },
                  dataType: "json",
                  success: function (res) {
                    if (res.status === "success") {
                      ui.item.attr("data-status", newStatus);
                      Swal.fire({
                        toast: true,
                        position: "top-end",
                        icon: "success",
                        title: `Order #${orderId} updated to ${newStatusText}`,
                        showConfirmButton: false,
                        timer: 1500,
                      });
                    } else {
                      $(ui.sender).sortable("cancel");
                      Swal.fire("Error", res.message || "Failed to update order.", "error");
                    }
                  },
                  error: function () {
                    $(ui.sender).sortable("cancel");
                    Swal.fire("Error", "Unable to update order status.", "error");
                  },
                });
              },
            })
            .disableSelection();

          // 🔥 Highlight rejected orders and auto-detect repayment
          highlightRejectedOrders();
        }
      },
      error: function () {
        console.error("Failed to refresh order queue.");
      },
    });
  }

  // ✅ Separate function for rejected orders visual state
  function highlightRejectedOrders() {
    $(".order-item").each(function () {
      const $order = $(this);
      const hasRepayReceipt = $order.find("img[src*='repay']").length > 0;

      // If it's rejected but no repay uploaded yet, mark red
      if ($order.hasClass("rejected-order") && !hasRepayReceipt) {
        $order.addClass("bg-danger text-white border-0");
      }

      // If repay uploaded, remove red highlight
      if (hasRepayReceipt) {
        $order.removeClass("bg-danger text-white border-0");
        $order.addClass("bg-success-subtle border-success");
      }
    });
  }


  // Auto refresh queue every 5 seconds
  setInterval(refreshOrderQue, 5000);

  // Load immediately on page load
  if ($(".load-order-que").length) {
    refreshOrderQue();
  }


  // Accept button only okaay na to
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