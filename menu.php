<?php
session_start();

require_once('./classes/database.php');
require_once (__DIR__. "/classes/config.php");

$db = new Database();

if (!isset($_SESSION['customer_ID'])) {
header("Location: login.php");
exit();
}


$customer_id = $_SESSION['customer_ID']; // assign session value first

$stmt = $db->conn->prepare("SELECT status FROM customer WHERE customer_id = ?");
$stmt->execute([$customer_id]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);
$isBlocked = ($customer && strtolower($customer['status']) === 'blocked');

// Check if the account is blocked
$stmt = $db->conn->prepare("SELECT status, block_reason FROM customer WHERE customer_id = ?");

$stmt->execute([$_SESSION['customer_ID']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && $user['status'] === 'blocked') {
    session_destroy();
    echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    <script>
      Swal.fire({
        icon: 'error',
        title: 'Access Denied',
        html: `<p>Your account has been blocked by the admin.</p>
               <p><strong>Reason:</strong> " . addslashes($user['block_reason']) . "</p>`,
        confirmButtonColor: '#d33'
      }).then(() => window.location.href = 'login.php');
    </script>";
    exit();
}

// Check if category_id is set in the URL, if not, set it to null or a default value
$categoryId = isset($_GET['category_id']) ? $_GET['category_id'] : null;
// Fetch products based on the category_id, or return an empty array if it's null
$products = $db->getAllProducts($categoryId) ?? [];
// Fetch product categories (this part remains unchanged)
$stmt = $db->conn->prepare("SELECT * FROM product_categories WHERE is_active = 1");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);



$grouped = [];
foreach ($products as $p) {
$cat = $p['category_id'] ?? 'Other';
$catKey = is_string($cat) ? $cat : (string)$cat;
$grouped[$catKey][] = [
'product_id' => (int)($p['product_id'] ?? 0),
'product_name' => $p['product_name'] ?? 'Unnamed',
'product_price' => (float)($p['product_price'] ?? 0),
'best' => isset($p['best']) ? (bool)$p['best'] : (($p['product_categoy'] ?? '') == 1),
'stock' => $p['stock_quantity'] ?? 0,
'status' => $p['is_active'] ?? 1,
'raw' => $p
  ];
}

