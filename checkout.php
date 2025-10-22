<?php
session_start();
    require_once('./classes/database.php');

    $db = new Database();

    if (!isset($_SESSION['customer_ID'])) {
    header("Location: login.php");
    exit();
}

$customerID = $_SESSION['customer_ID'];
$customerID = $_SESSION['customer_ID'];

//Fetch cart items with correct aliases
$stmt = $db->conn->prepare("
    SELECT cart.*, product.product_name, product.product_price 
    FROM cart 
    INNER JOIN product ON cart.product_id = product.product_id
    WHERE cart.customer_id = :customer_id
");

    $stmt->execute([':customer_id' => $customerID]);
    $cart = $stmt->fetchAll(PDO::FETCH_ASSOC);

$customer_name = $_SESSION['customer_FN'] ?? 'Guest';
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Checkout | Izana Coffee</title>

<!-- Bootstrap & SweetAlert -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Google Font: Montserrat -->
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<link rel="icon" type="image/svg+xml" href="uploads/icon.svg">

<style>
:root {
  --accent: #b07542;
  --accent-dark: #8a5c33;
  --gray-dark: #1e1e1e;
  --gray-mid: #2b2b2b;
  --gray-light: #444;
  --text-light: #f5f5f5;
  --success: #28a745;
}

body {
  margin: 0;
  font-family: 'Montserrat', sans-serif;
  color: var(--text-light);
  background: url('uploads/bgg.jpg') no-repeat center center fixed;
  background-size: cover;
  min-height: 100vh;
}

/* Navbar */
.navbar-custom {
  background: var(--gray-dark);
  border-bottom: 1px solid var(--gray-light);
}

.navbar-brand {
  color: var(--accent) !important;
  font-weight: 800;
  font-size: 2rem;
}

/* Back Button */
.btn-back {
  background: transparent;
  color: var(--accent);
  border: 2px solid var(--accent);
  border-radius: 30px;
  padding: 6px 18px;
  font-weight: 600;
  transition: .2s;
}

.btn-back:hover {
  background: var(--accent);
  color: #fff;
}

/* Checkout Box */
.checkout-container {
  max-width: 1000px;
  margin: 120px auto 60px;
  padding: 30px;
  background: var(--gray-mid);
  border: 1px solid var(--gray-light);
  border-radius: 18px;
  box-shadow: 0 10px 28px rgba(0, 0, 0, .5);
}

/* Title */
.checkout-title {
  font-weight: 800;
  font-size: 2.3rem;
  text-align: center;
  color: var(--accent);
  margin-bottom: 35px;
  letter-spacing: 1px;
}

/* Table */
.table {
  background: var(--gray-dark);
  color: var(--text-light);
  border-radius: 12px;
  overflow: hidden;
}

.table thead {
  background: var(--accent-dark);
  color: #fff;
  font-weight: 600;
}

.table td, .table th {
  vertical-align: middle;
  border-color: var(--gray-light) !important;
  font-size: 0.95rem;
}

/* Total */
#totalDisplay {
  font-size: 1.3rem;
  font-weight: 700;
  color: var(--success);
}

/* Checkout Button */
.btn-place-order {
  background: var(--accent);
  color: white;
  font-weight: 700;
  border-radius: 30px;
  padding: 12px;
  width: 100%;
  transition: .2s;
  letter-spacing: 0.5px;
}

.btn-place-order:hover {
  background: var(--accent-dark);
  transform: translateY(-2px);
}

/* Payment Options */
.payment-label {
  font-weight: 600;
  margin-top: 15px;
  letter-spacing: 0.3px;
}

/* Inputs */
.form-select, .form-control {
  background: var(--gray-dark);
  border: 1px solid var(--gray-light);
  color: var(--text-light);
  font-weight: 500;
}

.form-select option {
  color: #000;
}

/* Alerts */
.alert-info {
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid var(--gray-light);
  color: var(--text-light);
  font-weight: 500;
}

/* Responsive */
@media (max-width: 768px) {
  .checkout-container {
    margin: 90px 15px;
    padding: 20px;
  }

  .checkout-title {
    font-size: 1.8rem;
  }

  .btn-place-order {
    font-size: 0.95rem;
    padding: 10px;
  }

  .navbar-brand img {
    height: 60px;
  }
}
</style>
</head>
<body>


<nav class="navbar navbar-expand-lg navbar-dark navbar-custom fixed-top">
    <div class="container-fluid" style="max-width:1400px;">
       <img src="uploads/izana_logo.png" alt="IZANA Logo" style="height: 80px;" >
        <button class="btn-back ms-auto" id="backBtn"><i class="fas fa-arrow-left me-2"></i>Back</button>
    </div>
</nav>


