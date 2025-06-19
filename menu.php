<?php
session_start();
if (!isset($_SESSION['customer_ID'])) {
    header("Location: login.php");
    exit();
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
    body {
      background: url('uploads/bgg.jpg') no-repeat center center fixed;
      background-size: cover;
      font-family: 'Quicksand', sans-serif;
      color: #f7f1eb;
    }

    .container-menu {
      max-width: 1200px;
      margin: 80px auto;
      background: rgba(255, 248, 230, 0.15);
      border: 1.5px solid rgba(255, 255, 255, 0.3);
      border-radius: 18px;
      padding: 40px;
      box-shadow: 0 12px 30px rgba(0,0,0,0.3);
      backdrop-filter: blur(8px);
    }

    .title {
      font-family: 'Playfair Display', serif;
      font-size: 2.5rem;
      text-align: center;
      color: #fff8f3;
      margin-bottom: 30px;
      text-shadow: 1px 1px 0 #f2e1c9;
    }

    .category-title {
      font-size: 1.8rem;
      font-weight: bold;
      margin-top: 30px;
      margin-bottom: 20px;
      color: #fff8f3;
      border-bottom: 2px solid #fff;
      padding-bottom: 5px;
    }

    .menu-card {
      background: rgba(255, 255, 255, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.3);
      border-radius: 15px;
      padding: 20px;
      margin-bottom: 25px;
      text-align: center;
      box-shadow: 0 8px 20px rgba(0,0,0,0.2);
      backdrop-filter: blur(6px);
      transition: transform 0.3s ease;
    }

    .menu-card:hover {
      transform: translateY(-5px);
    }

    .menu-card img {
      width: 100%;
      max-height: 180px;
      object-fit: cover;
      border-radius: 12px;
      margin-bottom: 15px;
    }

    .menu-name {
      font-size: 1.2rem;
      font-weight: 600;
      color: #fffaf2;
    }

    .menu-price {
      color: #f2d9be;
      margin-bottom: 10px;
    }

    .btn-coffee {
      background-color: #b07542;
      color: #fff;
      font-weight: 600;
      border: none;
      padding: 10px 20px;
      border-radius: 30px;
      transition: all 0.3s ease-in-out;
    }

    .btn-coffee:hover {
      background-color: #8a5c33;
    }

    .badge-best {
      background-color: #f5b041;
      color: #000;
      font-weight: 700;
      font-size: 0.75rem;
      margin-top: 5px;
      padding: 5px 10px;
      border-radius: 50px;
      display: inline-block;
    }

    .quantity-input {
      width: 60px;
      border-radius: 10px;
      border: 1px solid #ccc;
      padding: 5px;
      text-align: center; A
      margin: 10px auto;
      font-weight: 600;
    }


    .btn-menu-toggle {
  background-color:transparent;
  color: white;
  font-weight: 600;
  border: none;
  border-radius: 8px;
  padding: 8px 16px;
}

.dropdown-menu {
  background-color: #fff9f3;
  border-radius: 10px;
  min-width: 180px;
  font-size: 0.95rem;
}

.dropdown-item:hover {
  background-color: #f5eee3;
}

.modal-content {
  background-color: #fff;
  color: #212529; /* Bootstrap dark text */
}

  </style>
</head>
<body>

<div class="container-menu">
  <h2 class="title">Izana Coffee Menu</h2>


  <?php
  function renderCategory($title, $items) {
    echo "<div class='category-title'>{$title}</div><div class='row'>";
    foreach ($items as $item) {
      echo card($item[0], $item[1], $item[2] ?? false);
    }
    echo "</div>";
  }

  function card($name, $price, $best = false) {
    $img = "uploads/default-drink.jpg";
    $bestLabel = $best ? "<div class='badge-best'>Best Seller</div>" : "";
    return <<<HTML
    <div class="col-md-4">
      <div class="menu-card">
        <img src="$img" alt="$name">
        <div class="menu-name">$name</div>
        <div class="menu-price">₱$price</div>
        $bestLabel
        <input type="number" min="1" max="99" value="1" class="quantity-input" name="quantity_$name">
        <button class="btn btn-coffee mt-2">Add</button>
      </div>
    </div>
    HTML;
  }

  renderCategory("Hot Latte (12oz)", [
    ['Caffe Americano', 70],
    ['Latte', 90],
    ['Cappuccino', 90],
    ['Caramel Macchiato', 90]
  ]);

  renderCategory("Iced Latte (16oz)", [
    ['Iced Caffe Americano', 90],
    ['Iced White Chocolate Mocha', 100],
    ['Iced Spanish Latte', 100, true],
    ['Iced Caffe Latte', 100],
    ['Iced Caffe Mocha', 100],
    ['Iced Caramel Macchiato', 100],
    ['Iced Strawberry Latte', 100],
    ['Iced Sea Salt Latte', 110]
  ]);

  renderCategory("Frappe (16oz)", [
    ['Dark Mocha', 120],
    ['Coffee Jelly', 120],
    ['Java Chip', 120],
    ['Strawberries & Cream', 120],
    ['Matcha', 120],
    ['Dark Chocolate M&M', 100],
    ['Red Velvet Oreo', 100]
  ]);

  renderCategory("Mango Supreme", [
    ['Mango Supreme - Caramel (S)', 80, true],
    ['Mango Supreme - Cream Cheese (S)', 80],
    ['Mango Supreme - Cream Cheese (L)', 90, true],
    ['Mango Supreme - Caramel (L)', 90]
  ]);

  renderCategory("Matcha (Ceremonial Grade) (16oz)", [
    ['Matcha Latte', 120],
    ['Matcha Strawberry Latte', 140]
  ]);

  renderCategory("Add-Ons & Extras", [
    ['Pearl', 20],
    ['Whip Cream', 20],
    ['Espresso Shot', 30]
  ]);
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

document.querySelectorAll('.btn-coffee').forEach(btn => {
  btn.addEventListener('click', function () {
    const card = this.closest('.menu-card');
    const name = card.querySelector('.menu-name').textContent.trim();
    const price = parseFloat(card.querySelector('.menu-price').textContent.replace(/[₱,]/g, ''));
    const qtyInput = card.querySelector('input[type="number"]');
    const quantity = parseInt(qtyInput.value) || 1;

    const existing = cart.find(item => item.name === name);
    if (existing) {
      existing.quantity += quantity;
    } else {
      cart.push({ name, price, quantity });
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

function renderCart() {
  const cartDiv = document.getElementById('cart-items');
  const totalDisplay = document.getElementById('cart-total');
  cartDiv.innerHTML = '';
  let total = 0;

  if (cart.length === 0) {
    cartDiv.innerHTML = `<div class="text-muted text-center">Your cart is empty.</div>`;
  }

  cart.forEach((item, index) => {
    const itemTotal = item.price * item.quantity;
    total += itemTotal;

    cartDiv.innerHTML += `
      <div class="mb-2 border-bottom pb-2">
        <div>
          <strong>${item.quantity}× ${item.name}</strong><br>
          <small>₱${item.price} each</small> <br>
          <small class="text-muted">₱${itemTotal}</small>
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

  totalDisplay.textContent = `Total: ₱${total}`;
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

 const options = [
  // HOT LATTE
  { name: 'Caffe Americano', price: 70 },
  { name: 'Latte', price: 90 },
  { name: 'Cappuccino', price: 90 },
  { name: 'Caramel Macchiato', price: 90 },

  // ICED LATTE
  { name: 'Iced Caffe Americano', price: 90 },
  { name: 'Iced White Chocolate Mocha', price: 100 },
  { name: 'Iced Spanish Latte', price: 100 },
  { name: 'Iced Caffe Latte', price: 100 },
  { name: 'Iced Caffe Mocha', price: 100 },
  { name: 'Iced Caramel Macchiato', price: 100 },
  { name: 'Iced Strawberry Latte', price: 100 },
  { name: 'Iced Sea Salt Latte', price: 110 },

  // FRAPPE
  { name: 'Dark Mocha', price: 120 },
  { name: 'Coffee Jelly', price: 120 },
  { name: 'Java Chip', price: 120 },
  { name: 'Strawberries & Cream', price: 120 },
  { name: 'Matcha', price: 120 },
  { name: 'Dark Chocolate M&M', price: 100 },
  { name: 'Red Velvet Oreo', price: 100 },

  // MANGO SUPREME (S/L)
  { name: 'Mango Supreme - Caramel (S)', price: 80 },
  { name: 'Mango Supreme - Caramel (L)', price: 90 },
  { name: 'Mango Supreme - Cream Cheese (S)', price: 80 },
  { name: 'Mango Supreme - Cream Cheese (L)', price: 90 },

  // MATCHA CEREMONIAL
  { name: 'Matcha Latte', price: 120 },
  { name: 'Matcha Strawberry Latte', price: 140 },

  // ADD-ONS
  { name: 'Pearl', price: 20 },
  { name: 'Whip Cream', price: 20 },
  { name: 'Espresso Shot', price: 30 }
];


  const optionsHTML = options.map((opt, i) =>
    `<option value="${i}">${opt.name} - ₱${opt.price}</option>`
  ).join('');

  Swal.fire({
    title: `Replace ${itemToReplace.name}`,
    html: `<select id="replace-select" class="swal2-select">${optionsHTML}</select>`,
    confirmButtonText: 'Replace',
    showCancelButton: true,
    preConfirm: () => {
      const selected = document.getElementById('replace-select').value;
      return selected;
    }
  }).then(result => {
    if (result.isConfirmed) {
      const newItem = options[result.value];
      cart[index] = {
        name: newItem.name,
        price: newItem.price,
        quantity: itemToReplace.quantity
      };
      renderCart();
      Swal.fire({
        icon: 'success',
        title: 'Replaced!',
        text: `${itemToReplace.name} replaced with ${newItem.name}.`,
        timer: 1200,
        showConfirmButton: false
      });
    }
  });
}

document.addEventListener("DOMContentLoaded", () => {
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
        Swal.fire({
          icon: 'success',
          title: 'Proceeding to Checkout...',
          timer: 1000,
          showConfirmButton: false
        }).then(() => {
          window.location.href = 'checkout.php';
        });
      }
    });
  }
});
</script>

</body>
</html>