function escape($s) {
  return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function card_html($p) {
  // ensure expected values exist
  $id = (int)($p['product_id'] ?? 0);
  $name = escape($p['product_name'] ?? 'Unnamed');
  $priceFmt = number_format((float)($p['product_price'] ?? 0), 2);
  $best = !empty($p['best']) ? true : false;
  $status = (int)($p['status'] ?? 1);
  $isActive = $p['is_active'] ?? 1;
  $isBlocked = $GLOBALS['isBlocked'];

  // Disable if product inactive OR customer blocked
  $disabledAttr = ($status === 0 || $isBlocked) ? 'disabled' : '';
  $inactiveClass = ($status === 0 || $isBlocked) ? 'faded' : '';

  // try to read image path from raw data if present, fallback to placeholder
  $img = 'uploads/default.jpg';
  if (!empty($p['raw']['image'])) {              
      $img = escape($p['raw']['image']);
  } elseif (!empty($p['raw']['image_path'])) {
      $img = escape($p['raw']['image_path']);
  }

  $bestLabel = $best ? '<span class="badge-best">Best Seller</span>' : '';
  $btnHtml = '<button class="btn btn-coffee mt-2" ' . $disabledAttr . '>Add</button>';

  $dataPrice = htmlspecialchars((string)($p['product_price'] ?? '0'), ENT_QUOTES);
  $dataName  = htmlspecialchars($name, ENT_QUOTES);

  $html  = '<div class="col-12 col-sm-6 col-lg-4">';
  $html .= '<div class="menu-card ' . $inactiveClass . '" data-product-id="' . $id . '" data-product-price="' . $dataPrice . '" data-product-name="' . $dataName . '">';
  $html .= '<div class="card-media">';
  $html .= '<img src="' . $img . '" alt="' . $dataName . '">';
  $html .= $bestLabel;
  $html .= '</div>'; // card-media
  $html .= '<div class="menu-body">';
  $html .= '<div class="menu-name">' . $name . '</div>';
  $html .= '<div class="menu-bottom">';
  $html .= '<div class="menu-price">₱' . $priceFmt . '</div>';
  $html .= '<div class="controls">';
$html .= '<input type="number" min="1" max="99" value="1" class="quantity-input" ' . $disabledAttr . ' onkeydown="return false;">';
  $html .= $btnHtml;
  $html .= '</div>'; // controls
  $html .= '</div>'; // menu-bottom
  $html .= '</div>'; // menu-body
  $html .= '</div>'; // menu-card
  $html .= '</div>'; // column

  return $html;
}
?>

<!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="utf-8">
    <title>Menu | Izana Coffee</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&display=swap" rel="stylesheet">

    <style>
    @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap');

    :root {
      --accent: #b07542;
      --accent-dark: #8a5c33;
      --bg-dark: #1a1a1a;
      --bg-mid: #232323;
      --bg-light: #2f2f2f;
      --text-light: #f5f5f5;
      --text-muted: #cfcfcf;
      --shadow: 0 8px 20px rgba(0, 0, 0, 0.4);
      --glass: rgba(255, 255, 255, 0.06);
    }

   
    body {
      margin: 0;
      font-family: 'Montserrat', sans-serif;
      color: var(--text-light);
      background: url('uploads/bgg.jpg') center/cover fixed no-repeat;
      position: relative;
    }

    body::before {
      content: '';
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.55);
      z-index: -1;
    }


    .navbar-custom {
      background: var(--bg-mid);
      backdrop-filter: blur(8px);
      border-bottom: 1px solid rgba(255,255,255,0.1);
      box-shadow: var(--shadow);
      transition: background 0.3s ease;
    }

    .navbar-brand {
      color: var(--accent) !important;
      font-weight: 800;
      font-size: 2rem;
      letter-spacing: 1px;
      text-transform: uppercase;
    }

    .navbar-brand:hover {
      color: var(--accent-dark) !important;
    }

    .navbar-custom .btn {
    width: 46px;
    height: 46px;
    border-radius: 50%;
    font-size: 1.2rem;
    background: var(--accent);
    color: #fff;
    transition: all 0.3s ease;
  }
 

    .navbar-custom .dropdown-menu {
    background: var(--bg-light);
    border-radius: 12px;
    box-shadow: var(--shadow);
    min-width: auto;
  }
    .navbar-custom .dropdown-item {
      color: var(--text-light);
      transition: 0.3s;
    }
    .navbar-custom .dropdown-item:hover {
      background: var(--accent);
      color: #fff;
    }


    .container-menu {
      max-width: 1400px;
      margin: 120px auto 60px;
    }

    .layout {
      display: flex;
      gap: 30px;
      flex-wrap: wrap;
    }


    .sidebar {
      flex: 0 0 270px;
      padding: 25px;
      background: var(--glass);
      border: 1px solid rgba(255,255,255,0.1);
      border-radius: 16px;
      height: calc(85vh);
      position: sticky;
      top: 120px;
      overflow-y: auto;
      backdrop-filter: blur(12px);
      box-shadow: var(--shadow);
      transition: all 0.3s ease;
    }

    .sidebar::-webkit-scrollbar {
      width: 6px;
    }
    .sidebar::-webkit-scrollbar-thumb {
      background: var(--accent);
      border-radius: 6px;
    }

    .sidebar h5 {
      color: var(--accent);
      font-weight: 900;
      font-size: 1.8rem;  
      text-transform: uppercase;
      letter-spacing: 1px;
      margin-bottom: 25px;
      text-align: center; 
      text-shadow: 0 2px 6px rgba(0,0,0,0.3); 
    }




      .sidebar a {
        display: block;
        color: var(--text-muted);
        padding: 12px 14px;
        border-radius: 10px;
        text-decoration: none;
        font-weight: 600;
        font-size: 1rem;
        margin-bottom: 6px;
        transition: all 0.3s ease;
      }
      .sidebar a:hover, .sidebar a.active {
        background: var(--accent);
        color: #fff;
        transform: translateX(5px);
      }

    
      main.content {
        flex: 1;
      }

      .page-title {
        text-align: center;
        font-weight: 800;
        font-size: 2.5rem;
        margin-bottom: 40px;
        color: var(--text-light);
        text-shadow: 0 2px 6px rgba(0, 0, 0, 0.4);
        letter-spacing: 1px;
      }

      .controls {
      display: flex;
      align-items: center;
      gap: 10px; 
    }

    .quantity-input {
      width: 60px;
      text-align: center;
      border: 1px solid rgba(255,255,255,0.2);
      border-radius: 8px;
      background: rgba(255,255,255,0.05);
      color: #fff;
      font-weight: 600;
      padding: 6px 4px;
    }

    .quantity-input:disabled {
      opacity: 0.5;
    }



      .menu-card {
        background: var(--glass);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 16px;
        overflow: hidden;
        box-shadow: var(--shadow);
        transition: all 0.35s ease;
        display: flex;
        flex-direction: column;
        backdrop-filter: blur(10px);
      }
     
      .card-media {
        position: relative;
        height: 200px;
      }
      .card-media img {
        width: 100%;
        height: 100%;
        object-fit: cover;
      }
      .badge-best {
        position: absolute;
        top: 12px;
        left: 12px;
        background: linear-gradient(135deg, #b07542, #d89b5c);
        color: #fff;
        padding: 5px 12px;
        border-radius: 20px;
        font-weight: 700;
        box-shadow: 0 3px 6px rgba(0,0,0,0.3);
      }

      .menu-body {
        flex: 1;
        padding: 16px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
      }

      .menu-name {
        font-weight: 800;
        font-size: 1.1rem;
        color: #fff;
        line-height: 1.3;
        margin-bottom: 8px;
        height: 42px;
        overflow: hidden;
        text-overflow: ellipsis;
        -webkit-line-clamp: 2;
        display: -webkit-box;
        -webkit-box-orient: vertical;
      }


      

  
    .menu-bottom {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: auto;
      gap: 12px;
      flex-wrap: wrap;
    }

   
    .menu-price {
      color: var(--accent);
      font-weight: 800;
      font-size: 1.15rem;
      white-space: nowrap;
    }

    
    .quantity-input {
      width: 60px;
      height: 38px;
      text-align: center;
      border-radius: 8px;
      border: 1px solid rgba(255,255,255,0.2);
      background: var(--bg-light);
      color: var(--text-light);
      font-weight: 600;
      font-size: 0.95rem;
      outline: none;
      transition: border-color 0.3s ease, background 0.3s ease;
    }
    .quantity-input:focus {
      border-color: var(--accent);
      background: var(--bg-mid);
    }

   
    .btn-coffee {
      background: var(--accent);
      color: #fff;
      border: none;
      padding: 8px 20px;
      border-radius: 25px;
      font-weight: 700;
      font-size: 0.95rem;
      transition: all 0.3s ease;
      box-shadow: 0 3px 8px rgba(0,0,0,0.3);
    }
    .btn-coffee:hover {
      background: var(--accent-dark);
      transform: translateY(-2px);
    }

  
    .menu-actions {
      display: flex;
      align-items: center;
      gap: 10px; 
    }


      
      .modal-content {
        background: var(--bg-mid);
        border-radius: 16px;
        border: 1px solid rgba(255,255,255,0.1);
        color: #fff;
      }
      .modal-header {
        background: var(--accent);
        color: #fff;
        font-weight: 700;
      }
      .modal-footer {
        background: var(--bg-mid);
        border-top: 1px solid rgba(255,255,255,0.1);
      }
      #cart-total {
        font-weight: 800;
        font-size: 1.6rem;
        text-align: right;
      }

      #notificationDropdown {
      position: absolute;
      top: 100%;
      right: 0;
      left: auto;
      transform: none;
      width: 90vw;
      max-width: 320px;
      max-height: 60vh;
      overflow-y: auto;
      border-radius: 12px;
      padding: 0.8rem 1rem;
      z-index: 1050;
      background-color: rgba(255,255,255,0.95);
    }

     
      @media (max-width: 1199px) {
        .layout {
          flex-direction: column;
        }
        .sidebar {
          width: 100%;
          height: auto;
          position: relative;
        }
        .navbar-brand {
          font-size: 1.8rem;
        }
      }

      @media (max-width: 767px) {
        .page-title {
          font-size: 2rem;
        }
        .menu-card {
          height: auto;
        }
        .btn-coffee {
          padding: 6px 14px;
        }
      }

      @media (max-width: 767px) {
    .navbar-brand img {
      height: 60px;
    }
    .navbar-custom .btn {
      width: 40px;
      height: 40px;
      font-size: 1rem;
    }
    .badge {
      font-size: 0.6rem;
      padding: 0.25em 0.45em;
    }
  }


