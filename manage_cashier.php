<?php
session_start();
if (!isset($_SESSION['admin_ID'])) {
    header("Location: admin_L.php");
    exit();
}

require_once('./classes/database.php');
$db = new Database();

$adminName = htmlspecialchars($_SESSION['admin_FN'] ?? 'Admin');

// Fetch all products
$products = $db->getAllProducts();
$categories = array_unique(array_map(fn($p) => $p['product_category'] ?? 'Other', $products));

// Handle order submission
$flash = null;  // For flash messages

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $cart = json_decode($_POST['cart'], true);
    $payment_method = $_POST['payment_method'] ?? 'Cash';
    $cash_received = floatval($_POST['cash_received'] ?? 0);

    // Ensure cart is not empty
    if (!empty($cart)) {
        $total_price = 0;
        foreach ($cart as $item) {
            $total_price += $item['price'] * $item['quantity'];
        }

        $change = $payment_method === 'Cash' ? max($cash_received - $total_price, 0) : 0;

        try {
            // Start a transaction
            $db->conn->beginTransaction();

            // Insert order into `order` table (walk-in, status Completed)
            $stmtOrder = $db->conn->prepare("
                INSERT INTO `order` 
                (customer_id, order_channel, total_amount, order_status)
                VALUES (NULL, 'walk-in', ?, 'Completed')
            ");
            $stmtOrder->execute([$total_price]);

            $order_ID = $db->conn->lastInsertId();
            if (!$order_ID) {
                throw new Exception('Failed to insert into the `order` table');
            }

            // Insert each item into `order_item`
            $stmtItem = $db->conn->prepare("
                INSERT INTO `order_item` (order_id, product_id, quantity, price) 
                VALUES (?, ?, ?, ?)
            ");
            foreach ($cart as $item) {
                $stmtItem->execute([$order_ID, $item['id'], $item['quantity'], $item['price']]);
            }

            // Insert payment
            $stmtPayment = $db->conn->prepare("
                INSERT INTO payment (order_id, payment_method, payment_amount)
                VALUES (?, ?, ?)
            ");
            $stmtPayment->execute([$order_ID, $payment_method, $total_price]);

            // Commit
            $db->conn->commit();

            // Set the flash message for success
            $change_amount = number_format($change, 2);
            $flash = [
                'title' => 'Success',
                'text'  => "Order placed successfully! | Change: ₱{$change_amount}",
                'icon'  => 'success',
                'redirect' => 'manage_cashier.php'
            ];
        } catch (Exception $e) {
            $db->conn->rollBack();
            // Set the flash message for error
            $msg = addslashes($e->getMessage());
            $flash = [
                'title' => 'Error',
                'text'  => "Failed to place order: {$msg}",
                'icon'  => 'error'
            ];
        }
    } else {
        // Set the flash message for empty cart
        $flash = [
            'title' => 'Error',
            'text'  => 'Cart is empty!',
            'icon'  => 'error'
        ];
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">  
<title>Manage Cashier | Cashier Panel</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
body {
  font-family: 'Quicksand', sans-serif;
  background: #e0e0e0;
  color: #2b2b2b;
  margin: 0;
  height: 100vh;
  overflow: hidden;
}
.wrapper { display: flex; height: 100vh; overflow: hidden; }
.sidebar {
  width: 250px;
  flex-shrink: 0;
  background: #1c1c1c;
  color: #fff;
  box-shadow: 3px 0 12px rgba(0,0,0,0.25);
  display: flex;
  flex-direction: column;
  position: fixed;
  top: 0; bottom: 0; left: 0;
  overflow-y: auto;
}
.main { margin-left: 250px; flex-grow: 1; display: flex; flex-direction: column; height: 100vh; overflow: hidden; }
.content { flex-grow: 1; overflow-y: auto; padding: 20px; }

.sidebar .nav-link {
  color: #bdbdbd;
  font-weight: 500;
  margin-bottom: 10px;
  padding: 10px 15px;
  border-radius: 12px;
  transition: all 0.3s ease;
}
.sidebar .nav-link.active, .sidebar .nav-link:hover {
  background-color: #6f4e37;
  color: #fff;
  transform: translateX(6px);
}

.admin-header {
  background: #f4f4f4;
  padding: 15px 25px;
  border-bottom: 1px solid #d6d6d6;
  box-shadow: 0 2px 6px rgba(0,0,0,0.08);
  flex-shrink: 0;
}

.section-title {
  border-left: 6px solid #6f4e37;
  padding-left: 12px;
  margin: 30px 0 20px;
  font-weight: 700;
  color: #333;
  text-transform: uppercase;
  letter-spacing: 1px;
}

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
</head>
<body>
<div class="wrapper">

  <!-- Sidebar -->
<div class="sidebar p-3">
  <h4 class="text-white mb-4 text-center"><i class="fas fa-mug-hot me-2"></i>Izana Admin</h4>
  <ul class="nav nav-pills flex-column">
    <li><a href="admin.php" class="nav-link"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
    <li><a href="view_customers.php" class="nav-link"><i class="fas fa-users me-2"></i>View Customers</a></li>
    <li><a href="view_orders.php" class="nav-link"><i class="fas fa-receipt me-2"></i>View Orders</a></li>
    <li><a href="manage_products.php" class="nav-link"><i class="fas fa-mug-hot me-2"></i>Manage Products</a></li>
    <li><a href="cashier.php" class="nav-link"><i class="fas fa-cash-register me-2"></i>Online Cashier</a></li>
    <li><a href="manage_cashier.php" class="nav-link active"><i class="fas fa-users-cog me-2"></i>POS</a></li>
    <li><a href="sales_report.php" class="nav-link"><i class="fas fa-chart-line me-2"></i>Sales Report</a></li>
    <li><a href="edit_profile.php" class="nav-link"><i class="fas fa-user-edit me-2"></i>Edit Profile</a></li>
    <li><a href="Logout_A.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
  </ul>
</div>

  <!-- Main -->
  <div class="main">
    <!-- Header -->
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
                  <img src="uploads/<?= htmlspecialchars($product['product_image'] ?? 't.jpg') ?>" class="card-img-top" alt="<?= htmlspecialchars($product['product_name']) ?>">
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
              <form method="POST" id="orderForm">
                <ul class="list-group" id="cartItems"></ul>
                <input type="hidden" name="cart" id="cartInput">
                <div class="total-price">Total: ₱<span id="totalPrice">0</span></div>

                <select name="order_type" class="form-select mb-2" required>
                  <option value="Dine-in">Dine-in</option>
                  <option value="Take-out">Take-out</option>
                </select>

                <select name="payment_method" id="paymentMethod" class="form-select mb-2">
                  <option value="Cash">Cash</option>
                  <option value="GCash">GCash</option>
                </select>

                <input type="number" name="cash_received" id="cashReceived" class="form-control mb-1" placeholder="Cash received" step="0.01">
                <div class="change-display" id="changeDisplay">Change: ₱0.00</div>
                <button type="submit" name="place_order" class="btn btn-place w-100"><i class="fa fa-check"></i> Place Order</button>
              </form>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<script>
// JavaScript for Cart Management and Form Submission

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

// Calculate change for cash payments
const cashInput = document.getElementById('cashReceived');
cashInput.addEventListener('input', updateChange);

function updateChange() {
    let total = parseFloat(document.getElementById('totalPrice').textContent) || 0;
    let cash = parseFloat(cashInput.value) || 0;
    let change = Math.max(cash - total, 0);
    document.getElementById('changeDisplay').textContent = `Change: ₱${change.toFixed(2)}`;
}

</script>

<?php if ($flash): ?>
<script>
  // Ensure Swal exists & run after DOM is ready
  window.addEventListener('DOMContentLoaded', function () {
    Swal.fire({
      title: <?= json_encode($flash['title']) ?>,
      text: <?= json_encode($flash['text']) ?>,
      icon: <?= json_encode($flash['icon']) ?>,
    }).then(function() {
      <?php if (!empty($flash['redirect'])): ?>
      window.location.href = <?= json_encode($flash['redirect']) ?>;
      <?php endif; ?>
    });
  });
</script>
<?php endif; ?>

</body>
</html>
