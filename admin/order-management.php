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
<div class="modal fade" id="viewOrderModal" tabindex="-1" aria-labelledby="simpleModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      
      <!-- Header -->
      <div class="modal-header">
        <h5 class="modal-title" id="simpleModalLabel">Order Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <!-- Body -->
      <div class="modal-body">

        <div class="order-items"></div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
                      type: 'POST',
                      data: { 
                        ref: "update_order_stats",
                        id: orderId,
                        status: newStatus,
                        orderType: orderType,
                      },
                      success: function(response) {
                        // console.log('Updated:', response);
                      },
                      error: function(xhr, status, error) {
                        // console.error('AJAX Error:', error);
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