</style>
</head>
<body>


<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark navbar-custom fixed-top">
<div class="container-fluid" style="max-width:1400px;">
  <a class="navbar-brand" href="#">
    <img src="uploads/izana_logo.png" alt="IZANA Logo" style="height: 80px;">
  </a>

  <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
    <span class="navbar-toggler-icon"></span>
  </button>

  <div class="collapse navbar-collapse justify-content-end" id="navMenu">
    <ul class="navbar-nav align-items-center">
      <!-- Notification Bell -->
      <li class="nav-item me-3 position-relative">
        <button class="btn  " id="btnNotification">
          <i class="fas fa-bell text-dark"></i>
          <span class="badge bg-danger rounded-pill position-absolute top-0 start-100 translate-middle" id="notificationCount" style="font-size: 0.7rem; display:none;">0</span>
        </button>

<!--Dropdown Notifications -->
<div class="dropdown-menu shadow border-0"
    id="notificationDropdown"
    style="
      position: absolute;
      top: 115%;
      left: 50%;
      transform: translateX(-50%);
      min-width: 320px;
      max-width: 340px;
      max-height: 320px;
      overflow-y: auto;
      border-radius: 20px;
      backdrop-filter: blur(10px);
      background-color: rgba(255, 255, 255, 0.8);
      z-index: 1050;
      box-shadow: 0 4px 16px rgba(0,0,0,0.1);
      padding: 0.8rem 1rem;
    ">