<div class="checkout-container">
    <h2 class="checkout-title">Checkout Summary</h2>

     <div class="alert alert-info mt-3">
        <i class="fas fa-info-circle me-2"></i>
        <strong>Note:</strong> For your online order, please note that it's <b>pickup only</b> , and you can easily pay the total amount by <b>scanning the QR code</b> or sending it to the <b>Gcash account</b> provided. <b>And then input the reference number together with the proof of payment</b>.
    </div>

    <div class="row g-4">
    <div class="col-lg-8 col-md-7 col-12 order-1">
        <!-- Order Summary Section -->
        <p><strong>Customer:</strong> <?= htmlspecialchars($customer_name); ?></p>

        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?>">
                <?= htmlspecialchars($flash['message']); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($cart)): ?>
            <div class="alert alert-warning text-center">
                Your cart is empty. Please go back to the <a href="menu.php" class="alert-link">menu</a>.
            </div>
        <?php else: ?>
            <form action="place_order.php" method="post" enctype="multipart/form-data">
                <input type="hidden" name="order_channel" value="online">

                <div class="table-responsive">
                    <table class="table table-bordered align-middle text-center">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th>Price (₱)</th>
                                <th>Qty</th>
                                <th>Subtotal (₱)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $total = 0;
                            foreach ($cart as $item):
                                $subtotal = $item['product_price'] * $item['qty'];
                                $total += $subtotal; ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['product_name']); ?></td>
                                    <td>₱<?= number_format($item['product_price'], 2); ?></td>
                                    <td><?= (int)$item['qty']; ?></td>
                                    <td>₱<?= number_format($subtotal, 2); ?></td>
                                </tr>
                            <?php endforeach; ?>    

                            <tr>
                                <td colspan="3" class="text-end fw-bold">Total:</td>
                                <td class="fw-bold text-success">₱<?= number_format($total, 2); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <!-- PAYMENT / UPLOAD SECTION AFTER ORDERS -->

<div class="col-lg-4 col-md-5 col-12 order-2 text-center">
    <img src="uploads/Izana Qr.JPEG" alt="GCash QR Code" class="img-fluid mb-3" style="max-width:200px; border-radius:10px;">
    
    <h6 class="fw-bold mb-1">GCash Account Name:</h6>
    <p class="mb-2" style="color:#b07542;">JOHN CLARENZ.</p>

    <h6 class="fw-bold mb-1">GCash Number:</h6>
    <p class="mb-3" style="color:#b07542;">0966-540-4987</p>

    <div class="text-start">
        <label for="ref_no" class="form-label">Reference (Optional)</label>
        <input type="text" class="form-control mb-3" id="ref_no" name="ref_no" placeholder="Enter Reference">

        <!-- 🕒 Pickup Time -->
        <label for="pickup_time" class="form-label">Set Pickup Time</label>
        <input type="time" class="form-control mb-3" id="pickup_time" name="pickup_time" required>

        <label for="pop" class="form-label">Upload Proof of Payment</label>
        <input type="file" class="form-control mb-3" id="pop" name="pop" accept=".jpg,.jpeg,.png" required>

        
        <button type="button" class="btn btn-place-order w-100">Place Order</button>
    </div>
</div>


</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

<script>

$(document).ready(function() {
    
    $(document).on('click','#backBtn', function(){
        window.location.href = 'menu.php'
    });
});


//  $(document).on('click','.btn-place-order', function(){
    

//         let ref_no = $("#ref_no") .val();
//         let pop = $("#pop") [0];
//         let file = pop.files[0];

//             if(ref_no == '' || !file) {
//                 Swal.fire({
//                     icon: 'error',
//                     title: 'Reference Fields',
//                     text: 'Please input reference number and Proof of Payment.',
//                     timer: 3000,
//                     showConfirmButton: false
//                 });
//                 return;
//             } else {

//             let formData = new FormData();
//             formData.append("ref", "place_order");
//             formData.append("ref_no", ref_no);     
//             formData.append("pop", file);          
            
//             $.ajax({
//                 url: "functions.php",
//                 method: "POST",
//                 data: formData,
//             contentType: false,
//                 processData: false,
//                 dataType: 'json',
//                 success: function(response) {
                    
//                 }
//             });
//         }
// });


$(document).on('click', '.btn-place-order', function() {
    let ref_no = $("#ref_no").val().trim();
    let pop = $("#pop")[0];
    let file = pop.files[0];
    let pickup_time = $("#pickup_time").val(); // ✅ get time

    if (!file) {
        Swal.fire({
            icon: 'error',
            title: 'Missing Proof',
            text: 'Please upload your Proof of Payment.',
            timer: 1500,
            showConfirmButton: false
        });
        return;
    }

    Swal.fire({
        title: 'Confirm Order?',
        text: "Make sure all details are correct before proceeding.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#b07542',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Place Order',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {

            let formData = new FormData();
            formData.append("ref", "place_order");
            formData.append("ref_no", ref_no || 'N/A');
            formData.append("pop", file);
            formData.append("pickup_time", pickup_time || ''); // ✅ send time

            $.ajax({
                url: "functions.php",
                method: "POST",
                data: formData,
                contentType: false,
                processData: false,
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Order Placed!',
                            text: 'Your order has been submitted successfully.',
                            timer: 1800,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.href = 'menu.php';
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Order Failed',
                            text: response.message || 'Something went wrong. Please try again.',
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    console.log('Response:', xhr.responseText);
                    Swal.fire('Error', 'Unable to connect to the server.', 'error');
                }
            });
        }
    });
});




</script>

</body>
</html>
