<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Drag and Drop Orders</title>

  <!-- jQuery + jQuery UI -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

  <style>
    body { background: #f8f9fa; padding: 30px; }
    .list-group { min-height: 200px; }
    .list-group-item { cursor: move; }
    .placeholder-highlight { border: 2px dashed #6c757d; background: #e9ecef; }
  </style>
</head>
<body>
  <div class="container-fluid">
    <div class="row">
      <div class="col-md-3">
        <ul class="list-group" id="pending">
          <li class="list-group-item bg-success fw-bold text-white">Pending</li>
          <li class="list-group-item">Order 1</li>
          <li class="list-group-item">Order 2</li>
          <li class="list-group-item">Order 3</li>
        </ul>
      </div>
      <div class="col-md-3">
        <ul class="list-group" id="preparing">
          <li class="list-group-item bg-success fw-bold text-white">Preparing</li>
          <li class="list-group-item">Order 4</li>
          <li class="list-group-item">Order 5</li>
        </ul>
      </div>
      <div class="col-md-3">
        <ul class="list-group" id="ready">
          <li class="list-group-item bg-success fw-bold text-white">Ready for Pickup</li>
          <li class="list-group-item">Order 6</li>
        </ul>
      </div>
      <div class="col-md-3">
        <ul class="list-group" id="cancel">
          <li class="list-group-item bg-success fw-bold text-white">Cancel</li>
          <li class="list-group-item">Order 7</li>
        </ul>
      </div>
    </div>
  </div>

  <script>
    $(function() {
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
          const newStatus = $(this).find('li:first').text();
          console.log(`${orderName} moved to ${newStatus}`);
        }
      }).disableSelection();
    });
  </script>
</body>
</html>