<!-- Header -->
<div class="fw-semibold text-center text-white py-2 mb-2"
      style="background-color: #b46c38; border-radius: 12px;">
  Notifications
</div>

<!-- Notification List -->
<div id="notificationList">
  <div class="notif-item">
    <i class="fas fa-coffee notif-icon"></i>
    <div class="notif-text">
      <strong>Your order #27 has been placed</strong><br>
      <small>and is now pending confirmation.</small>
      <div class="notif-time">2025-10-16 17:34:08</div>
    </div>
  </div>
</div>
</div>



      <!-- Cart -->
      <li class="nav-item me-3 position-relative">
        <button class="btn  position-relative" id="btnShowCartModal" data-bs-toggle="modal" data-bs-target="#cartModal">
          <i class="fas fa-shopping-cart"></i>
          <span class="badge bg-danger rounded-pill position-absolute top-0 start-100 translate-middle" id="cartCount" style="font-size: 0.7rem; display:none;">0</span>
        </button>
      </li>

      <!-- Dropdown Menu -->
  <li class="nav-item dropdown me-3 position-relative">
    <button class="btn position-relative" data-bs-toggle="dropdown" aria-expanded="false">
      <i class="fas fa-user-circle"></i>
    </button>
    <ul class="dropdown-menu dropdown-menu-end shadow">
      <li>
        <a class="dropdown-item" href="profile.php">
          <i class="fas fa-user me-2"></i>Profile
        </a>
      </li>
      <li>
        <a class="dropdown-item text-danger" href="Logout.php">
          <i class="fas fa-sign-out-alt me-2"></i>Logout
        </a>
      </li>
    </ul>
  </li>

    </ul>
  </div>
</div>
</nav>



<div class="container-menu">
  <div class="layout">
  <aside class="sidebar">

  <h5>Categories</h5>

<?php foreach ($categories as $category): 
  $active = ($categoryId == $category['category_id']) ? "active" : "";
?>
<a class="<?= $active ?>" 
    href="<?php echo BASE_URL ?>menu.php?category_id=<?= $category['category_id'] ?>">
    <?= htmlspecialchars($category['category']) ?>
</a>
<?php endforeach; ?>


  </aside>
  <main class="content">
  <div class="page-title">Coffee Menu</div>
    
<?php if(empty($categories)): ?>
  <div class="alert alert-light">No products categories available.</div>
<?php endif; ?>

<?php foreach($grouped as $catId=>$items): 
  // Get category name
  $stmt = $db->conn->prepare("SELECT category FROM product_categories WHERE category_id = ?");
  $stmt->execute([$catId]);
  $catRow = $stmt->fetch(PDO::FETCH_ASSOC);
  $catName = $catRow ? $catRow['category'] : "Other";

  $anchor = 'cat-'.preg_replace('/[^a-z0-9\-_]/i','-', strtolower($catName));
