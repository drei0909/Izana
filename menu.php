<?php
session_start();
require_once('./classes/database.php');
require_once (__DIR__. "/classes/config.php");

if (!isset($_SESSION['customer_ID'])) {
    header("Location: login.php");
    exit();
}


$db = new Database();
$products = $db->getAllProducts($_GET['category_id']) ?? [];

$stmt = $db->conn->prepare("SELECT * FROM product_categories WHERE deleted = 0");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);



$grouped = [];
foreach ($products as $p) {
    $cat = $p['product_category'] ?? 'Other';
    $catKey = is_string($cat) ? $cat : (string)$cat;
    $grouped[$catKey][] = [
        'product_id' => (int)($p['product_id'] ?? 0),
        'product_name' => $p['product_name'] ?? 'Unnamed',
        'product_price' => (float)($p['product_price'] ?? 0),
        'best' => isset($p['best']) ? (bool)$p['best'] : (($p['product_category'] ?? '') == 1),
        'stock' => $p['stock_quantity'] ?? 0,
        'status' => $p['is_active'] ?? 1,
        'raw' => $p
    ];
}

function escape($s) {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function card_html($p) {
    $id = (int)$p['product_id'];
    $name = escape($p['product_name']);
    $priceFmt = number_format((float)$p['product_price'], 2);
    $best = $p['best'] ?? false;
    $status = (int)($p['status'] ?? 1);
    $img = "uploads/t.jpg";

    $bestLabel = $best ? "<span class='badge-best'>Best Seller</span>" : "";
    $disabled = $status == 0 ? "disabled" : "";
    $btn = $status == 0 ? "<button class='btn btn-coffee mt-2' disabled>Unavailable</button>" : "<button class='btn btn-coffee mt-2'>Add</button>";
    $inactive = $status == 0 ? "faded" : "";

    return <<<HTML
<div class="col-12 col-sm-6 col-lg-4">
  <div class="menu-card {$inactive}" data-product-id="{$id}" data-product-price="{$p['product_price']}" data-product-name="{$name}">
    <div class="card-media">
      <img src="{$img}" alt="{$name}">
      {$bestLabel}
    </div>
    <div class="menu-body">
      <div class="menu-name">{$name}</div>
      <div class="menu-bottom">
        <div class="menu-price">₱{$priceFmt}</div>
        <div class="controls">
          <input type="number" min="1" max="99" value="1" class="quantity-input" {$disabled}>
          {$btn}
        </div>
      </div>
    </div>
  </div>
</div>
HTML;
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
  font-family: 'Quicksand', sans-serif;
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
.container-menu { max-width:1400px; margin:120px auto 40px; padding:20px; }
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
  border:1px solid var(--gray-light);
  border-radius:12px;
  overflow:hidden;
  margin-bottom:25px;
  transition:.2s;
}
.menu-card:hover { transform: translateY(-5px); box-shadow:0 8px 24px rgba(0,0,0,.4);}
.card-media { position:relative; height:220px; }
.card-media img { width:100%; height:100%; object-fit:cover; }
.badge-best { position:absolute; top:12px; left:12px; background: var(--accent); color:#fff; padding:5px 12px; border-radius:14px; font-size:.95rem; }
.menu-body { padding:18px; display:flex; flex-direction:column; gap:12px; }
.menu-name { font-weight:800; font-size:1.2rem; color:#fff; }
.menu-bottom { display:flex; justify-content:space-between; align-items:center; }
.menu-price { color: var(--accent); font-weight:800; font-size:1.1rem; }
.quantity-input { width:70px; padding:6px; text-align:center; border-radius:6px; border:1px solid var(--gray-light); background: var(--gray-dark); color: var(--text-light);}
.btn-coffee { background: var(--accent); color:#fff; border:none; padding:6px 14px; border-radius:20px; font-weight:700;}
.btn-coffee:hover { background: var(--accent-dark);}
.faded { opacity:.6; pointer-events:none; }

/* Modal */
.modal-content { border-radius:12px; background: var(--gray-dark); color:#fff; border:1px solid var(--gray-light); padding:20px; }
.modal-header { background: var(--accent-dark); color:#fff; border-bottom:1px solid var(--gray-light); font-size:1.4rem; font-weight:700;}
.modal-footer { background: var(--gray-mid); border-top:1px solid var(--gray-light);}
#cart-total { font-weight:800; color:#fff; font-size:1.6rem; text-align:right; }
#checkoutBtn { background: var(--accent); color:#fff; border:none; border-radius:20px; padding:8px 18px; font-weight:700;}
#checkoutBtn:hover { background: var(--accent-dark); cursor:pointer; }

/* Responsive */
@media(max-width:1199px) {
  .layout { flex-direction:column; }
  .sidebar { width:100%; height:auto; position:relative; top:auto; margin-bottom:20px; }
  .navbar-brand { font-size:1.7rem; }
  .navbar-custom .btn { width:45px; height:45px; }
}
@media(max-width:575px) {
  .menu-card { margin-bottom:18px; }
  .menu-name { font-size:1.05rem; }
  .menu-price { font-size:1rem; }
  .sidebar a { font-size:1rem; padding:10px 8px; }
}
</style>


</head>
<body>
<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark navbar-custom fixed-top">
  <div class="container-fluid" style="max-width:1400px;">
    <a class="navbar-brand" href="#">Izana Coffee</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-end" id="navMenu">
      <ul class="navbar-nav align-items-center">
        <li class="nav-item me-3">
          <button class="btn btn-warning rounded-circle" data-bs-toggle="modal" data-bs-target="#cartModal">
            <i class="fas fa-shopping-cart"></i>
          </button>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Menu</a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="profile.php">Profile</a></li>
            <li><a class="dropdown-item text-danger" href="login.php">Logout</a></li>
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
  <?php foreach ($categories as $category): ?>
       <a href="<?php echo BASE_URL ?>menu.php?category_id=<?= $category['id'] ?>"><?= htmlspecialchars($category['category']) ?></a>
 <?php endforeach; ?>

     
    </aside>
    <main class="content">
      <div class="page-title">Coffee Menu</div>
      
      <?php if(empty($categories)): ?>
        <div class="alert alert-light">No products categories available.</div>
      <?php endif; ?>

      <?php foreach($grouped as $cat=>$items): $anchor='cat-'.preg_replace('/[^a-z0-9\-_]/i','-',strtolower($cat)); ?>
        <section id="<?=escape($anchor)?>" class="mb-4">
          <h4 class="border-bottom pb-2 mb-3 text-light"><?=escape($cat)?></h4>
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
      <div class="modal-header"><h5 class="modal-title">🛒 Cart</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body" id="cart-items"><div class="text-muted">Cart empty.</div></div>
      <div class="modal-footer d-flex justify-content-between">
        <div id="cart-total">Total: ₱0</div>
        <button id="checkoutBtn" class="btn btn-success btn-sm">Checkout</button>
      </div>
    </div>
  </div>

  <!-- SCRIPTS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
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
      renderCart();

      const checkoutBtn = document.getElementById('checkoutBtn');
      if (checkoutBtn) {
        checkoutBtn.addEventListener('click', () => {
          if (cart.length === 0) {
            Swal.fire({ icon:'warning', title:'Cart is Empty!', text:'Add something to checkout.', confirmButtonColor: '#b07542' });
            return;
          }
          fetch('save_cart.php', {
            method:'POST',
            headers:{ 'Content-Type':'application/json' },
            body: JSON.stringify(cart)
          }).then(() => {
            Swal.fire({ icon:'success', title:'Proceeding to Checkout...', timer:800, showConfirmButton:false })
              .then(() => window.location.href = 'checkout.php');
          }).catch(err => {
            console.error('Save cart error', err);
            Swal.fire({ icon:'error', title:'Save Error', text:'Unable to save cart, please try again.' });
          });
        });
      }

      // show one-time promos from PHP
      <?php if ($show_new_customer_promo): ?>
      Swal.fire({ icon:'info', title:'🎉 Welcome!', text:'Claim ₱30 off your first cup with code: FIRSTCUP', confirmButtonColor:'#b07542' });
      <?php endif; ?>

      <?php if ($show_promo_applied): ?>
      Swal.fire({ icon:'info', title:'Promo Applied!', text:'You’ve successfully claimed 10% off with WELCOME10!', confirmButtonColor:'#b07542' });
      <?php endif; ?>

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
          const quantity = Math.max(1, parseInt(qtyInput.value || 1));
          const productID = parseInt(card.dataset.productId || 0);

          const existing = cart.find(i => i.id === productID);
          if (existing) existing.quantity += quantity;
          else cart.push({ id: productID, name, price, quantity });

          renderCart();
          Swal.fire({ icon:'success', title:'Added!', text: `${quantity} × ${name} added to cart.`, timer:1000, showConfirmButton:false });
        });
      });
    }

    function renderCart() {
      const cartDiv = document.getElementById('cart-items');
      const totalDisplay = document.getElementById('cart-total');
      cartDiv.innerHTML = '';
      let total = 0;

      if (!cart.length) {
        cartDiv.innerHTML = `<div class="text-muted text-center">Your cart is empty.</div>`;
        totalDisplay.textContent = `Total: ₱0`;
        return;
      }

      cart.forEach((item, index) => {
        const itemTotal = item.price * item.quantity;
        total += itemTotal;

        const itemEl = document.createElement('div');
        itemEl.className = 'mb-2 border-bottom pb-2';
        itemEl.innerHTML = `
          <div>
            <strong>${escapeHtml(item.quantity)}× ${escapeHtml(item.name)}</strong><br>
            <small>₱${item.price.toFixed(2)} each</small><br>
            <small class="text-muted">₱${itemTotal.toFixed(2)}</small>
          </div>
          <div class="mt-1 d-flex gap-2">
            <button class="btn btn-sm btn-outline-danger" data-index="${index}" aria-label="Remove item">
              <i class="fas fa-trash-alt"></i>
            </button>
            <button class="btn btn-sm btn-outline-warning" data-index-replace="${index}" aria-label="Replace item">
              <i class="fas fa-sync-alt"></i>
            </button>
          </div>
        `;
        cartDiv.appendChild(itemEl);
      });

      cartDiv.querySelectorAll('button[data-index]').forEach(b => b.addEventListener('click', (e) => {
        const idx = Number(e.currentTarget.getAttribute('data-index'));
        removeCartItem(idx);
      }));
      cartDiv.querySelectorAll('button[data-index-replace]').forEach(b => b.addEventListener('click', (e) => {
        const idx = Number(e.currentTarget.getAttribute('data-index-replace'));
        replaceCartItem(idx);
      }));

      totalDisplay.textContent = `Total: ₱${total.toFixed(2)}`;
    }

    function removeCartItem(index) {
      const name = cart[index]?.name || '';
      cart.splice(index, 1);
      renderCart();
      Swal.fire({ icon:'info', title:'Removed', text:`${name} removed from cart.`, timer:900, showConfirmButton:false });
    }

    function replaceCartItem(index) {
      const itemToReplace = cart[index];
      const options = (productOptions || []).filter(p => (p.is_active ?? p.status ?? 1) == 1);
      if (!options.length) {
        Swal.fire({ icon:'info', title:'No Options', text:'No replacement products available.' });
        return;
      }
      const optionsHTML = options.map((opt,i) => `<option value="${i}">${escapeHtml(opt.product_name)} - ₱${parseFloat(opt.product_price).toFixed(2)}</option>`).join('');
      Swal.fire({
        title: `Replace ${escapeHtml(itemToReplace.name)}`,
        html: `<select id="replace-select" class="swal2-select">${optionsHTML}</select>`,
        confirmButtonText: 'Replace',
        showCancelButton: true,
        preConfirm: () => {
          const el = document.getElementById('replace-select');
          return el ? el.value : null;
        }
      }).then(result => {
        if (result.isConfirmed && result.value !== null) {
          const newItem = options[Number(result.value)];
          cart[index] = { id: newItem.product_id, name: newItem.product_name, price: parseFloat(newItem.product_price), quantity: itemToReplace.quantity };
          renderCart();
          Swal.fire({ icon:'success', title:'Replaced!', text: `${itemToReplace.name} replaced with ${newItem.product_name}.`, timer:1000, showConfirmButton:false });
        }
      });
    }

    function escapeHtml(text) {
      return String(text).replace(/[&<>"']/g, function(m){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]; });
    }
  })();
  </script>
</body>
</html>
