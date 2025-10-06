<?php
session_start();

  require_once('./classes/database.php');
  require_once (__DIR__. "/classes/config.php");

  $db = new Database();

  if (!isset($_SESSION['customer_ID'])) {
  header("Location: login.php");
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

    // try to read image path from raw data if present, fallback to placeholder
    $img = 'uploads/default.jpg';
    if (!empty($p['raw']['image']) ) {              // adjust field name if different
        $img = escape($p['raw']['image']);
    } elseif (!empty($p['raw']['image_path'])) {
        $img = escape($p['raw']['image_path']);
    }

    $bestLabel = $best ? '<span class="badge-best">Best Seller</span>' : '';
    $disabledAttr = $status === 0 ? 'disabled' : '';
    $btnHtml = $status === 0
        ? '<button class="btn btn-coffee mt-2" disabled></button>'
        : '<button class="btn btn-coffee mt-2">Add</button>';
    $inactiveClass = $status === 0 ? 'faded' : '';

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
    $html .= '<input type="number" min="1" max="99" value="1" class="quantity-input" ' . $disabledAttr . '>';
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
		:root {
		--accent: #b07542;
		--accent-dark: #8a5c33;
		--gray-dark: #1e1e1e;
		--gray-mid: #2b2b2b;
		--gray-light: #444;
		--text-light: #f5f5f5;
		--text-muted: #ccc;
		}
    
    body {
    margin: 0;
    font-family: 'Montserrat', sans-serif;
    color: var(--text-light);
    background: url('uploads/bgg.jpg') no-repeat center center fixed;
    background-size: cover;
    }

		body::before {
		content:'';
		position: fixed;
		inset:0;
		background: rgba(0,0,0,.6);
		z-index: -1;
		}

		/* Navbar */
		.navbar-custom {
		background: var(--gray-dark);
		border-bottom: 1px solid var(--gray-light);
		}
		.navbar-brand {
		color: var(--accent) !important;
		font-family: 'Playfair Display', serif;
		font-weight: 800;
		font-size: 2rem;
		}
		.navbar-custom .btn { width:50px; height:50px; font-size:1.2rem; }
		.navbar-custom .dropdown-menu { background: var(--gray-mid); border:1px solid var(--gray-light); min-width:150px; }
		.navbar-custom .dropdown-item { color: var(--text-light); }
		.navbar-custom .dropdown-item:hover { background: var(--accent); color:#fff; }

		/* Layout */
		.container-menu { max-width:1400px; margin:120px auto 40px; }
		.layout { display:flex; gap:30px; flex-wrap:wrap; }
		.sidebar { flex:0 0 280px; padding:25px; background: var(--gray-mid); border-radius:12px; height:calc(85vh); position:sticky; top:120px; overflow-y:auto; scrollbar-width:thin; }
		.sidebar::-webkit-scrollbar { width:6px; }
		.sidebar::-webkit-scrollbar-thumb { background: var(--accent); border-radius:6px; }
		.sidebar h5 { color: var(--accent); font-weight:800; font-size:1.4rem; margin-bottom:20px; }
		.sidebar a { color: var(--text-light); display:block; padding:12px 10px; border-radius:6px; text-decoration:none; font-weight:600; font-size:1.1rem; }
		.sidebar a:hover { background: var(--accent); color:#fff; }
		.sidebar a { scroll-margin-top:130px; }

		/* Main content */
		main.content { flex:1; }
		.page-title { text-align:center; font-family:'Playfair Display', serif; font-weight:800; font-size:2.4rem; margin-bottom:40px; color: var(--text-light); }

		/* Menu Cards */
  .menu-card {
    background: var(--gray-mid);
    color: var(--text-light);
    border: 1px solid var(--gray-light);
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 25px;
    transition: .2s;
    display: flex;
    flex-direction: column;
    height: 360px; /* 🔥 fixed uniform height */
  }

  .card-media {
    position: relative;
    height: 200px; /* 🔥 fixed image height */
    flex-shrink: 0;
  }
  .card-media img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  .menu-body {
    flex: 1;
    padding: 15px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
  }

  .menu-name {
    font-weight: 800;
    font-size: 1.1rem;
    color: #fff;
    margin-bottom: 8px;
    line-height: 1.2;
    height: 40px;         /* 🔥 reserve space for names */
    overflow: hidden;     /* 🔥 cut overflow */
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2; /* 🔥 max 2 lines */
    -webkit-box-orient: vertical;
  }

  .menu-bottom {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: auto;
  }

  .menu-price {
    color: var(--accent);
    font-weight: 800;
    font-size: 1.05rem;
    white-space: nowrap;  /* 🔥 prevent breaking */
  }

  .quantity-input {
    width: 60px;
    padding: 6px;
    text-align: center;
    border-radius: 6px;
    border: 1px solid var(--gray-light);
    background: var(--gray-dark);
    color: var(--text-light);
    font-size: 0.9rem;
  }

  .btn-coffee {
    background: var(--accent);
    color: #fff;
    border: none;
    padding: 6px 14px;
    border-radius: 20px;
    font-weight: 700;
    font-size: 0.9rem;
    flex-shrink: 0;
  }

  .sidebar a.active {
    background: var(--accent);
    color: #fff;
    font-weight: 700;
  }

  .sidebar a {
    transition: background 0.2s ease, color 0.2s ease;
  }


    
      .card-media { position:relative; height:220px; }
      .card-media img { width:100%; height:100%; object-fit:cover; }
      .badge-best { position:absolute; top:12px; left:12px; background: var(--accent); color:#fff; padding:5px 12px; border-radius:14px; font-size:.95rem; }
      .menu-body { padding:18px; display:flex; flex-direction:column; gap:12px; }
      .menu-name { font-weight:800; font-size:1.2rem; color:#fff; }
      .menu-bottom { display:flex; justify-content:space-between; align-items:center; }
      .menu-price { color: var(--accent); font-weight:800; font-size:1.1rem; }
      .quantity-input { width:70px; padding:6px; text-align:center; border-radius:6px; border:1px solid var(--gray-light); background: var(--gray-dark); color: var(--text-light);}
      .btn-coffee { background: var(--accent); color:#fff; border:none; padding:6px 14px; border-radius:20px; font-weight:700;}
      
      .faded { opacity:.6; pointer-events:none; }

      /* Modal */
      .modal-content { border-radius:12px; background: var(--gray-dark); color:#fff; border:1px solid var(--gray-light); }
      .modal-header { background: var(--accent-dark); color:#fff; border-bottom:1px solid var(--gray-light); font-size:1.4rem; font-weight:700;}
      .modal-footer { background: var(--gray-mid); border-top:1px solid var(--gray-light);}
      #cart-total { font-weight:800; color:#fff; font-size:1.6rem; text-align:right; }
      #checkoutBtn { background: var(--accent); color:#fff; border:none; border-radius:20px; padding:8px 18px; font-weight:700;}
      

      /* Responsive */
  @media (max-width: 1199px) {
    .layout { flex-direction: column; }
    .sidebar { width: 100%; height: auto; position: relative; top: auto; margin-bottom: 20px; }
    .navbar-brand { font-size: 1.7rem; }
    .navbar-custom .btn { width: 45px; height: 45px; }
  }

  @media (max-width: 991px) {
    .navbar-brand img { height: 60px; }
    .sidebar h5 { font-size: 1.2rem; }
    .sidebar a { font-size: 1rem; }
    .menu-card .menu-name { font-size: 1.1rem; }
  }

  @media (max-width: 767px) {
    .navbar-brand img { height: 50px; }
    .menu-card .menu-name { font-size: 1rem; }
    .menu-card .menu-price { font-size: 0.95rem; }
    .quantity-input { width: 60px; font-size: 0.9rem; }
    .btn-coffee { font-size: 0.9rem; padding: 5px 12px; }
  }

  @media (max-width: 575px) {
    .menu-card { margin-bottom: 18px; }
    .menu-name { font-size: 1rem; }
    .menu-price { font-size: 0.9rem; }
    .sidebar a { font-size: 0.9rem; padding: 8px 6px; }
    .navbar-brand img { height: 45px; }
    .btn-coffee { font-size: 0.85rem; }
  }

  /* Grid adjustment for extra large screens */
  @media (min-width: 1400px) {
    .row.gy-3 > [class*="col-"] {
      flex: 0 0 25%; /* 4 per row */
      max-width: 25%;
    }
  }

</style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark navbar-custom fixed-top">

              <div class="container-fluid" style="max-width:1400px;">
              <a class="navbar-brand" href="#"><img src="uploads/izana_logo.png" alt="IZANA Logo" style="height: 80px;" ></a>

              <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
              <span class="navbar-toggler-icon"></span>
              </button>

              <div class="collapse navbar-collapse justify-content-end" id="navMenu">
              <ul class="navbar-nav align-items-center">
              <li class="nav-item me-3">

              <button class="btn btn-warning rounded-circle" 
              id="btnShowCartModal"
              data-bs-toggle="modal" 
              data-bs-target="#cartModal">
              <i class="fas fa-shopping-cart"></i>
              </button>

              </li>
              <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Menu</a>
              <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="profile.php">Profile</a></li>
              <li><a class="dropdown-item text-danger" href="Logout.php">Logout</a></li>
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


    </main>
  </div>
</div>

<!-- Cart Modal -->
<div class="modal fade" id="cartModal">
      <div class="modal-dialog modal-sm modal-dialog-scrollable">
      <div class="modal-content">
      
      <div class="modal-header"><h5 class="modal-title">🛒 Cart</h5><button 
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

<script>

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


(function(){
    let cart = [];
    let productOptions = [];

     // Fetch product options for replacement list
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
                // proceed to checkout (server-side cart has items)
                window.location.href = 'checkout.php';
              } else {
                Swal.fire({ icon:'warning', title:'Cart is Empty!', text:'Add something to checkout.', confirmButtonColor: '#b07542' });
              }
            },
            error: function() {
              Swal.fire({ icon:'error', title:'Unable to verify cart', text:'Please try again.' });
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