?>
  <section id="<?=escape($anchor)?>" class="mb-4">
    <h4 class="border-bottom pb-2 mb-3 text-light"><?=escape($catName)?></h4>
    <div class="row gy-3">
      <?php foreach($items as $item) echo card_html($item); ?>
    </div>
  </section>
<?php endforeach; ?>


        <div class="alert alert-danger text-center mt-4 account-status-msg">
          <i class="fas fa-ban me-1"></i> Your account is blocked. Ordering is disabled.
        </div>


  
 



  </main>
</div>
</div>

<!-- Cart Modal -->
<div class="modal fade" id="cartModal">
    <div class="modal-dialog modal-sm modal-dialog-scrollable">
    <div class="modal-content">
    
    <div class="modal-header"><h5 class="modal-title">Cart</h5><button 
    class="btn-close" data-bs-dismiss="modal"></button>
    </div>

    <div class="modal-body" id="cart-items">
    <div class="">Cart empty.</div>
    </div>

    <div class="modal-footer d-flex justify-content-between">
    <div id="cart-total">Total: ₱0</div>

    <button id="checkoutBtn" class="btn btn-success btn-sm">Checkout</button>

    </div>
  </div>
</div>



<!-- SCRIPTS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
$(document).ready(function() {

  // Update cart count badge
  function updateCartCount() {
    $.ajax({
      url: "functions.php",
      method: "POST",
      data: { ref: "get_cart_count" },
      dataType: "json",
      success: function(res) {
        if (res.status === "success") {
          if (res.count > 0) {
            $("#cartCount").text(res.count).show();
          } else {
            $("#cartCount").hide();
          }
        }
      }
    });
  }

// Load notifications
function loadNotifications() {
    $.ajax({
        url: "functions.php",
        method: "POST",
        data: { ref: "fetch_notifications" },
        dataType: "json",
        success: function(res) {
            if (res.status !== "success") return;

            let notifList = "";

            if (res.notifications.length > 0) {
                res.notifications.forEach(n => {
                    notifList += `
                    <div class="p-2 border-bottom ${n.is_read == 1 ? 'bg-white' : 'bg-light'}">
                        <small>${n.message}</small><br>
                       ${n.show_repay ? `
                        <small>
                            <span class="text-primary fw-bold repayText" 
                                  data-order-id="${n.order_id}" 
                                  style="cursor:pointer;">[Repay]</span>
                        </small><br>` : ''}

                        <small class="text-muted" style="font-size: 0.7rem;">${n.created_at}</small>
                    </div>`;
                });
            } else {
                notifList = '<p class="text-center text-muted small m-2">No notifications</p>';
            }

            $("#notificationList").html(notifList);
            if (res.unread_count > 0) {
                $("#notificationCount").text(res.unread_count).show();
            } else {
                $("#notificationCount").hide();
            }
        }
    });
}

// Repay order click
$(document).on("click", ".repayText", function() {
    let orderId = $(this).data("order-id");

    Swal.fire({
        title: 'Repay Order',
        html: `
            <div class="text-center mb-2">
                <p class="mb-1 text-muted" style="font-size:0.85rem;">Scan this QR to pay:</p>
                <img src="uploads/Izana Qr.JPG" alt="GCash QR" style="width:180px; border-radius:8px; border:1px solid #ccc;">
            </div>
            <hr>
            <p class="text-muted mb-1" style="font-size:0.85rem;">Then upload your receipt below:</p>
            <input type="file" id="receiptUpload" class="swal2-input" accept="image/*" style="width: auto;">
        `,
        confirmButtonText: 'Submit',
        showCancelButton: true,
        preConfirm: () => {
            const file = Swal.getPopup().querySelector('#receiptUpload').files[0];
            if (!file) Swal.showValidationMessage('Please select a receipt file');
            return file;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('ref', 'repay_order');
            formData.append('order_id', orderId);
            formData.append('receipt', result.value);

            $.ajax({
                url: 'functions.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'success') {
                        Swal.fire('Success', res.msg, 'success');
                        loadNotifications();
                    } else {
                        Swal.fire('Error', res.msg, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Something went wrong', 'error');
                }
            });
        }
    });
});




  // Initial account status check
  let account_status_msg = '<?= $isBlocked ?>';
  if(account_status_msg == '1'){ // If account is blocked
  $(".account-status-msg").show();
  }else{
  $(".account-status-msg").hide();
  }

 // Check account status periodically
function checkAccountStatus() {
  $.ajax({
    url: "functions.php",
    method: "POST",
    data: { ref: "check_acc_status" },
    dataType: "json",
    success: function(res) {
      if (res.status === "blocked") {
        Swal.fire({
          icon: "error",
          title: "Account Blocked",
          html: `
            <p>Your account has been blocked by the admin.</p>
            ${res.reason ? `<p><strong>Reason:</strong> ${res.reason}</p>` : ""}
          `,
          confirmButtonColor: "#d33"
        }).then(() => {
          window.location.href = "login.php"; // 🔥 Redirect after logout
        });
      }
    },
    error: function() {
      console.error("Error checking account status");
    }
  });
}


// Run every 5 seconds
setInterval(checkAccountStatus, 5000);


// Mark notifications as read
function markNotificationsRead() {
  $.ajax({
    url: "functions.php",
    method: "POST",
    data: { ref: "mark_notifications_read" },
    dataType: "json",
    success: function(res) {
      if (res.status === "success") {
        $("#notificationCount").hide(); // Hide badge immediately
        loadNotifications(); // Refresh list
    }
  }
});
}

// When user clicks the bell, mark as read
$("#notificationDropdown").on("click", function() {
markNotificationsRead();
});


// Load notifications when the page opens
$(document).ready(function() {
loadNotifications();

// Auto-refresh notifications every 5 seconds
// setInterval(
//   loadNotifications,
//   checkAccountStatus
// , 5000);
});


updateCartCount();
loadNotifications();

setInterval(() => {
  updateCartCount();
  loadNotifications();
  checkAccountStatus();
}, 2000); 


$("#btnNotification").on("click", function(e) {
  e.stopPropagation();
  $("#notificationDropdown").toggle();

  // Mark all as read
  $.ajax({
    url: "functions.php",
    method: "POST",
    data: { ref: "mark_notifications_read" },
    success: function() {
      $("#notificationCount").hide();
    }
  });
});


$(document).on("click", function(e) {
  if (!$(e.target).closest("#btnNotification, #notificationDropdown").length) {
    $("#notificationDropdown").hide();
  }
});



$(document).on('click', '.btn-coffee', function() {
  setTimeout(updateCartCount, 700);
});




$("#btnShowCartModal").on("click", function() {
  updateCartCount();
});
$(document).on('click', '.delete-cart-item', function() {
  setTimeout(updateCartCount, 700);
});
});


