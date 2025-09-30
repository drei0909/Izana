<?php
session_start();

require_once('../classes/database.php');
include_once __DIR__ . "/../classes/config.php";

$db = new Database();

if (!isset($_SESSION['admin_ID'])) {
    header("Location: admin_L.php");
    exit();
}

$adminName = htmlspecialchars($_SESSION['admin_FN'] ?? 'Admin');

// Fetch all products
$products = $db->getAllProducts();
$categories = array_unique(array_map(fn($p) => $p['product_category'] ?? 'Other', $products));
?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php include ('templates/header.php'); ?>

<style>
  .menu-card { transition: transform 0.2s, box-shadow 0.2s; cursor: pointer; border-radius: 12px; }
  .menu-card:hover { transform: scale(1.03); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
  .card-img-top { height: 140px; object-fit: cover; border-radius: 12px 12px 0 0; }
  .cart-container { background: #fff; padding: 15px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.08); }
  .cart-item { display: flex; justify-content: space-between; align-items: center; padding: 6px 10px; border-radius: 8px; background: #fff7e6; margin-bottom: 6px; font-size: 14px; }
  .cart-item button { padding: 3px 6px; }
  .btn-add { background-color: #b07542; border: none; color: #fff; font-size: 13px; }
  .btn-add:hover { background-color: #8a5c33; }
  .btn-place { background-color: #6c4b35; margin-top: 10px; border: none; color: #fff; font-weight: bold; }
  .btn-place:hover { background-color: #8a5c33; }
  .total-price { font-size: 20px; font-weight: bold; margin-bottom: 10px; }
  .change-display { font-weight: bold; margin-top: 10px; color: green; }
</style>

<div class="wrapper">

<?php include ('templates/sidebar.php'); ?>

  <div class="main">
    <div class="admin-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">Welcome, <?= $adminName ?></h5>
      <span class="text-muted"><i class="fas fa-user-shield me-1"></i>Admin Panel</span>
    </div>

    <!-- Content -->
    <div class="content">
      <div class="container-fluid">
        <h4 class="section-title"><i class="fas fa-cash-register me-2"></i>Point of Sale</h4>
        <div class="row">
          <!-- Menu -->
          <div class="col-md-8">
            <div class="mb-3 d-flex gap-2">
              <input type="text" id="searchInput" class="form-control" placeholder="Search products...">
              <select id="categoryFilter" class="form-select">
                <option value="">All Categories</option>
                <?php foreach($categories as $cat): ?>
                  <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="row" id="menuList">
              <?php foreach ($products as $product): ?>
                <div class="col-md-4 mb-3 menu-item" data-category="<?= htmlspecialchars($product['product_category'] ?? 'Other') ?>" data-name="<?= strtolower($product['product_name']) ?>">
                  <div class="card menu-card shadow-sm">
                    <img src="uploads/<?= htmlspecialchars($product['product_image'] ?? 'bgggg.JPG') ?>" class="card-img-top" alt="<?= htmlspecialchars($product['product_name']) ?>">
                    <div class="card-body text-center">
                      <h6 class="card-title"><?= htmlspecialchars($product['product_name']) ?></h6>
                      <p class="card-text mb-2">₱<?= number_format($product['product_price'], 2) ?></p>
                      <button class="btn btn-add btn-sm add-to-cart"
                          data-id="<?= $product['product_id'] ?>"
                          data-name="<?= htmlspecialchars($product['product_name']) ?>"
                          data-price="<?= $product['product_price'] ?>">Add <i class="fa fa-plus"></i></button>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Cart -->
          <div class="col-md-4">
            <div class="cart-container">
              <h4>Cart</h4>
              <form id="orderForm">
                <ul class="list-group" id="cartItems"></ul>
                <input type="hidden" name="cart" id="cartInput">
                <div class="total-price">Total: ₱<span id="totalPrice">0</span></div>

                <select name="payment_method" id="paymentMethod" class="form-select mb-2">
                  <option value="Cash">Cash</option>
                  <option value="GCash">GCash</option>
                  <option value="Card">Card</option>
                </select>

                <input type="number" name="cash_received" id="cashReceived" class="form-control mb-1" placeholder="Cash received" step="0.01">
                <div class="change-display" id="changeDisplay">Change: ₱0.00</div>
                <button type="submit" class="btn btn-place w-100"><i class="fa fa-check"></i> Place Order</button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Cart & POS logic -->
<script>
let cart = [];

function renderCart() {
    let cartList = document.getElementById('cartItems');
    cartList.innerHTML = '';
    let total = 0;
    cart.forEach((item, index) => {
        total += item.price * item.quantity;
        cartList.innerHTML += `
        <li class="cart-item list-group-item">
            ${item.name} 
            <span class="badge bg-primary rounded-pill">${item.quantity}</span>
            <div>
                <button onclick="changeQty(${index}, -1)" class="btn btn-sm btn-danger me-1"><i class="fa fa-minus"></i></button>
                <button onclick="changeQty(${index}, 1)" class="btn btn-sm btn-success me-1"><i class="fa fa-plus"></i></button>
                <button onclick="removeItem(${index})" class="btn btn-sm btn-outline-danger"><i class="fa fa-trash"></i></button>
            </div>
        </li>`;
    });
    document.getElementById('totalPrice').textContent = total.toFixed(2);
    document.getElementById('cartInput').value = JSON.stringify(cart);
    updateChange();
}

function removeItem(index) { cart.splice(index, 1); renderCart(); }
function changeQty(index, delta) { cart[index].quantity += delta; if(cart[index].quantity<=0) cart.splice(index,1); renderCart(); }

document.querySelectorAll('.add-to-cart').forEach(btn => {
    btn.addEventListener('click', () => {
        const id = btn.dataset.id;
        const name = btn.dataset.name;
        const price = parseFloat(btn.dataset.price);
        const existing = cart.find(i => i.id == id);
        if (existing) existing.quantity++;
        else cart.push({id, name, price, quantity:1});
        renderCart();
    });
});

// Show change if payment is Cash
const cashInput = document.getElementById('cashReceived');
cashInput.addEventListener('input', updateChange);
document.getElementById('paymentMethod').addEventListener('change', updateChange);

function updateChange() {
    let total = parseFloat(document.getElementById('totalPrice').textContent) || 0;
    let cash = parseFloat(cashInput.value) || 0;
    let method = document.getElementById('paymentMethod').value;
    let change = (method === 'Cash') ? Math.max(cash - total, 0) : 0;
    document.getElementById('changeDisplay').textContent = `Change: ₱${change.toFixed(2)}`;
}

// Submit order
document.getElementById("orderForm").addEventListener("submit", function(e){
    e.preventDefault();

    let cartData = JSON.parse(document.getElementById("cartInput").value || "[]");
    if(cartData.length === 0){
        Swal.fire("Error", "Cart is empty!", "error");
        return;
    }

    let formData = new FormData();
    formData.append("ref", "place_pos_order");
    formData.append("cart", JSON.stringify(cartData));
    formData.append("payment_method", document.getElementById("paymentMethod").value);
    formData.append("cash_received", document.getElementById("cashReceived").value);

  fetch("../functions.php", {
    method: "POST",
    body: formData
})

    .then(res => res.json())
    .then(data => {
        if(data.status === "success"){
            Swal.fire("Success", data.message + "<br>Change: ₱" + data.change, "success")
            .then(() => window.location.reload());
        } else {
            Swal.fire("Error", data.message, "error");
        }
    })
    .catch(err => {
        console.error("Fetch error:", err);
        Swal.fire("Error", "Something went wrong", "error");
    });
});
</script>

</body>
</html>
