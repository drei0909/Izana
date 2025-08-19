<?php
session_start();

if (!isset($_SESSION['customer_ID'])) {
    header("Location: login.php");
    exit();
}

require_once('./classes/database.php');
$db = new Database();

/**
 * IMPORTANT:
 * getAllProducts() must return the column `is_active` (1 or 0).
 * If your column name is different, change it below accordingly.
 */
$products = $db->getAllProducts();

// New customer promo flag
if (isset($_SESSION['is_new']) && $_SESSION['is_new']) {
    $_SESSION['show_promo'] = true;
}

// Group products by category
$grouped = [];
foreach ($products as $p) {
    $cat = $p['product_category'] ?? 'Other';
    $grouped[$cat][] = [
        (int)$p['product_id'],
        $p['product_name'],
        (float)$p['product_price'],
        ($p['product_category'] == 1),                 // your “best seller” flag, kept as-is
        isset($p['stock_quantity']) ? (int)$p['stock_quantity'] : 0,
        isset($p['is_active']) ? (int)$p['is_active'] : 1 // 1=active, 0=inactive
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Menu | Izana Coffee</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Quicksand:wght@400;600&display=swap" rel="stylesheet">
  <style>
/* RESET + GLOBAL */
body {
  margin: 0;
  font-family: 'Quicksand', sans-serif;
  background: linear-gradient(160deg, #e6e6e6 0%, #cfcfcf 50%, #1c1c1c 100%);
  color: #2b2b2b;
  min-height: 100vh;
}

/* Inactive products (deactivated) */
.menu-card.faded {
  opacity: 0.5;
  filter: grayscale(100%);
  pointer-events: none; /* prevents clicks on any inner element */
}
.menu-card.faded .btn-coffee {
  background: #aaa;
  cursor: not-allowed;
}

/* MAIN CONTAINER */
.container-menu {
  max-width: 1200px;
  margin: 70px auto;
  background: #fff;
  border-radius: 16px;
  padding: 40px;
  box-shadow: 0 12px 30px rgba(0,0,0,0.2);
}

/* TITLES */
.title {
  font-family: 'Playfair Display', serif;
  font-size: 2.5rem;
  text-align: center;
  color: #4e342e;
  margin-bottom: 35px;
  border-bottom: 3px solid #b07542;
  padding-bottom: 10px;
}
.category-title {
  font-size: 1.6rem;
  font-weight: 600;
  margin: 40px 0 20px;
  color: #1c1c1c;
  border-left: 6px solid #b07542;
  padding-left: 10px;
}

/* PRODUCT CARDS */
.menu-card {
  background: #f8f8f8;
  border: 1px solid #ddd;
  border-radius: 14px;
  padding: 18px;
  margin-bottom: 25px;
  text-align: center;
  transition: all 0.3s ease;
}
.menu-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 6px 18px rgba(0,0,0,0.15);
}
.menu-card img {
  width: 100%;
  max-height: 180px;
  object-fit: cover;
  border-radius: 12px;
  margin-bottom: 15px;
}
.menu-name { font-size: 1.2rem; font-weight: 600; color: #2b2b2b; }
.menu-price { color: #b07542; font-weight: 600; margin-bottom: 10px; }

/* BUTTONS */
.btn-coffee {
  background-color: #4e342e;
  color: #fff;
  font-weight: 600;
  border: none;
  padding: 8px 20px;
  border-radius: 25px;
  transition: all 0.3s;
}
.btn-coffee:hover { background-color: #2b1d17; }

/* BADGES */
.badge-best {
  background-color: #b07542;
  color: #fff;
  font-weight: 600;
  font-size: 0.75rem;
  padding: 4px 10px;
  border-radius: 20px;
  display: inline-block;
  margin-top: 6px;
}

/* INPUT */
.quantity-input {
  width: 60px;
  border-radius: 8px;
  border: 1px solid #aaa;
  padding: 5px;
  text-align: center;
  margin: 10px auto;
  font-weight: 600;
}

/* DROPDOWN MENU */
.btn-menu-toggle {
  background-color: #4e342e;
  color: white;
  border: none;
  border-radius: 8px;
  padding: 8px 16px;
}
.btn-menu-toggle:hover { background-color: #2b1d17; }
.dropdown-menu {
  background-color: #f9f9f9;
  border-radius: 10px;
  min-width: 180px;
  font-size: 0.95rem;
}
.dropdown-item:hover { background-color: #eee; }

/* CART MODAL */
.modal-content { background-color: #f9f9f9; border-radius: 12px; }
.modal-header { background-color: #1c1c1c; color: #fff; border-top-left-radius: 12px; border-top-right-radius: 12px; }
.modal-footer { border-top: 1px solid #ddd; }

/* CART BUTTON */
button[data-bs-target="#cartModal"] { background: #b07542; color: white; border: none; }
button[data-bs-target="#cartModal"]:hover { background: #8a5c33; }
  </style>
</head>
<body>

<div class="container-menu">
  <h2 class="title">Izana Coffee Menu</h2>

<?php if (isset($_SESSION['is_new']) && $_SESSION['is_new']) : ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
Swal.fire({
    icon: 'info',
    title: '🎉 Welcome!',
    text: 'claim your ₱30 off for your first cup discount using code: FIRSTCUP',
    confirmButtonColor: '#b07542'
});
</script>
<?php unset($_SESSION['is_new']); endif; ?>

<?php if (isset($_SESSION['promo_applied_successfully']) && $_SESSION['promo_applied_successfully']): ?>
<script>
Swal.fire({
  icon: 'info',
  title: 'Promo Applied!',
  text: 'You’ve successfully claimed 10% off with WELCOME10!',
  confirmButtonColor: '#b07542'
});
</script>
<?php unset($_SESSION['promo_applied_successfully']); endif; ?>

<?php
// ---------- PHP rendering helpers ----------
function renderCategory($title, $items) {
    echo "<div class='category-title'>".htmlspecialchars($title)."</div><div class='row'>";
    foreach ($items as $item) {
        // item: [id, name, price, best, stock, status]
        echo card($item[0], $item[1], $item[2], $item[3] ?? false, $item[4] ?? 0, $item[5] ?? 1);
    }
    echo "</div>";
}

function card($productID, $name, $price, $best = false, $stock = 0, $status = 1) {
    $img = "uploads/t.jpg";
    $bestLabel = $best ? "<div class='badge-best'>Best Seller</div>" : "";
    $inactiveClass = ($status == 0) ? "faded" : "";
    $btnHtml = ($status == 0)
        ? "<button class='btn btn-coffee mt-2' disabled>Unavailable</button>"
        : "<button class='btn btn-coffee mt-2'>Add</button>";
    $disabledAttr = ($status == 0) ? 'disabled' : '';
    $priceFmt = number_format((float)$price, 2);

    // escape name for HTML
    $safeName = htmlspecialchars($name);

    return <<<HTML
    <div class="col-md-4">
      <div class="menu-card {$inactiveClass}" data-product-id="{$productID}">
        <img src="{$img}" alt="{$safeName}">
        <div class="menu-name">{$safeName}</div>
        <div class="menu-price">₱{$priceFmt}</div>
        {$bestLabel}
        <input type="number" min="1" max="99" value="1" class="quantity-input" name="quantity_{$safeName}" {$disabledAttr}>
        {$btnHtml}
      </div>
    </div>
HTML;
}

// Render all categories
foreach ($grouped as $category => $items) {
    renderCategory($category, $items);
}
?>
</div>

<!-- Cart Modal -->
<div class="modal fade" id="cartModal" tabindex="-1" aria-labelledby="cartModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable modal-sm">
    <div class="modal-content">
      <div class="modal-header bg-light">
        <h5 class="modal-title" id="cartModalLabel">🛒 Your Cart</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="cart-items">
        <div class="text-muted text-center">Your cart is empty.</div>
      </div>
      <div class="modal-footer d-flex justify-content-between align-items-center">
        <div id="cart-total" class="fw-bold">Total: ₱0</div>
        <button id="checkoutBtn" class="btn btn-success btn-sm">Checkout</button>
      </div>
    </div>
  </div>
</div>

<!-- Top-left menu dropdown -->
<div class="dropdown position-fixed top-0 start-0 m-3" style="z-index: 1050;">
  <button class="btn btn-menu-toggle dropdown-toggle" type="button" id="menuDropdown" data-bs-toggle="dropdown" aria-expanded="false">
    ☰ Menu
  </button>
  <ul class="dropdown-menu shadow" aria-labelledby="menuDropdown">
    <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
    <li><hr class="dropdown-divider"></li>
    <li><a class="dropdown-item text-danger" href="login.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
  </ul>
</div>

<div style="position: fixed; top: 20px; right: 20px; z-index: 1050;">
  <button class="btn btn-outline-dark rounded-circle" data-bs-toggle="modal" data-bs-target="#cartModal" title="View Cart">
    <i class="fas fa-shopping-cart fa-lg"></i>
  </button>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let cart = [];
let productOptions = [];

// Fetch products for "replace item" options
document.addEventListener("DOMContentLoaded", () => {
  fetch('get_products.php')
    .then(r => r.json())
    .then(data => {
      productOptions = data;
      attachAddToCartListeners();
    })
    .catch(err => console.error('Error loading products:', err));

  const checkoutBtn = document.getElementById("checkoutBtn");
  if (checkoutBtn) {
    checkoutBtn.addEventListener("click", () => {
      if (cart.length === 0) {
        Swal.fire({
          icon: 'warning',
          title: 'Cart is Empty!',
          text: 'Add something to checkout.',
          confirmButtonColor: '#b07542'
        });
      } else {
        fetch('save_cart.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(cart)
        }).then(() => {
          Swal.fire({
            icon: 'success',
            title: 'Proceeding to Checkout...',
            timer: 1000,
            showConfirmButton: false
          }).then(() => {
            window.location.href = 'checkout.php';
          });
        });
      }
    });
  }
});

function attachAddToCartListeners() {
  document.querySelectorAll('.btn-coffee').forEach(btn => {
    btn.addEventListener('click', function () {
      if (this.disabled) return; // extra guard

      const card = this.closest('.menu-card');
      const name = card.querySelector('.menu-name').textContent.trim();
      const price = parseFloat(card.querySelector('.menu-price').textContent.replace(/[₱,]/g, ''));
      const qtyInput = card.querySelector('input[type="number"]');
      const quantity = parseInt(qtyInput.value) || 1;
      const productID = parseInt(card.getAttribute('data-product-id'));

      const existing = cart.find(item => item.id === productID);
      if (existing) {
        existing.quantity += quantity;
      } else {
        cart.push({ id: productID, name, price, quantity });
      }

      renderCart();

      Swal.fire({
        icon: 'success',
        title: 'Added!',
        text: `${quantity} × ${name} added to cart.`,
        timer: 1200,
        showConfirmButton: false
      });
    });
  });
}

function renderCart() {
  const cartDiv = document.getElementById('cart-items');
  const totalDisplay = document.getElementById('cart-total');
  cartDiv.innerHTML = '';
  let total = 0;

  if (cart.length === 0) {
    cartDiv.innerHTML = `<div class="text-muted text-center">Your cart is empty.</div>`;
    totalDisplay.textContent = `Total: ₱0`;
    return;
  }

  cart.forEach((item, index) => {
    const itemTotal = item.price * item.quantity;
    total += itemTotal;

    cartDiv.innerHTML += `
      <div class="mb-2 border-bottom pb-2">
        <div>
          <strong>${item.quantity}× ${escapeHtml(item.name)}</strong><br>
          <small>₱${item.price.toFixed(2)} each</small><br>
          <small class="text-muted">₱${itemTotal.toFixed(2)}</small>
        </div>
        <div class="mt-1 d-flex gap-2">
          <button class="btn btn-sm btn-outline-danger" onclick="removeCartItem(${index})">
            <i class="fas fa-trash-alt"></i>
          </button>
          <button class="btn btn-sm btn-outline-warning" onclick="replaceCartItem(${index})">
            <i class="fas fa-sync-alt"></i>
          </button>
        </div>
      </div>
    `;
  });

  totalDisplay.textContent = `Total: ₱${total.toFixed(2)}`;
}

function removeCartItem(index) {
  const itemName = cart[index].name;
  cart.splice(index, 1);
  renderCart();

  Swal.fire({
    icon: 'info',
    title: 'Removed',
    text: `${itemName} removed from cart.`,
    timer: 1200,
    showConfirmButton: false
  });
}

function replaceCartItem(index) {
  const itemToReplace = cart[index];

  // Optional: filter out inactive products if your JSON contains is_active/status
  const options = productOptions.filter(p => (p.is_active ?? p.status ?? 1) == 1);

  const optionsHTML = options.map((opt, i) =>
    `<option value="${i}">${escapeHtml(opt.product_name)} - ₱${parseFloat(opt.product_price).toFixed(2)}</option>`
  ).join('');

  Swal.fire({
    title: `Replace ${escapeHtml(itemToReplace.name)}`,
    html: `<select id="replace-select" class="swal2-select">${optionsHTML}</select>`,
    confirmButtonText: 'Replace',
    showCancelButton: true,
    preConfirm: () => document.getElementById('replace-select').value
  }).then(result => {
    if (result.isConfirmed) {
      const newItem = options[result.value];
      cart[index] = {
        id: newItem.product_id,
        name: newItem.product_name,
        price: parseFloat(newItem.product_price),
        quantity: itemToReplace.quantity
      };

      renderCart();
      Swal.fire({
        icon: 'success',
        title: 'Replaced!',
        text: `${itemToReplace.name} replaced with ${newItem.product_name}.`,
        timer: 1200,
        showConfirmButton: false
      });
    }
  });
}

function escapeHtml(text) {
  const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
  return text.replace(/[&<>"']/g, m => map[m]);
}
</script>

</body>
</html>