//show cart
$(document).ready(function(){
  window.getCart = function(){
        $.ajax({
        url: "functions.php",
        method: "POST",
        data: {
        ref: "show_cart",
      },

        dataType: 'json',
        success: function(response) {
        if (response.status === "success") {
        // Update Cart Modal
        $("#cart-items").html(response.html_cart_content);

        // Update Cart Grand Total
        $("#cart-total").text("Total: ₱" + response.cart_grand_total);
          } else {
              // clear display when empty/error
              $("#cart-items").html('<div class="">Cart empty.</div>');
              $("#cart-total").text("Total: ₱0");
              }
          },

            error: function() {
                $("#cart-items").html('<div class="">Cart empty.</div>');
                $("#cart-total").text("Total: ₱0");
        }
    });
}

//showcartmodal
$(document).on("click", "#btnShowCartModal", function(){
    getCart();
});

//delete cart item
$(document).on("click", ".delete-cart-item", function(){

        var cart_id = $(this).data('id');

        $.ajax({
          url: "functions.php",
          method: "POST",
          data: {
            ref: "delete_cart_item",
            cart_id: cart_id
          },
          dataType: 'json',
      
              success: function(response) {
                if (response.status === "success") {
                  Swal.fire({
                    icon: 'success',
                    title: 'Item Removed',
                    text: 'The product has been removed from your cart.',
                    timer: 1500,
                    showConfirmButton: false
                  });

                  getCart();
                  
                } else {
                Swal.fire({
                  icon: 'error',
                  title: 'Oops...',
                  text: 'Something went wrong while deleting the item.'
              });
            }
          }
    });

});
});


