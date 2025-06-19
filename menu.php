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
      text-align: center;
      margin: 10px auto;
      font-weight: 600;
    }

    .btn-logout {
      position: fixed;
      top: 20px;
      right: 20px;
      background-color: transparent;
      border: 2px solid #b07542;
      padding: 8px 18px;
      border-radius: 30px;
      font-weight: 600;
      color: #4b3a2f;
      text-decoration: none;
      z-index: 1000;
    }

    .btn-logout:hover {
      background: #f8e5d0;
    }

    #floatingCart {
      position: fixed;
      bottom: 20px;
      right: 20px;
      width: 300px;
      background: rgba(255,255,255,0.95);
      border-radius: 15px;
      box-shadow: 0 6px 20px rgba(0,0,0,0.2);
      padding: 15px;
      z-index: 9999;
      cursor: move;
      color: #000;
    }
  </style>
</head>
<body>

<a href="login.php" class="btn-logout"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>

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

<!-- Movable Floating Cart -->
<div id="floatingCart" class="shadow-lg">
  <h5 class="text-center fw-bold mb-3">🛒 Your Cart</h5>
  <div id="cart-items" class="mb-3" style="max-height: 200px; overflow-y: auto;"></div>
  <hr class="my-2">
  <div id="cart-total" class="fw-bold text-end">Total: ₱0</div>
  <div class="text-center mt-3">
    <button id="checkoutBtn" class="btn btn-sm btn-success w-100">Checkout</button>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let cart = [];

// Add to Cart
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

// Render Cart
function renderCart() {
  const cartDiv = document.getElementById('cart-items');
  cartDiv.innerHTML = '';
  let total = 0;

  if (cart.length === 0) {
    cartDiv.innerHTML = `<div class="text-muted text-center">Your cart is empty.</div>`;
  }

  cart.forEach((item, index) => {
    const itemTotal = item.price * item.quantity;
    total += itemTotal;

    cartDiv.innerHTML += `
      <div class="d-flex justify-content-between align-items-start mb-2 border-bottom pb-2">
        <div>
          <strong>${item.quantity}× ${item.name}</strong><br>
          <small>₱${item.price} each</small><br>
          <small class="text-muted">₱${itemTotal}</small>
        </div>
        <div class="text-end">
          <button class="btn btn-sm btn-outline-danger mb-1" onclick="removeCartItem(${index})">
            <i class="fas fa-trash-alt"></i>
          </button>
          <br>
          <button class="btn btn-sm btn-outline-warning" onclick="replaceCartItem(${index})">
            <i class="fas fa-sync-alt"></i>
          </button>
        </div>
      </div>
    `;
  });

  document.getElementById('cart-total').innerText = `Total: ₱${total}`;
}

// Remove from Cart
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

// Replace Item
function replaceCartItem(index) {
  const itemToReplace = cart[index];

  // Sample product list (you can generate dynamically from PHP too)
  const options = [
    { name: 'Latte', price: 90 },
    { name: 'Caramel Macchiato', price: 90 },
    { name: 'Matcha Strawberry Latte', price: 140 },
    { name: 'Iced Spanish Latte', price: 100 },
    { name: 'Pearl', price: 20 }
  ];

  let selectHTML = '<select id="newItem" class="swal2-select">';
  options.forEach((opt, i) => {
    selectHTML += `<option value="${i}">${opt.name} - ₱${opt.price}</option>`;
  });
  selectHTML += '</select>';

  Swal.fire({
    title: 'Replace Item',
    html: `<p>Replacing <strong>${itemToReplace.name}</strong></p>${selectHTML}`,
    confirmButtonText: 'Replace',
    showCancelButton: true,
    confirmButtonColor: '#b07542'
  }).then(result => {
    if (result.isConfirmed) {
      const selectedIndex = document.getElementById('newItem').value;
      const newItem = options[selectedIndex];

      // Replace but keep quantity
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

// Checkout button behavior
document.addEventListener("DOMContentLoaded", () => {
  const checkoutBtn = document.getElementById("checkoutBtn");
  if (checkoutBtn) {
    checkoutBtn.addEventListener("click", function () {
      if (cart.length === 0) {
        Swal.fire({
          icon: 'warning',
          title: 'Cart is Empty!',
          text: 'Add something to checkout.',
          confirmButtonColor: '#b07542'
        });
      } else {
        // You can redirect or submit here
        window.location.href = 'checkout.php';
      }
    });
  }
});

// Make floating cart draggable
(function dragElement(el) {
  let pos1 = 0, pos2 = 0, pos3 = 0, pos4 = 0;
  el.onmousedown = dragMouseDown;

  function dragMouseDown(e) {
    e.preventDefault();
    pos3 = e.clientX;
    pos4 = e.clientY;
    document.onmouseup = closeDrag;
    document.onmousemove = elementDrag;
  }

  function elementDrag(e) {
    e.preventDefault();
    pos1 = pos3 - e.clientX;
    pos2 = pos4 - e.clientY;
    pos3 = e.clientX;
    pos4 = e.clientY;
    el.style.top = (el.offsetTop - pos2) + "px";
    el.style.left = (el.offsetLeft - pos1) + "px";
  }

  function closeDrag() {
    document.onmouseup = null;
    document.onmousemove = null;
  }
})(document.getElementById("floatingCart"));
</script>


</body>
</html>
