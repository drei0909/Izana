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

// Fetch categories
$stmt = $db->conn->prepare("SELECT * FROM product_categories WHERE is_active = 1");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch products
$products = $db->getAllProducts() ?? [];

$grouped = [];
foreach ($products as $p) {
    $cat = $p['category_id'] ?? 'Other';
    $grouped[$cat][] = $p;
}

function escape($s) {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function card_html($p) {
    $id = (int)($p['product_id'] ?? 0);
    $name = escape($p['product_name'] ?? 'Unnamed');
    $price = number_format((float)($p['product_price'] ?? 0), 2);

    
    $img_db = '';
    if (!empty($p['image_path'])) {
        $img_db = $p['image_path']; 
    } elseif (!empty($p['image'])) {
        $img_db = $p['image'];
    }
    
    $img_src = $img_db ? '../' . ltrim($img_db, '/\\') : '../uploads/bgggg.jpg';
    $img_src = escape($img_src);

   
    $dataPrice = htmlspecialchars((string)($p['product_price'] ?? '0'), ENT_QUOTES);
    $dataName = htmlspecialchars($name, ENT_QUOTES);

    $html  = '<div class="col-12 col-sm-6 col-lg-4">';
    $html .= '<div class="menu-card">';
    $html .= '<div class="card-media">';
    $html .= '<img src="' . $img_src . '" alt="' . $name . '" class="img-fluid rounded">';
    $html .= '</div>';
    $html .= '<div class="menu-body">';
    $html .= '<div class="menu-name fw-bold">' . $name . '</div>';
    $html .= '<div class="menu-bottom d-flex justify-content-between align-items-center">';
    $html .= '<div class="menu-price text-success fw-bold">₱' . $price . '</div>';
    $html .= '<button class="btn btn-sm btn-add add-to-cart" data-id="' . $id . '" data-name="' . $dataName . '" data-price="' . $dataPrice . '">Add</button>';
    $html .= '</div></div></div></div>';
    return $html;
}
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php include('templates/header.php'); ?>

<style>
  .menu-card { transition: transform 0.2s, box-shadow 0.2s; border-radius: 12px; background: #fff; padding: 10px; }
  .cart-container { background: #fff; padding: 15px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.08); }
  .cart-item { display: flex; justify-content: space-between; align-items: center; padding: 6px 10px; border-radius: 8px; background: #fff7e6; margin-bottom: 6px; font-size: 14px; }
  .btn-add { background-color: #b07542; border: none; color: #fff; font-size: 13px; }
  .btn-add:hover { background-color: #8a5c33; }
  .btn-place { background-color: #6c4b35; border: none; color: #fff; font-weight: bold; }
  .btn-place:hover { background-color: #8a5c33; }
  .total-price { font-size: 20px; font-weight: bold; margin-bottom: 10px; }
  .change-display { font-weight: bold; margin-top: 10px; color: green; }
</style>

<div class="wrapper">
<?php include('templates/sidebar.php'); ?>

<div class="main">
  <div class="admin-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Welcome, <?= $adminName ?></h5>
    <span class="text-muted"><i class="fas fa-user-shield me-1"></i>Admin Panel</span>
  </div>

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
                <option value="<?= htmlspecialchars($cat['category_id']) ?>"><?= htmlspecialchars($cat['category']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <?php foreach($grouped as $catId => $items): 
              $stmt = $db->conn->prepare("SELECT category FROM product_categories WHERE category_id = ?");
              $stmt->execute([$catId]);
              $catRow = $stmt->fetch(PDO::FETCH_ASSOC);
              $catName = $catRow ? $catRow['category'] : "Other";
          ?>
          <section class="mb-4">
           <h4 class="border-bottom pb-2 mb-3 text-dark fw-bold"><?= strtoupper(escape($catName)) ?></h4>

            <div class="row gy-3">
              <?php foreach($items as $item) echo card_html($item); ?>
            </div>
          </section>
          <?php endforeach; ?>
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

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let cart = [];

function renderCart() {
    const cartList = document.getElementById('cartItems');
    cartList.innerHTML = '';
    let total = 0;
    cart.forEach((item, index) => {
        total += item.price * item.quantity;
        cartList.innerHTML += `
        <li class="cart-item list-group-item d-flex justify-content-between align-items-center">
            <div>${item.name} (x${item.quantity})</div>
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

// attach add-to-cart handlers (for dynamically-rendered content, use event delegation)
document.addEventListener('click', function(e) {
    if (e.target.closest('.add-to-cart')) {
        const btn = e.target.closest('.add-to-cart');
        const id = btn.dataset.id;
        const name = btn.dataset.name;
        const price = parseFloat(btn.dataset.price);
        const existing = cart.find(i => i.id == id);
        if (existing) existing.quantity++;
        else cart.push({id, name, price, quantity:1});
        renderCart();
    }
});

const cashInput = document.getElementById('cashReceived');
if (cashInput) cashInput.addEventListener('input', updateChange);
document.getElementById('paymentMethod').addEventListener('change', updateChange);

function updateChange() {
    let total = parseFloat(document.getElementById('totalPrice').textContent) || 0;
    let cash = parseFloat(cashInput.value) || 0;
    let method = document.getElementById('paymentMethod').value;
    let change = (method === 'Cash') ? Math.max(cash - total, 0) : 0;
    document.getElementById('changeDisplay').textContent = `Change: ₱${change.toFixed(2)}`;
}

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

    
    fetch("admin_functions.php", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if(data.status === "success"){
            Swal.fire("Success", data.message + "<br>Change: ₱" + data.change, "success")
            .then(() => {
                cart = [];
                renderCart();
                
                // set palang ng pang realod if gusto
            });
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
<script>
// 🔍 SEARCH + CATEGORY FILTER
document.addEventListener("DOMContentLoaded", function () {
  const searchInput = document.getElementById("searchInput");
  const categoryFilter = document.getElementById("categoryFilter");

  function filterProducts() {
    const searchTerm = searchInput.value.toLowerCase();
    const selectedCategory = categoryFilter.value;

    // Loop through all menu cards
    document.querySelectorAll(".menu-card").forEach(card => {
      const productName = card.querySelector(".menu-name")?.textContent.toLowerCase() || "";
      const cardCategory = card.closest("section")?.getAttribute("data-category") || "";

      const matchesSearch = productName.includes(searchTerm);
      const matchesCategory = selectedCategory === "" || selectedCategory === cardCategory;

      // Show or hide
      card.parentElement.style.display = (matchesSearch && matchesCategory) ? "block" : "none";
    });
  }

  searchInput.addEventListener("input", filterProducts);
  categoryFilter.addEventListener("change", filterProducts);
});
</script>

</body>
</html>