//BLOCK
$(document).on('click', '.add-to-cart', function(e) {
  <?php if ($isBlocked): ?>
    e.preventDefault();
    Swal.fire({
      icon: 'error',
      title: 'Access Denied',
      text: 'Your account is blocked. Please contact the coffee shop admin.',
      confirmButtonColor: '#b07542'
    });
    return false;
  <?php endif; ?>
});



(function(){
  let cart = [];
  let productOptions = [];

    
document.addEventListener('DOMContentLoaded', () => {
    fetch('get_products.php')
    .then(r => r.json())
    .then(data => { productOptions = data || []; })
    .catch(err => console.error('Error loading replacement products:', err));

    attachAddToCartListeners();


const checkoutBtn = document.getElementById('checkoutBtn');

if (checkoutBtn) {
checkoutBtn.addEventListener('click', () => {

  // Ask server for current cart before proceeding
  $.ajax({
    url: "functions.php",
    method: "POST",
    data: { ref: "show_cart" },
    dataType: "json",
    success: function(response) {
      if (response.status === "success" && response.cart_grand_total && parseFloat(response.cart_grand_total.replace(/,/g,'')) > 0) {
        
        // SweetAlert confirmation
        Swal.fire({
          title: 'Proceed to Checkout?',
          text: 'Please confirm if you’re ready to complete your order.',
          icon: 'question',
          showCancelButton: true,
          confirmButtonText: 'Yes, proceed',
          cancelButtonText: 'No, stay here',
          confirmButtonColor: '#b07542',
          cancelButtonColor: '#6c757d',
          reverseButtons: true,
          background: '#fff8f0',
          backdrop: `rgba(0,0,0,0.3)`
        }).then((result) => {
          if (result.isConfirmed) {
            // Proceed to checkout (server-side cart has items)
            window.location.href = 'checkout.php';
          } else {
            Swal.fire({
              icon: 'info',
              title: 'Checkout cancelled',
              text: 'You can continue adding more items.',
              confirmButtonColor: '#b07542',
              timer: 1800,
              showConfirmButton: false
            });
          }
        });

      } else {
        Swal.fire({
          icon: 'warning',
          title: 'Cart is Empty!',
          text: 'Add something to checkout.',
          confirmButtonColor: '#b07542'
        });
      }
    },
    error: function() {
      Swal.fire({
        icon: 'error',
        title: 'Unable to verify cart',
        text: 'Please try again.',
        confirmButtonColor: '#b07542'
      });
    }
  });
});
}

  // Smooth scroll for category links
  document.querySelectorAll('aside.sidebar a[href^="#"]').forEach(a => {
      a.addEventListener('click', (e) => {
      e.preventDefault();
      const el = document.querySelector(a.getAttribute('href'));
      if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
});
});


function attachAddToCartListeners() {
      document.querySelectorAll('.btn-coffee').forEach(btn => {
          btn.addEventListener('click', function(){
          if (this.disabled) return;
          const card = this.closest('.menu-card');
          const name = (card.dataset.productName || card.querySelector('.menu-name').textContent).trim();
          const price = parseFloat(card.dataset.productPrice || card.querySelector('.menu-price').textContent.replace(/[₱,]/g,'')) || 0;
          const qtyInput = card.querySelector('input[type="number"]');
          const productID = parseInt(card.dataset.productId || 0);

          let quantity = $(this).closest(".controls").find(".quantity-input").val();
          cart.push({ id: productID, name, price, quantity });
      
          // Add ajax code here to save in the database the item save in cart
          $.ajax({
          url: "functions.php",
          method: "POST",
          data: {
              ref: "add_to_cart",
              cart: cart,
          },

          dataType: "json",
          success: function(response) {
              if(response.status === "success") {
                  // reset local cart (server holds canonical cart)
                  cart = [];  

                  // fetch latest server-side cart and update modal
                  if (typeof getCart === 'function') {
                      getCart();
                  }
              }
          }
          });

          
          Swal.fire({
          icon: 'success',
          title: 'Added!',
          text: `${quantity} × ${name} added to cart.`,
          timer: 1000,
          showConfirmButton: false
    });
});
});
}


})();
</script>

</body>
</html>
